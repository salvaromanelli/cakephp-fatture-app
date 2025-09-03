<!DOCTYPE html>
<html>
<head>
    <title>CakePHP Fatture API ✅</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        body{font-family:system-ui;margin:0;background:linear-gradient(135deg,#667eea,#764ba2);min-height:100vh;display:flex;align-items:center;justify-content:center}
        .card{background:white;border-radius:12px;padding:40px;max-width:700px;box-shadow:0 20px 40px rgba(0,0,0,0.1)}
        .status{background:#d4edda;color:#155724;padding:20px;border-radius:8px;margin:20px 0;border-left:4px solid #28a745}
        .endpoints{background:#f8f9fa;padding:20px;border-radius:8px;margin:20px 0}
        .endpoint{font-family:monospace;background:#e9ecef;padding:8px;margin:8px 0;border-radius:4px}
        h1{color:#333;margin:0 0 20px;text-align:center}
    </style>
</head>
<body>
    <div class="card">
        <h1>🧾 CakePHP Fatture API</h1>
        <div class="status">
            <strong>✅ API Funcionando Correctamente</strong><br>
            📅 Deployado: <?= date('Y-m-d H:i:s T') ?><br>
            🚀 AWS Elastic Beanstalk<br>
            ⚡ PHP <?= phpversion() ?>
        </div>
        
        <div class="endpoints">
            <strong>🌐 API Endpoints Disponibles:</strong>
            <div class="endpoint">GET /health</div>
            <div class="endpoint">GET /api/invoices.json</div>
            <div class="endpoint">GET /api/invoices/stats.json</div>
            <div class="endpoint">GET /api/invoices/{id}.json</div>
        </div>
        
        <p style="text-align:center;"><strong>🎯 Ready para conectar con React Frontend</strong></p>
    </div>
</body>
</html>
