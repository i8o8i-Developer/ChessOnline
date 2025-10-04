<?php
// maintenance.php - Maintenance Mode Page
require_once 'Config.php';

// Send proper maintenance status
http_response_code(503);
header('Retry-After: 3600');
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Maintenance | I8O8IChess</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link href="https://fonts.googleapis.com/css2?family=Press+Start+2P&display=swap" rel="stylesheet">
  <style>
    :root{
      --bg-0: #0b0b0b;
      --bg-1: #131313;
      --panel: #181818;
      --muted: #2f2f2f;
      --accent: #4CAF50;
      --accent-2: #3fa34a;
      --text-on-dark: #dfeee0;
      --glass: rgba(255,255,255,0.02);
      --card-radius: 18px;
    }

    *{ box-sizing: border-box; margin:0; padding:0 }
    html,body{ height:100%; }

    body{
      font-family: 'Courier New', monospace;
      background: radial-gradient(900px 300px at 8% 10%, rgba(76,175,80,0.03), transparent), linear-gradient(180deg,var(--bg-0),var(--bg-1));
      color:var(--text-on-dark);
      min-height:100vh;
      display:flex;
      align-items:center;
      justify-content:center;
      padding:40px;
      -webkit-font-smoothing:antialiased;
    }

    .Container{
      width:100%;
      max-width:820px;
      background: linear-gradient(180deg, rgba(24,24,24,0.88), rgba(16,16,16,0.88));
      padding:48px 56px;
      border-radius: var(--card-radius);
      border:1px solid rgba(255,255,255,0.03);
      box-shadow: 0 38px 80px rgba(0,0,0,0.7), inset 0 1px 0 rgba(255,255,255,0.02);
      text-align: center;
    }

    .Logo{ margin-bottom:22px }
    .Logo h1{
      font-family: 'Press Start 2P', monospace;
      color:var(--accent);
      font-size:44px;
      line-height:1;
      letter-spacing:3px;
      text-shadow: 0 12px 36px rgba(76,175,80,0.12), 0 2px 0 rgba(0,0,0,0.6);
      margin:0 0 12px 0;
    }

    .MaintenanceIcon{
      font-size: 120px;
      margin: 16px 0 14px 0;
      color: var(--accent);
      filter: drop-shadow(0 12px 30px rgba(50,150,50,0.08));
      animation: float 3.6s ease-in-out infinite;
      display:inline-block;
    }

    @keyframes float{ 0%,100%{ transform: translateY(0) } 50%{ transform: translateY(-8px) } }

    .Message{
      font-size: 22px;
      margin-bottom: 10px;
      color: #9aa79a;
      letter-spacing: 1px;
    }

    .Description{
      font-size: 15px;
      color: #7a8a7a;
      margin-bottom: 26px;
      max-width:700px;
      margin-left:auto; margin-right:auto;
      line-height:1.6;
    }

    .btn{
      display:inline-block;
      padding:12px 22px;
      border-radius:12px;
      background: linear-gradient(180deg,var(--accent), var(--accent-2));
      color:#06210a;
      font-weight:800;
      text-decoration:none;
      box-shadow: 0 10px 28px rgba(76,175,80,0.12);
      transition: transform 160ms ease, box-shadow 160ms ease;
    }
    .btn:hover{ transform: translateY(-3px); box-shadow: 0 20px 40px rgba(76,175,80,0.16) }

    @media (max-width:720px){
      .Logo h1{ font-size:36px }
      .MaintenanceIcon{ font-size:84px }
      .Container{ padding:28px }
    }
  </style>
</head>
<body>
  <div class="Container">
    <div class="Logo">
      <h1><?php echo htmlspecialchars($AppConfig['AppLogo'] ?? '{i8o8i}'); ?>Chess</h1>
    </div>
    <div class="MaintenanceIcon">🔧</div>
    <div class="Message">Under Maintenance</div>
    <p class="Description">We're Currently Performing Maintenance. Please Check Back Later. We Appreciate Your Patience — Updates Will Be Available As Soon As Possible.</p>
  </div>
</body>
</html>