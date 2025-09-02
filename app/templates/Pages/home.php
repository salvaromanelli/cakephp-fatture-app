<!DOCTYPE html>
<html>
<head>
    <title>CakePHP Fatture API</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        body { font-family: Arial; margin: 40px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 40px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .status { padding: 20px; background: #d4edda; border-left: 4px solid #28a745; margin: 20px 0; }
        .endpoint { background: #e9ecef; padding: 10px; margin: 10px 0; border-radius: 4px; font-family: monospace; }
        .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin: 20px 0; }
        .card { background: #f8f9fa; padding: 20px; border-radius: 4px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🧾 CakePHP Fatture API</h1>
        <p>Sistema de gestión de facturas - Backend API funcionando correctamente</p>
        
        <div class="status">
            <strong>✅ Estado:</strong> API funcionando correctamente<br>
            <strong>🚀 Entorno:</strong> AWS Elastic Beanstalk<br>
            <strong>📅 Última actualización:</strong> <?= date('Y-m-d H:i:s') ?>
        </div>

        <h2>📊 Endpoints de API disponibles:</h2>
        <div class="endpoint">GET /health - Health check del sistema</div>
        <div class="endpoint">GET /api/invoices.json - Lista de facturas</div>
        <div class="endpoint">GET /api/invoices/stats.json - Estadísticas</div>
        <div class="endpoint">GET /api/invoices/{id}.json - Factura específica</div>

        <div class="grid">
            <div class="card">
                <h3>🔧 Stack Técnico</h3>
                <ul>
                    <li>CakePHP Framework</li>
                    <li>PHP 8.2</li>
                    <li>MySQL 8.0 (RDS)</li>
                    <li>AWS Elastic Beanstalk</li>
                </ul>
            </div>
            <div class="card">
                <h3>🎯 Características</h3>
                <ul>
                    <li>API REST completa</li>
                    <li>CORS habilitado</li>
                    <li>JSON responses</li>
                    <li>Health monitoring</li>
                </ul>
            </div>
        </div>
        
        <p style="text-align: center; color: #6c757d; margin-top: 40px;">
            🌐 Ready para conectar con React Frontend
        </p>
    </div>
</body>
</html>
