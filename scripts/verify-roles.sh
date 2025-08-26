#!/bin/bash

echo "🔍 Verificando roles de Elastic Beanstalk..."

# Verificar rol EC2
echo "📋 Verificando aws-elasticbeanstalk-ec2-role..."
if aws iam get-role --role-name aws-elasticbeanstalk-ec2-role >/dev/null 2>&1; then
    echo "✅ aws-elasticbeanstalk-ec2-role existe"
    echo "   Políticas adjuntas:"
    aws iam list-attached-role-policies --role-name aws-elasticbeanstalk-ec2-role --query 'AttachedPolicies[].PolicyName' --output text
else
    echo "❌ aws-elasticbeanstalk-ec2-role NO existe"
fi

echo ""

# Verificar rol service
echo "📋 Verificando aws-elasticbeanstalk-service-role..."
if aws iam get-role --role-name aws-elasticbeanstalk-service-role >/dev/null 2>&1; then
    echo "✅ aws-elasticbeanstalk-service-role existe"
    echo "   Políticas adjuntas:"
    aws iam list-attached-role-policies --role-name aws-elasticbeanstalk-service-role --query 'AttachedPolicies[].PolicyName' --output text
else
    echo "❌ aws-elasticbeanstalk-service-role NO existe"
fi

echo ""
echo "🎯 Cuando ambos roles existan, estarás listo para el deployment!"
