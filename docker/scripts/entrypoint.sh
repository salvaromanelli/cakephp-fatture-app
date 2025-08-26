#!/bin/bash
set -e

echo "🚀 Starting CakePHP Fatture App..."

# Ensure proper permissions
chown -R www-data:www-data /var/www/html/tmp /var/www/html/logs
chmod -R 777 /var/www/html/tmp /var/www/html/logs

# Clear CakePHP cache if in development
if [ "$CAKE_ENV" != "production" ]; then
    echo "Clearing CakePHP cache..."
    rm -rf /var/www/html/tmp/cache/* 2>/dev/null || true
fi

# Run database migrations if needed
if [ "$RUN_MIGRATIONS" = "true" ]; then
    echo "Running database migrations..."
    cd /var/www/html && php bin/cake.php migrations migrate || true
fi

# Create health check endpoint
cat > /var/www/html/webroot/health.php << 'HEALTH_EOF'
<?php
http_response_code(200);
header('Content-Type: text/plain');
echo "healthy";
?>
HEALTH_EOF

echo "✅ Setup complete. Starting Apache..."

# Execute the main command
exec "$@"
