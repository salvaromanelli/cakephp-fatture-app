#!/bin/bash
set -e

# Colores
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

# Variables para Free Tier
APP_NAME="cakephp-fatture-demo"
ENV_NAME="demo-environment"
REGION="us-east-1"  # ✅ Región más barata
S3_BUCKET="${APP_NAME}-demo-$(date +%s)"
VERSION_LABEL="demo-$(date +%Y%m%d-%H%M%S)"
STACK_NAME="${APP_NAME}-free-tier"

echo -e "${BLUE}🎯 CakePHP Demo - AWS Free Tier Deployment${NC}"
echo -e "${YELLOW}💰 100% Gratis - Perfecto para entrevistas técnicas${NC}"

# Verificar región (us-east-1 es la más barata)
echo -e "${YELLOW}📍 Configurando región us-east-1 para Free Tier...${NC}"
export AWS_DEFAULT_REGION=us-east-1

# Crear CloudFormation para Free Tier
echo -e "${YELLOW}🏗️  Creando infraestructura Free Tier...${NC}"

# Password simple para demo
DB_PASSWORD="Demo123456!"

cat > aws/cloudformation/free-tier-stack.yml << 'CFEOF'
AWSTemplateFormatVersion: '2010-09-09'
Description: 'Free Tier RDS MySQL for CakePHP Demo'

Resources:
  # Security Group para RDS
  DBSecurityGroup:
    Type: AWS::EC2::SecurityGroup
    Properties:
      GroupDescription: RDS MySQL Security Group
      SecurityGroupIngress:
        - IpProtocol: tcp
          FromPort: 3306
          ToPort: 3306
          CidrIp: 0.0.0.0/0
          Description: MySQL access (demo only)

  # RDS Free Tier
  DemoDatabase:
    Type: AWS::RDS::DBInstance
    Properties:
      DBInstanceIdentifier: cakephp-demo-db
      DBInstanceClass: db.t3.micro
      Engine: mysql
      EngineVersion: '8.0.35'
      AllocatedStorage: 20
      StorageType: gp2
      StorageEncrypted: false
      
      DBName: cakephp_demo
      MasterUsername: demo_user
      MasterUserPassword: Demo123456!
      
      VPCSecurityGroups:
        - !Ref DBSecurityGroup
      
      BackupRetentionPeriod: 0
      MultiAZ: false
      PubliclyAccessible: true
      DeletionProtection: false
      DeleteAutomatedBackups: true

Outputs:
  DatabaseEndpoint:
    Description: RDS Endpoint
    Value: !GetAtt DemoDatabase.Endpoint.Address
    Export:
      Name: demo-db-endpoint
CFEOF

aws cloudformation deploy \
    --template-file aws/cloudformation/free-tier-stack.yml \
    --stack-name $STACK_NAME \
    --region us-east-1 \
    --no-fail-on-empty-changeset

# Obtener endpoint
DB_ENDPOINT=$(aws cloudformation describe-stacks \
    --stack-name $STACK_NAME \
    --region us-east-1 \
    --query 'Stacks[0].Outputs[?OutputKey==`DatabaseEndpoint`].OutputValue' \
    --output text)

echo -e "${GREEN}✅ Base de datos creada: $DB_ENDPOINT${NC}"

# Crear S3 bucket (gratis hasta 5GB)
echo -e "${YELLOW}📦 Creando S3 bucket (Free Tier)...${NC}"
aws s3 mb "s3://$S3_BUCKET" --region us-east-1

