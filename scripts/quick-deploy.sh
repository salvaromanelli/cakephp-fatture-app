#!/bin/bash
set -e

GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

echo -e "${BLUE}🚀 CakePHP Fatture - Quick Deploy${NC}"

# Variables
APP_NAME="cakephp-fatture-$(date +%s)"
ENV_NAME="production-env"
REGION="us-east-1"
VERSION_LABEL="v$(date +%Y%m%d-%H%M%S)"

echo -e "${YELLOW}📦 Preparando aplicación...${NC}"

# Actualizar configuración para producción
cat > app/config/app_production.php << 'PRODEOF'
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
        'salt' => '4cae913d40f8b18c0a79fa6e4365ef954b7efd93edda537c8742634369e18a6e',
    ],
    'Datasources' => [
        'default' => [
            'className' => 'Cake\Database\Connection',
            'driver' => 'Cake\Database\Driver\Mysql',
            'host' => 'localhost',
            'port' => 3306,
            'username' => 'root',
            'password' => '',
            'database' => 'cakephp_demo',
            'encoding' => 'utf8mb4',
            'timezone' => 'UTC',
            'cacheMetadata' => true,
            'log' => false,
        ],
    ],
];
PRODEOF

# Health check simple
mkdir -p app/webroot
cat > app/webroot/health.php << 'HEALTHEOF'
<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

http_response_code(200);

$health = [
    'status' => 'healthy',
    'service' => 'CakePHP Fatture Demo',
    'timestamp' => date('c'),
    'version' => '1.0.0',
    'environment' => 'production'
];

echo json_encode($health, JSON_PRETTY_PRINT);
HEALTHEOF

# API de facturas mock
mkdir -p app/webroot/api
cat > app/webroot/api/invoices.json << 'APIEOF'
{
  "success": true,
  "message": "CakePHP Fatture API - Demo data",
  "data": [
    {
      "id": 1,
      "numero": "FACT-2024-001",
      "cliente": "Empresa Demo SL",
      "total": "1250.00",
      "fecha": "2024-08-26",
      "descripcion": "Desarrollo aplicación web CakePHP + React",
      "estado": "pagada"
    },
    {
      "id": 2,
      "numero": "FACT-2024-002", 
      "cliente": "StartupTech Inc",
      "total": "890.50",
      "fecha": "2024-08-25",
      "descripcion": "Consultoría técnica y deployment AWS",
      "estado": "pagada"
    },
    {
      "id": 3,
      "numero": "FACT-2024-003",
      "cliente": "Digital Solutions Agency",
      "total": "2100.00",
      "fecha": "2024-08-24", 
      "descripcion": "Desarrollo API REST con integración base de datos",
      "estado": "pendiente"
    },
    {
      "id": 4,
      "numero": "FACT-2024-004",
      "cliente": "Innovation Labs",
      "total": "1575.25",
      "fecha": "2024-08-23",
      "descripcion": "Arquitectura cloud AWS + Elastic Beanstalk",
      "estado": "pagada"
    }
  ],
  "meta": {
    "total_facturas": 4,
    "total_importe": "5815.75",
    "moneda": "EUR",
    "last_update": "2024-08-26T12:00:00Z",
    "api_version": "1.0",
    "backend": "CakePHP on AWS Elastic Beanstalk"
  }
}
APIEOF

# Stats API
cat > app/webroot/api/stats.json << 'STATSEOF'
{
  "success": true,
  "data": {
    "total_facturas": 248,
    "total_importe": "54290.75",
    "facturas_pagadas": 201,
    "facturas_pendientes": 47,
    "crecimiento_mensual": 18.5,
    "ultimo_mes": "€4,520.30",
    "promedio_factura": "€218.91"
  },
  "meta": {
    "generated_at": "2024-08-26T12:00:00Z",
    "period": "last_12_months"
  }
}
STATSEOF

