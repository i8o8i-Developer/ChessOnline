<?php
require_once 'Config.php';
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Contact Developer - I8O8IChess</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link href="https://fonts.googleapis.com/css2?family=Press+Start+2P&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="static/style.css">
  <style>
  :root{
    --bg-0:#060606;
    --bg-1:#0f0f10;
    --card-0:#0f1112;
    --card-1:#0c0c0c;
    --accent:#4CAF50;
    --muted:#9aa0a6;
    --glass: rgba(255,255,255,0.02);
    --radius:12px;
  }

  *,*::before,*::after{ box-sizing:border-box }
  /* Center The Card And Prevent Page Scrollbar; Allow The Card To Scroll Internally When Content Is Tall */
  html, body { height: 100%; }
  body{ font-family:'Courier New', monospace; margin:0; padding:32px; background: radial-gradient(900px 300px at 8% 8%, rgba(76,175,80,0.02), transparent), linear-gradient(180deg,var(--bg-0),var(--bg-1)); color:#e6efe6; height:100vh; box-sizing:border-box; display:flex; align-items:center; justify-content:center; overflow:hidden }

  .Card{ max-width:920px; width:100%; margin:0 auto; background: linear-gradient(180deg,var(--card-0),var(--card-1)); padding:28px; border-radius:var(--radius); border:1px solid rgba(255,255,255,0.03); box-shadow: 0 20px 60px rgba(0,0,0,0.7); max-height: calc(100vh - 64px); overflow:auto; -webkit-overflow-scrolling: touch; }

  .header-row{ display:flex; align-items:center; justify-content:space-between; gap:12px; margin-bottom:8px }
  h1{ color:var(--accent); font-family: 'Press Start 2P', 'Courier New', monospace; font-size:28px; margin:0; letter-spacing:1px }

  .header-actions{ display:flex; gap:10px }
  .header-btn{ text-decoration:none; display:inline-flex; align-items:center; gap:8px; padding:8px 12px; border-radius:10px; font-weight:800; font-size:13px; background:linear-gradient(180deg,var(--accent), #3fa34a); color:#06210a; box-shadow: 0 8px 24px rgba(76,175,80,0.12) }
  .header-btn.small{ padding:6px 10px; font-size:12px }

  .note{ color:var(--muted); margin:6px 0 12px; font-size:14px }

  label{ display:block; margin-top:12px; margin-bottom:8px; color:var(--muted); font-size:18px }
  .form-row{ width:100%; font-weight: 800; }

  input, textarea{ width:100%; padding:14px 14px; border-radius:10px; border:1px solid rgba(255,255,255,0.04); background: linear-gradient(180deg,#eef7ff, #e2ecf7); color:#052127; font-size:14px; box-shadow: inset 0 6px 18px rgba(12,20,30,0.06); transition: box-shadow 160ms ease, transform 120ms ease }
  input::placeholder, textarea::placeholder{ color:#6b6b6b }
  input:focus, textarea:focus{ outline:none; box-shadow: 0 10px 40px rgba(76,175,80,0.12); transform: translateY(-2px); border-color: rgba(76,175,80,0.95) }
  textarea{ min-height:220px; resize:vertical }

  .btn-send{ margin-top:18px; padding:12px 18px; border-radius:10px; border:none; background:linear-gradient(180deg,var(--accent), #3fa34a); color:#06210a; font-weight:800; cursor:pointer; box-shadow: 0 10px 30px rgba(76,175,80,0.14); transition: transform 140ms ease, box-shadow 140ms ease }
  .btn-send:hover{ transform: translateY(-3px); box-shadow: 0 20px 50px rgba(76,175,80,0.18) }

  a.mail-link{ color:#9EEBAF; text-decoration:underline }

  .footer-links{ margin-top:18px; display:flex; gap:12px; align-items:center }
  .btn-ghost{ background: linear-gradient(180deg,#151515,#0f0f0f); color:#d6d6d6; padding:8px 12px; border-radius:10px; border:1px solid rgba(255,255,255,0.03); text-decoration:none; box-shadow: 0 6px 18px rgba(0,0,0,0.6) }
  .btn-ghost:hover{ transform: translateY(-2px) }

  @media (max-width:700px){ .Card{ padding:18px } h1{ font-size:20px } .header-row{ flex-direction:column; align-items:flex-start; gap:10px } .header-actions{ width:100%; justify-content:flex-end } }
  </style>
</head>
<body>
  <div class="Card">
    <div class="header-row">
      <h1>Contact Developer</h1>
      <div class="header-actions">
        <a class="header-btn small" href="index.php">Home</a>
      </div>
    </div>
    <p class="note">Use The Form Below To Compose an Email to the Developer. This Will Open Your Default Mail App with the Message Prefilled.</p>
    <form id="contactForm" onsubmit="return sendMail();">
      <div class="form-row">
        <label for="fromName">Your Name</label>
        <input id="fromName" name="fromName" placeholder="Your Name" />
      </div>

      <div class="form-row">
        <label for="fromEmail">Your Email</label>
        <input id="fromEmail" name="fromEmail" placeholder="your@example.com" />
      </div>

      <div class="form-row">
        <label for="subject">Subject</label>
        <input id="subject" name="subject" placeholder="Subject" />
      </div>

      <div class="form-row">
        <label for="message">Message</label>
        <textarea id="message" name="message" rows="8" placeholder="Write Your Message Here..."></textarea>
      </div>

      <div class="form-row"><button class="btn-send" type="submit">Send Email</button></div>
    </form>

    <p class="note">Or Email Directly: <a class="mail-link" href="mailto:i8o8iworkstation@outlook.com">i8o8iworkstation@outlook.com</a></p>

    <div class="footer-links">
      <a class="btn-ghost" href="lobby.php">← Back to Lobby</a>
      <div class="plus">+</div>
    </div>
  </div>

  <script>
    function sendMail(){
      const to = 'i8o8iworkstation@outlook.com';
      const name = document.getElementById('fromName').value || '';
      const from = document.getElementById('fromEmail').value || '';
      const subject = document.getElementById('subject').value || '';
      const body = document.getElementById('message').value || '';

      const fullBody = `From: ${name} (${from})\n\n${body}`;
      const mailto = `mailto:${encodeURIComponent(to)}?subject=${encodeURIComponent(subject)}&body=${encodeURIComponent(fullBody)}`;
      window.location.href = mailto;
      return false;
    }
  </script>
</body>
</html>