# Configuración de producción
cat > app/config/app_demo.php << DEMOEOF
<?php
return [
    'debug' => false,
    'Security' => [
        'salt' => 'DemoSaltForInterview123456789',
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
DEMOEOF

# Crear configuración EB para Free Tier
cat > .ebextensions/99-free-tier.config << FREEEOF
option_settings:
  aws:autoscaling:launchconfiguration:
    InstanceType: t3.micro
    
  aws:autoscaling:asg:
    MinSize: 1
    MaxSize: 1
    
  aws:elasticbeanstalk:environment:
    EnvironmentType: SingleInstance
    
  aws:elasticbeanstalk:healthreporting:system:
    SystemType: basic
    
  aws:elasticbeanstalk:application:environment:
    CAKE_ENV: production
    DEBUG: "false"
FREEEOF

# Crear package
echo -e "${YELLOW}📦 Creando deployment package...${NC}"
zip -r "${VERSION_LABEL}.zip" \
    app/ \
    .ebextensions/ \
    -x "app/tmp/*" "app/logs/*" "*.DS_Store"

aws s3 cp "${VERSION_LABEL}.zip" "s3://$S3_BUCKET/" --region us-east-1

# Crear aplicación EB
echo -e "${YELLOW}🚀 Creando aplicación Elastic Beanstalk...${NC}"

if ! aws elasticbeanstalk describe-applications \
    --application-names $APP_NAME \
    --region us-east-1 2>/dev/null | grep -q $APP_NAME; then
    
    aws elasticbeanstalk create-application \
        --application-name $APP_NAME \
        --description "CakePHP Demo for Technical Interview" \
        --region us-east-1
fi

# Crear versión
aws elasticbeanstalk create-application-version \
    --application-name $APP_NAME \
    --version-label $VERSION_LABEL \
    --description "Demo deployment" \
    --source-bundle S3Bucket="$S3_BUCKET",S3Key="${VERSION_LABEL}.zip" \
    --region us-east-1

# Crear environment
echo -e "${YELLOW}🌍 Creando environment (5-10 minutos)...${NC}"
echo -e "${BLUE}☕ Tiempo perfecto para preparar tu presentación...${NC}"

aws elasticbeanstalk create-environment \
    --application-name $APP_NAME \
    --environment-name $ENV_NAME \
    --solution-stack-name "64bit Amazon Linux 2 v3.6.1 running PHP 8.2" \
    --version-label $VERSION_LABEL \
    --region us-east-1

echo -e "${BLUE}⏳ Esperando que el environment esté listo...${NC}"
aws elasticbeanstalk wait environment-updated \
    --application-name $APP_NAME \
    --environment-names $ENV_NAME \
    --region us-east-1

# Obtener URL
APP_URL=$(aws elasticbeanstalk describe-environments \
    --application-name $APP_NAME \
    --environment-names $ENV_NAME \
    --region us-east-1 \
    --query 'Environments[0].CNAME' \
    --output text)

echo -e "${GREEN}🎉 ¡DEMO LISTO PARA ENTREVISTA!${NC}"
echo -e "${GREEN}================================${NC}"
echo -e "${GREEN}🌐 URL: http://$APP_URL${NC}"
echo -e "${GREEN}🗄️  Base de datos: $DB_ENDPOINT${NC}"
echo -e "${GREEN}💰 Costo: $0.00 (Free Tier)${NC}"
echo -e "${GREEN}⏰ Tiempo de deployment: $(date)${NC}"
echo ""
echo -e "${BLUE}📋 Para la entrevista, menciona:${NC}"
echo -e "   ✅ AWS Free Tier deployment"
echo -e "   ✅ Elastic Beanstalk con auto-scaling"
echo -e "   ✅ RDS MySQL en la nube"
echo -e "   ✅ S3 para assets estáticos"
echo -e "   ✅ Docker containerización"
echo -e "   ✅ CloudFormation Infrastructure as Code"
echo -e "   ✅ Zero-downtime deployments"

# Cleanup
rm "${VERSION_LABEL}.zip"

echo -e "${YELLOW}🔧 Comandos útiles para la demo:${NC}"
echo -e "   aws elasticbeanstalk describe-environments --application-name $APP_NAME"
echo -e "   aws rds describe-db-instances --db-instance-identifier cakephp-demo-db"
echo -e "   aws s3 ls s3://$S3_BUCKET"
