#!/bin/bash
set -e

# Colores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Variables de configuración
APP_NAME="cakephp-fatture-app"
ENV_NAME="cakephp-fatture-prod"
REGION="eu-west-1"
S3_BUCKET="your-eb-deployments-bucket"
VERSION_LABEL="v$(date +%Y%m%d-%H%M%S)"

echo -e "${BLUE}🚀 Starting AWS deployment process for CakePHP Fatture App...${NC}"

# Verificar dependencias
command -v aws >/dev/null 2>&1 || { echo -e "${RED}❌ AWS CLI is required but not installed.${NC}" >&2; exit 1; }
command -v docker >/dev/null 2>&1 || { echo -e "${RED}❌ Docker is required but not installed.${NC}" >&2; exit 1; }

# Build Docker image
echo -e "${YELLOW}📦 Building Docker image...${NC}"
docker build -t $APP_NAME:$VERSION_LABEL -t $APP_NAME:latest .

# Test the image locally
echo -e "${YELLOW}🧪 Testing Docker image...${NC}"
docker run --rm -d --name test-$APP_NAME -p 8080:80 \
  -e CAKE_ENV=production \
  -e DEBUG=false \
  $APP_NAME:$VERSION_LABEL

# Wait for container to start
sleep 15

# Health check
echo -e "${YELLOW}🏥 Running health check...${NC}"
if curl -f -s http://localhost:8080/health > /dev/null; then
    echo -e "${GREEN}✅ Health check passed${NC}"
    docker stop test-$APP_NAME
else
    echo -e "${RED}❌ Health check failed${NC}"
    docker logs test-$APP_NAME
    docker stop test-$APP_NAME
    exit 1
fi

# Create deployment package
echo -e "${YELLOW}📦 Creating deployment package...${NC}"
zip -r deploy-$VERSION_LABEL.zip . \
  -x "*.git*" \
  "node_modules/*" \
  "*.log" \
  "app/tmp/*" \
  "*.DS_Store" \
  "deploy-*.zip"

# Check if S3 bucket exists
if ! aws s3 ls "s3://$S3_BUCKET" 2>&1 > /dev/null; then
    echo -e "${YELLOW}📦 Creating S3 bucket: $S3_BUCKET${NC}"
    aws s3 mb s3://$S3_BUCKET --region $REGION
fi

# Upload to S3
echo -e "${YELLOW}☁️ Uploading to S3...${NC}"
aws s3 cp deploy-$VERSION_LABEL.zip s3://$S3_BUCKET/ --region $REGION

# Check if EB application exists
if ! aws elasticbeanstalk describe-applications --application-names $APP_NAME --region $REGION 2>/dev/null | grep -q $APP_NAME; then
    echo -e "${YELLOW}🏗️ Creating Elastic Beanstalk application...${NC}"
    aws elasticbeanstalk create-application \
        --application-name $APP_NAME \
        --description "CakePHP Fatture Application" \
        --region $REGION
fi

# Create application version
echo -e "${YELLOW}📋 Creating application version...${NC}"
aws elasticbeanstalk create-application-version \
    --application-name $APP_NAME \
    --version-label $VERSION_LABEL \
    --description "Deployment $(date)" \
    --source-bundle S3Bucket="$S3_BUCKET",S3Key="deploy-$VERSION_LABEL.zip" \
    --region $REGION

# Check if environment exists
if ! aws elasticbeanstalk describe-environments --application-name $APP_NAME --environment-names $ENV_NAME --region $REGION 2>/dev/null | grep -q $ENV_NAME; then
    echo -e "${YELLOW}🌍 Creating Elastic Beanstalk environment...${NC}"
    aws elasticbeanstalk create-environment \
        --application-name $APP_NAME \
        --environment-name $ENV_NAME \
        --solution-stack-name "64bit Amazon Linux 2 v3.4.0 running PHP 8.2" \
        --version-label $VERSION_LABEL \
        --region $REGION \
        --option-settings \
            Namespace=aws:elasticbeanstalk:environment,OptionName=EnvironmentType,Value=SingleInstance \
            Namespace=aws:autoscaling:launchconfiguration,OptionName=InstanceType,Value=t3.small
    
    echo -e "${BLUE}⏳ Waiting for environment to be created (this may take several minutes)...${NC}"
    aws elasticbeanstalk wait environment-updated --application-name $APP_NAME --environment-names $ENV_NAME --region $REGION
else
    # Deploy to existing environment
    echo -e "${YELLOW}🚀 Deploying to existing Elastic Beanstalk environment...${NC}"
    aws elasticbeanstalk update-environment \
        --application-name $APP_NAME \
        --environment-name $ENV_NAME \
        --version-label $VERSION_LABEL \
        --region $REGION

    echo -e "${BLUE}⏳ Waiting for deployment to complete...${NC}"
    aws elasticbeanstalk wait environment-updated --application-name $APP_NAME --environment-names $ENV_NAME --region $REGION
fi

# Get environment URL
ENV_URL=$(aws elasticbeanstalk describe-environments \
    --application-name $APP_NAME \
    --environment-names $ENV_NAME \
    --region $REGION \
    --query 'Environments[0].CNAME' \
    --output text)

echo -e "${GREEN}✅ Deployment completed successfully!${NC}"
echo -e "${GREEN}🌐 Application URL: http://$ENV_URL${NC}"
echo -e "${GREEN}🔍 AWS Console: https://$REGION.console.aws.amazon.com/elasticbeanstalk/home?region=$REGION#/environment/dashboard?environmentId=$ENV_NAME${NC}"

# Cleanup
rm deploy-$VERSION_LABEL.zip

# Final health check
echo -e "${YELLOW}🏥 Final health check on deployed application...${NC}"
sleep 30
if curl -f -s http://$ENV_URL/health > /dev/null; then
    echo -e "${GREEN}✅ Production health check passed!${NC}"
else
    echo -e "${YELLOW}⚠️ Production health check failed - check AWS console${NC}"
fi

echo -e "${BLUE}🎉 Deployment process completed!${NC}"
