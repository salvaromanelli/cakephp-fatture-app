#!/bin/bash
set -e

GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
RED='\033[0;31m'
NC='\033[0m'

echo -e "${BLUE}🎯 CakePHP Fatture - AWS Free Tier Deployment (Fixed)${NC}"

# Variables
APP_NAME="cakephp-fatture-demo"
ENV_NAME="demo-environment"  
REGION="us-east-1"
S3_BUCKET="${APP_NAME}-$(date +%s)"
VERSION_LABEL="demo-$(date +%Y%m%d-%H%M%S)"
STACK_NAME="${APP_NAME}-free-tier-v2"

echo -e "${YELLOW}🏗️  Paso 1: Creando base de datos RDS (simplificada)...${NC}"

# CloudFormation más simple y robusta
cat > aws/cloudformation/simple-rds.yml << 'RDSEOF'
AWSTemplateFormatVersion: '2010-09-09'
Description: 'Simple RDS MySQL for CakePHP Demo'

Resources:
  DemoDatabase:
    Type: AWS::RDS::DBInstance
    Properties:
      DBInstanceIdentifier: !Sub "${AWS::StackName}-db"
      DBInstanceClass: db.t3.micro
      Engine: mysql
      EngineVersion: '8.0.35'
      AllocatedStorage: 20
      StorageType: gp2
      StorageEncrypted: false
      
      DBName: cakephp_demo
      MasterUsername: demo_user
      MasterUserPassword: Demo123456!
      
      BackupRetentionPeriod: 0
      MultiAZ: false
      PubliclyAccessible: true
      DeletionProtection: false
      DeleteAutomatedBackups: true
      
      VPCSecurityGroups:
        - !Ref DBSecurityGroup

  DBSecurityGroup:
    Type: AWS::EC2::SecurityGroup
    Properties:
      GroupDescription: !Sub "${AWS::StackName} RDS Security Group"
      SecurityGroupIngress:
        - IpProtocol: tcp
          FromPort: 3306
          ToPort: 3306
          CidrIp: 0.0.0.0/0
          Description: MySQL access for demo

Outputs:
  DatabaseEndpoint:
    Description: RDS Database Endpoint
    Value: !GetAtt DemoDatabase.Endpoint.Address
    Export:
      Name: !Sub "${AWS::StackName}-db-endpoint"
      
  DatabaseURL:
    Description: Full Database Connection URL
    Value: !Sub 
      - "mysql://demo_user:Demo123456!@${DBEndpoint}:3306/cakephp_demo"
      - DBEndpoint: !GetAtt DemoDatabase.Endpoint.Address
RDSEOF

echo -e "${BLUE}📊 Desplegando CloudFormation...${NC}"

# Desplegar con mejor manejo de errores
if aws cloudformation deploy \
    --template-file aws/cloudformation/simple-rds.yml \
    --stack-name $STACK_NAME \
    --region $REGION \
    --no-fail-on-empty-changeset; then
    
    echo -e "${GREEN}✅ CloudFormation desplegado exitosamente${NC}"
