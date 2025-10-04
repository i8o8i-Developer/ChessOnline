<?php
// 404.php - Page Not Found
require_once 'Config.php';

// Check For Maintenance Mode
if (file_exists('maintenance')) {
    include 'maintenance.php';
    exit();
}

// Set 404 Status Code
http_response_code(404);
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>404 - Page Not Found | I8O8IChess</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <style>
    :root{
      --bg-0: #0f0f10;
      --bg-1: #1c1c1c;
      --panel: #222426;
      --muted: #2f2f2f;
      --accent: #4CAF50;
      --text-on-dark: #dfeee0;
      --glass: rgba(255,255,255,0.02);
      --card-radius: 14px;
    }

    *{ box-sizing: border-box; margin:0; padding:0 }

    html,body{ height:100%; }
    body{
      font-family: 'Courier New', monospace;
      background: radial-gradient(1200px 400px at 10% 10%, rgba(76,175,80,0.03), transparent), linear-gradient(180deg,var(--bg-0),var(--bg-1));
      color:var(--text-on-dark);
      min-height:100vh;
      display:flex;
      align-items:center;
      justify-content:center;
      padding:28px;
      -webkit-font-smoothing:antialiased;
    }

    .Container{
      width:100%;
      max-width:640px;
      margin: 24px;
      background: linear-gradient(180deg, rgba(28,28,28,0.88), rgba(20,20,20,0.88));
      padding:36px 44px;
      border-radius: calc(var(--card-radius) + 6px);
      border:1px solid rgba(255,255,255,0.04);
      box-shadow: 0 28px 80px rgba(0,0,0,0.7), inset 0 1px 0 rgba(255,255,255,0.02);
      text-align: center;
      backdrop-filter: blur(6px);
      -webkit-backdrop-filter: blur(6px);
    }

    .Logo{ margin-bottom:28px }
    .Logo h1{
      font-family: 'Press Start 2P', monospace;
      color:var(--accent);
      font-size: clamp(26px, 6vw, 44px);
      line-height:1;
      letter-spacing:2px;
      text-shadow: 0 6px 18px rgba(76,175,80,0.12), 0 1px 0 rgba(0,0,0,0.6);
      margin-bottom:8px;
    }

    .ErrorCode{
      font-size: clamp(64px, 18vw, 120px);
      font-weight: 700;
      color: var(--accent);
      margin: 18px 0 10px 0;
      text-shadow: 0 6px 18px rgba(76,175,80,0.12);
      line-height: 0.9;
      font-family: 'Courier New', monospace;
      letter-spacing: 2px;
    }

    .Message{
      font-size: clamp(14px, 2.4vw, 18px);
      margin-bottom: 18px;
      color: #9aa79a;
    }

    .btn{
      display:inline-block;
      padding:12px 22px;
      border-radius:12px;
      font-weight:700;
      font-size:15px;
      text-decoration:none;
      border:none;
      cursor:pointer;
      transition: transform 140ms ease, box-shadow 140ms ease, opacity 120ms ease;
      background: linear-gradient(180deg,var(--accent), #3fa34a);
      color:#06210a;
      box-shadow: 0 10px 28px rgba(76,175,80,0.12);
      -webkit-font-smoothing:antialiased;
      margin-top:12px;
    }
    .btn:hover{ transform: translateY(-3px); box-shadow: 0 20px 44px rgba(76,175,80,0.16) }
    .btn:focus{ outline: 3px solid rgba(76,175,80,0.12); outline-offset: 3px }

    /* small screen adjustments */
    @media (max-width:420px){
      .Container{ padding: 20px 18px; margin: 14px }
      .ErrorCode{ font-size: clamp(48px, 28vw, 80px) }
    }

    /* Mobile fixes (<=475px): center container and enable internal scrolling */
    @media (max-width:475px){
      html, body { height:auto; min-height:100%; padding:12px; margin:0; overflow:auto }
      body{ display:flex; align-items:center; justify-content:center }
      .Container{ width:100%; max-width:460px; padding:16px; margin:0 auto; box-sizing:border-box; border-radius:12px; max-height: calc(100vh - 32px); overflow:auto; -webkit-overflow-scrolling: touch }
      .Logo h1{ font-size: clamp(20px, 6vw, 32px) }
      .ErrorCode{ font-size: clamp(48px, 18vw, 96px) }
      .btn{ width:100% !important; box-sizing:border-box }
    }
  </style>
  <link href="https://fonts.googleapis.com/css2?family=Press+Start+2P&display=swap" rel="stylesheet">
</head>
<body>
  <div class="Container">
    <div class="Logo">
      <h1><?php echo $AppConfig['AppLogo']; ?>Chess</h1>
    </div>
    <div class="ErrorCode">404</div>
    <div class="Message">Page not found</div>
    <p style="color:#b8cbb8; margin:0 0 10px 0;">The page you're looking for doesn't exist or has been moved.</p>
    <a href="index.php" class="btn">Go Home</a>
  </div>
</body>
</html>