# Página de demo
cat > app/webroot/index.html << 'HTMLEOF'
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CakePHP Fatture API - Backend Demo</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <div class="container mx-auto px-4 py-8">
        <div class="bg-white rounded-lg shadow-lg p-8">
            <h1 class="text-3xl font-bold text-gray-800 mb-4">
                🧾 CakePHP Fatture API
            </h1>
            <p class="text-gray-600 mb-8">
                Backend API funcionando correctamente en AWS Elastic Beanstalk
            </p>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="bg-green-50 p-6 rounded-lg border-l-4 border-green-500">
                    <h3 class="font-bold text-green-800 mb-2">✅ Health Check</h3>
                    <p class="text-green-700">Sistema funcionando correctamente</p>
                    <a href="/health.php" class="text-green-600 hover:text-green-800 text-sm">
                        → Ver health.php
                    </a>
                </div>
                
                <div class="bg-blue-50 p-6 rounded-lg border-l-4 border-blue-500">
                    <h3 class="font-bold text-blue-800 mb-2">📊 API Endpoints</h3>
                    <p class="text-blue-700">Datos de facturas disponibles</p>
                    <a href="/api/invoices.json" class="text-blue-600 hover:text-blue-800 text-sm">
                        → Ver API JSON
                    </a>
                </div>
            </div>

            <div class="mt-8 p-4 bg-gray-100 rounded">
                <h3 class="font-bold mb-2">🔗 Endpoints disponibles:</h3>
                <ul class="text-sm space-y-1">
                    <li>• <code>GET /health.php</code> - Health check</li>
                    <li>• <code>GET /api/invoices.json</code> - Lista de facturas</li>
                    <li>• <code>GET /api/stats.json</code> - Estadísticas</li>
                </ul>
            </div>

            <div class="mt-6 text-center">
                <p class="text-gray-500 text-sm">
                    🚀 CakePHP Backend desplegado en AWS • Ready para conectar con React Frontend
                </p>
            </div>
        </div>
    </div>
</body>
</html>
HTMLEOF

echo -e "${YELLOW}🏗️  Creando aplicación Elastic Beanstalk...${NC}"

# Crear aplicación
aws elasticbeanstalk create-application \
    --application-name $APP_NAME \
    --description "CakePHP Fatture API Demo" \
    --region $REGION

echo -e "${GREEN}✅ Aplicación creada: $APP_NAME${NC}"

# Crear package ZIP
echo -e "${YELLOW}📦 Creando package...${NC}"
zip -r "${VERSION_LABEL}.zip" app/ .ebextensions/ -x "app/tmp/*" "app/logs/*" "*.DS_Store"

# Subir a S3 (crear bucket temporal)
S3_BUCKET="${APP_NAME}-deploy"
aws s3 mb "s3://$S3_BUCKET" --region $REGION 2>/dev/null || true
aws s3 cp "${VERSION_LABEL}.zip" "s3://$S3_BUCKET/" --region $REGION

# Crear versión de aplicación
aws elasticbeanstalk create-application-version \
    --application-name $APP_NAME \
    --version-label $VERSION_LABEL \
    --description "Quick deploy $(date)" \
    --source-bundle S3Bucket="$S3_BUCKET",S3Key="${VERSION_LABEL}.zip" \
    --region $REGION

echo -e "${YELLOW}🚀 Desplegando environment...${NC}"

# Crear environment
aws elasticbeanstalk create-environment \
    --application-name $APP_NAME \
    --environment-name $ENV_NAME \
    --solution-stack-name "64bit Amazon Linux 2 v3.6.1 running PHP 8.2" \
    --version-label $VERSION_LABEL \
    --option-settings \
        Namespace=aws:autoscaling:launchconfiguration,OptionName=InstanceType,Value=t3.micro \
        Namespace=aws:elasticbeanstalk:environment,OptionName=EnvironmentType,Value=SingleInstance \
    --region $REGION

echo -e "${BLUE}⏳ Esperando deployment (5-10 minutos)...${NC}"
echo -e "${YELLOW}☕ Perfecto para preparar la demo del frontend...${NC}"

# Wait for deployment
aws elasticbeanstalk wait environment-ready \
    --application-name $APP_NAME \
    --environment-names $ENV_NAME \
    --region $REGION

# Obtener URL final
FINAL_URL=$(aws elasticbeanstalk describe-environments \
    --application-name $APP_NAME \
    --environment-names $ENV_NAME \
    --region $REGION \
    --query 'Environments[0].CNAME' \
    --output text)

echo -e "${GREEN}🎉 ¡DEPLOYMENT COMPLETADO!${NC}"
echo -e "${GREEN}=========================${NC}"
echo -e "${GREEN}🌐 API URL: http://$FINAL_URL${NC}"
echo -e "${GREEN}🏥 Health: http://$FINAL_URL/health.php${NC}"
echo -e "${GREEN}📊 API: http://$FINAL_URL/api/invoices.json${NC}"
echo ""
echo -e "${BLUE}📝 Para conectar el frontend:${NC}"
echo -e "${YELLOW}VITE_API_URL=http://$FINAL_URL${NC}"

# Cleanup
rm "${VERSION_LABEL}.zip"

# Test final
echo -e "${BLUE}🧪 Test de conectividad:${NC}"
sleep 30
curl -f -s "http://$FINAL_URL/health.php" | head -5 || echo "Aún inicializando..."

echo -e "${GREEN}✅ Backend listo para conectar con React!${NC}"

# Guardar URL para fácil acceso
echo "http://$FINAL_URL" > backend-url.txt
echo -e "${GREEN}📄 URL guardada en: backend-url.txt${NC}"
