<?php
require_once 'Config.php';

// Check for maintenance mode
if (file_exists('maintenance')) {
    include 'maintenance.php';
    exit();
}
?>

<!doctype html>
<html>
<head>
	<meta charset="utf-8">
	<title>Release Notes - I8O8IChess</title>
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<link href="https://fonts.googleapis.com/css2?family=Press+Start+2P&display=swap" rel="stylesheet">
	<link rel="stylesheet" href="static/style.css">
	<style>
	:root { --bg: #080808; --card: #0f1112; --muted: #9aa0a6; --accent: #4CAF50; --glass: rgba(255,255,255,0.03); }
	 /* Center The Card Both Horizontally And Vertically
		 Keep The ViewPort Free Of The Browser Scrollbar By Hiding Page Overflow
		 And Allowing The Card To Scroll Internally If Its Content Is Taller Than The Viewport. */
	 html, body { height:100%; }
	 body { font-family: 'Courier New', monospace; background: var(--bg); color:#fff; padding:28px; height:100vh; box-sizing:border-box; display:flex; align-items:center; justify-content:center; overflow:hidden; }
	 .Card { max-width:1000px; width:100%; margin:0 auto; background:linear-gradient(180deg,#0e0f10,#121416); padding:24px; border-radius:10px; border:1px solid var(--glass); box-shadow: 0 10px 40px rgba(0,0,0,0.7); max-height: calc(100vh - 56px); overflow:auto; -webkit-overflow-scrolling: touch; }
		.header-row { display:flex; align-items:center; justify-content:space-between; gap:12px; }
		h1 { color: var(--accent); font-family: 'Press Start 2P', 'Courier New', monospace; font-size:20px; margin:0; }
		.header-actions { display:flex; gap:8px; }
		.btn { display:inline-block; padding:8px 12px; border-radius:8px; text-decoration:none; font-weight:700; font-size:13px; }
		.btn.primary { background:var(--accent); color:#061a06; }
		.btn.ghost { background:transparent; color:#ddd; border:1px solid var(--glass); }

		.meta { color:var(--muted); font-size:0.9em; margin-top:8px; }

		.releases { display:grid; grid-template-columns: 1fr; gap:14px; margin-top:18px; }
		.release-card { background: linear-gradient(180deg, rgba(255,255,255,0.01), rgba(255,255,255,0.007)); padding:16px; border-radius:8px; border:1px solid rgba(255,255,255,0.02); }
		.release-card h2 { margin:0 0 8px 0; color:#c8f6d0; font-size:16px; }
		.release-meta { color:var(--muted); font-size:0.85em; margin-bottom:8px; }
		.changes { margin:0; padding-left:18px; color:#ddd; }
		.changes li { margin-bottom:6px; line-height:1.4; }

		.page-footer { margin-top:18px; display:flex; gap:12px; align-items:center; flex-wrap:wrap; }
		.small { color:var(--muted); font-size:0.85em; }

		@media (min-width:760px) {
			.releases { grid-template-columns: 1fr 1fr; }
		}
		@media (max-width:520px) {
			h1 { font-size:16px; }
			.btn { padding:8px 10px; font-size:12px; }
		}
		/* Small Screen Responsive Adjustments (<=475px) For Release Notes */
		@media (max-width:475px){
		html, body { height:auto; min-height:100%; padding:12px; margin:0; overflow:auto }
		body{ display:flex; align-items:center; justify-content:center }
		.Card{ width:100%; max-width:460px; padding:12px; margin:0 auto; box-sizing:border-box; border-radius:12px; max-height: calc(100vh - 32px); overflow:auto }
		.header-row{ flex-direction:column; align-items:center; gap:8px }
		.releases{ grid-template-columns:1fr }
		.header-actions{ justify-content:center }
		.btn{ width:100% !important; box-sizing:border-box }
		}
	</style>
</head>
<body>
	<div class="Card">
		<div class="header-row">
			<h1>Release Notes</h1>
			<div class="header-actions">
				<a class="btn ghost" href="contact.php">Contact Dev</a>
				<a class="btn primary" href="index.php">Home</a>
			</div>
		</div>

		<div class="meta">Current API : <?php echo htmlspecialchars($AppConfig['ApiBaseUrl']); ?> · App : <?php echo htmlspecialchars($AppConfig['AppName']); ?></div>

		<div class="releases">
			<div class="release-card">
				<h2>v0.3.0 — New Pages & Small UX Updates</h2>
				<div class="release-meta">2025-10-02</div>
				<ul class="changes">
					<li>Added "Contact Developer" Page That Opens An Email Draft To The Developer.</li>
					<li>Added "Release Notes" Page With Changelog And Metadata.</li>
					<li>Added Quick Access Buttons For Contact & Release On Home And Lobby Pages.</li>
					<li>Minor Styling Improvements For Forms And Buttons.</li>
				</ul>
			</div>

			<div class="release-card">
				<h2>v0.2.0 — Lobby and Matchmaking Improvements</h2>
				<div class="release-meta">2025-09-29</div>
				<ul class="changes">
					<li>Quick Match Now Supports Game Type Selection (Classical/Rapid/Blitz).</li>
					<li>Improved Leaderboard Parsing And UI.</li>
				</ul>
			</div>

		</div>

		<div class="page-footer">
			<div class="small">Want Earlier Releases? Check The Repository Or Ask The Developer.</div>
			<div style="margin-left:auto;">
				<a class="btn ghost" href="lobby.php">← Back to Lobby</a>
			</div>
		</div>
	</div>
</body>
</html>