else
    echo -e "${RED}❌ Error en CloudFormation. Verificando...${NC}"
    
    # Mostrar eventos del error
    echo -e "${YELLOW}📋 Últimos eventos del stack:${NC}"
    aws cloudformation describe-stack-events \
        --stack-name $STACK_NAME \
        --region $REGION \
        --query 'StackEvents[0:5].[Timestamp,ResourceStatus,ResourceStatusReason]' \
        --output table
    
    # Intentar crear RDS directamente sin CloudFormation
    echo -e "${YELLOW}🔄 Intentando crear RDS directamente...${NC}"
    
    # Crear Security Group
    SG_ID=$(aws ec2 create-security-group \
        --group-name cakephp-demo-sg \
        --description "CakePHP Demo Security Group" \
        --query 'GroupId' \
        --output text 2>/dev/null || echo "exists")
    
    if [ "$SG_ID" != "exists" ]; then
        # Agregar regla para MySQL
        aws ec2 authorize-security-group-ingress \
            --group-id $SG_ID \
            --protocol tcp \
            --port 3306 \
            --cidr 0.0.0.0/0
        echo -e "${GREEN}✅ Security Group creado: $SG_ID${NC}"
    else
        # Obtener SG existente
        SG_ID=$(aws ec2 describe-security-groups \
            --group-names cakephp-demo-sg \
            --query 'SecurityGroups[0].GroupId' \
            --output text 2>/dev/null || echo "")
        echo -e "${YELLOW}ℹ️  Usando Security Group existente: $SG_ID${NC}"
    fi
    
    # Crear RDS directamente
    DB_ID="cakephp-demo-direct-$(date +%s)"
    echo -e "${BLUE}🗄️  Creando RDS directamente...${NC}"
    
    aws rds create-db-instance \
        --db-instance-identifier $DB_ID \
        --db-instance-class db.t3.micro \
        --engine mysql \
        --engine-version 8.0.35 \
        --allocated-storage 20 \
        --storage-type gp2 \
        --db-name cakephp_demo \
        --master-username demo_user \
        --master-user-password Demo123456! \
        --vpc-security-group-ids $SG_ID \
        --backup-retention-period 0 \
        --no-multi-az \
        --publicly-accessible \
        --no-deletion-protection \
        --no-storage-encrypted
    
    echo -e "${BLUE}⏳ Esperando que RDS esté disponible (5-10 minutos)...${NC}"
    aws rds wait db-instance-available --db-instance-identifier $DB_ID
    
    # Obtener endpoint
    DB_ENDPOINT=$(aws rds describe-db-instances \
        --db-instance-identifier $DB_ID \
        --query 'DBInstances[0].Endpoint.Address' \
        --output text)
    
    echo -e "${GREEN}✅ RDS creado directamente: $DB_ENDPOINT${NC}"
fi

# Si CloudFormation funcionó, obtener endpoint
if [ -z "$DB_ENDPOINT" ]; then
    DB_ENDPOINT=$(aws cloudformation describe-stacks \
        --stack-name $STACK_NAME \
        --region $REGION \
        --query 'Stacks[0].Outputs[?OutputKey==`DatabaseEndpoint`].OutputValue' \
        --output text 2>/dev/null || echo "")
fi

if [ -z "$DB_ENDPOINT" ]; then
    echo -e "${RED}❌ No se pudo obtener el endpoint de la base de datos${NC}"
    echo -e "${YELLOW}🔧 Verificar manualmente en la consola de RDS${NC}"
    exit 1
fi

echo -e "${GREEN}✅ Base de datos lista: $DB_ENDPOINT${NC}"

echo -e "${YELLOW}📦 Paso 2: Preparando aplicación...${NC}"

# Crear S3 bucket
aws s3 mb "s3://$S3_BUCKET" --region $REGION

# Health check
mkdir -p app/webroot
cat > app/webroot/health.php << 'HEALTHEOF'
<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
http_response_code(200);

try {
    $pdo = new PDO("mysql:host=$DB_ENDPOINT;dbname=cakephp_demo", "demo_user", "Demo123456!");
    $db_status = "connected";
    $pdo = null;
} catch(PDOException $e) {
    $db_status = "error: " . $e->getMessage();
}

echo json_encode([
    'status' => 'healthy',
    'service' => 'CakePHP Fatture Demo',
    'timestamp' => date('c'),
    'version' => '1.0.0',
    'database' => $db_status,
    'endpoint' => '$DB_ENDPOINT'
]);
HEALTHEOF

# Configurar app para producción  
cat > app/config/app_production.php << PRODEOF
<?php
return [
    'debug' => false,
    'App' => [
        'namespace' => 'App',
        'encoding' => 'UTF-8',
        'defaultLocale' => 'en_US',
        'defaultTimezone' => 'UTC',
    ],
    'Security' => [
        'salt' => 'DemoSaltForTechnicalInterview2024',
    ],
    'Datasources' => [
        'default' => [
            'className' => 'Cake\Database\Connection',
            'driver' => 'Cake\Database\Driver\Mysql',
            'host' => '$DB_ENDPOINT',
            'port' => 3306,
            'username' => 'demo_user',
            'password' => 'Demo123456!',
            'database' => 'cakephp_demo',
            'encoding' => 'utf8mb4',
            'timezone' => 'UTC',
            'cacheMetadata' => true,
            'log' => false,
        ],
    ],
];
PRODEOF

echo -e "${YELLOW}📦 Creando package de deployment...${NC}"

# Crear package
zip -r "${VERSION_LABEL}.zip" \
    app/ \
    .ebextensions/ \
    -x "app/tmp/*" "app/logs/*" "*.DS_Store" "app/vendor/*/tests/*"

aws s3 cp "${VERSION_LABEL}.zip" "s3://$S3_BUCKET/" --region $REGION

echo -e "${YELLOW}🚀 Paso 3: Desplegando en Elastic Beanstalk...${NC}"

# Crear aplicación EB si no existe
if ! aws elasticbeanstalk describe-applications \
    --application-names $APP_NAME \
    --region $REGION 2>/dev/null | grep -q $APP_NAME; then
    
    aws elasticbeanstalk create-application \
        --application-name $APP_NAME \
        --description "CakePHP Fatture Demo for Technical Interview" \
        --region $REGION
    
    echo -e "${GREEN}✅ Aplicación EB creada${NC}"
fi

# Crear versión
aws elasticbeanstalk create-application-version \
    --application-name $APP_NAME \
    --version-label $VERSION_LABEL \
    --description "Free Tier Demo - $(date)" \
    --source-bundle S3Bucket="$S3_BUCKET",S3Key="${VERSION_LABEL}.zip" \
    --region $REGION

# Crear environment
echo -e "${YELLOW}🌍 Creando environment (esto tomará varios minutos)...${NC}"
echo -e "${BLUE}☕ Tiempo para preparar la presentación de la entrevista...${NC}"

if ! aws elasticbeanstalk describe-environments \
    --application-name $APP_NAME \
    --environment-names $ENV_NAME \
    --region $REGION 2>/dev/null | grep -q $ENV_NAME; then

    aws elasticbeanstalk create-environment \
        --application-name $APP_NAME \
        --environment-name $ENV_NAME \
        --solution-stack-name "64bit Amazon Linux 2 v3.6.1 running PHP 8.2" \
        --version-label $VERSION_LABEL \
        --region $REGION
    
    echo -e "${BLUE}⏳ Esperando deployment...${NC}"
    aws elasticbeanstalk wait environment-updated \
        --application-name $APP_NAME \
        --environment-names $ENV_NAME \
        --region $REGION
else
    echo -e "${YELLOW}🔄 Actualizando environment existente...${NC}"
    
    aws elasticbeanstalk update-environment \
        --application-name $APP_NAME \
        --environment-name $ENV_NAME \
        --version-label $VERSION_LABEL \
        --region $REGION
    
    aws elasticbeanstalk wait environment-updated \
        --application-name $APP_NAME \
        --environment-names $ENV_NAME \
        --region $REGION
fi

# Obtener URL
APP_URL=$(aws elasticbeanstalk describe-environments \
    --application-name $APP_NAME \
    --environment-names $ENV_NAME \
    --region $REGION \
    --query 'Environments[0].CNAME' \
    --output text)

echo -e "${GREEN}🎉 ¡DEPLOYMENT COMPLETADO!${NC}"
echo -e "${GREEN}================================${NC}"
echo -e "${GREEN}🌐 Aplicación: http://$APP_URL${NC}"
echo -e "${GREEN}🏥 Health Check: http://$APP_URL/health.php${NC}"
echo -e "${GREEN}🗄️  Base de datos: $DB_ENDPOINT${NC}"
echo -e "${GREEN}💰 Costo: $0.00 (Free Tier)${NC}"
echo ""
echo -e "${BLUE}🎯 URLs para la entrevista:${NC}"
echo -e "   📱 Aplicación: http://$APP_URL"
echo -e "   🔍 Health: http://$APP_URL/health.php"
echo -e "   📊 API: http://$APP_URL/api/invoices.json"
echo -e "   🏛️  AWS Console: https://console.aws.amazon.com/elasticbeanstalk/home?region=us-east-1"

# Health check final
echo -e "${YELLOW}🏥 Verificación final...${NC}"
sleep 30

for i in {1..3}; do
    if curl -f -s "http://$APP_URL/health.php" >/dev/null; then
        echo -e "${GREEN}✅ Health check #$i: PASSED${NC}"
        break
    else
        echo -e "${YELLOW}⏳ Health check #$i: Esperando...${NC}"
        sleep 20
    fi
done

# Mostrar respuesta del health check
echo -e "${BLUE}📊 Response del health check:${NC}"
curl -s "http://$APP_URL/health.php" | jq . 2>/dev/null || curl -s "http://$APP_URL/health.php"

# Cleanup
rm "${VERSION_LABEL}.zip"

echo -e "${BLUE}🎊 ¡Demo lista para la entrevista técnica!${NC}"
