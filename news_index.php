<?php
// News listing page — modern styled version
require __DIR__ . '/db.php';

$stmt = $pdo->prepare("SELECT id, slug, title, short_description, thumbnail_url, thumbnail_type, published_at
                       FROM news
                       WHERE is_published = 1
                       ORDER BY COALESCE(published_at, created_at) DESC");
$stmt->execute();
$items = $stmt->fetchAll();

function esc($s){ return htmlspecialchars($s ?? '', ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8'); }
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>LYCA — News</title>
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="description" content="LYCA News — latest updates on community health, safety, and alerts.">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&family=Merriweather:wght@400;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
:root{--bg:#f7fbfd;--card:#fff;--accent:#0b74de;--muted:#6b7280;--accent2:#089981;--radius:12px}
*{box-sizing:border-box}
body{margin:0;font-family:Inter,system-ui,-apple-system,Segoe UI,Roboto,Arial;background:linear-gradient(180deg,#f2f9ff 0,#f7fbfd 100%);color:#072b4f}
.container{max-width:1100px;margin:28px auto;padding:20px}
.header{display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:22px}
.brand{display:flex;align-items:center;gap:12px}
.logo{width:50px;height:50px;border-radius:10px;background:linear-gradient(180deg,var(--accent),#0a63c0);display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;font-family:Merriweather,serif}
.title{font-family:Merriweather,serif;font-size:1.35rem}
.tagline{color:var(--muted);font-size:0.95rem}
.nav{display:flex;gap:10px;align-items:center}
.nav a{color:var(--accent);text-decoration:none;font-weight:600;padding:8px 12px;border-radius:10px}
.nav a:hover{background:#eef6ff}
.grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:20px}
.card{background:var(--card);border-radius:var(--radius);padding:16px;box-shadow:0 10px 28px rgba(2,6,23,0.06);display:flex;flex-direction:column}
.thumb{width:100%;height:180px;object-fit:cover;border-radius:8px;background:#eef2f7}
.title2{font-weight:700;margin:12px 0 6px;font-size:1.05rem}
.meta{color:var(--muted);font-size:0.9rem}
.excerpt{color:#334155;font-size:0.95rem;margin-top:6px;flex-grow:1}
.read{display:inline-block;margin-top:12px;color:#fff;background:var(--accent);padding:8px 12px;border-radius:8px;text-decoration:none;font-weight:600}
.read:hover{background:#084a9e}
.footer{margin-top:28px;color:var(--muted);font-size:0.9rem;text-align:center;padding:18px;background:var(--card);border-radius:var(--radius);box-shadow:0 6px 20px rgba(2,6,23,0.04)}
@media (max-width:820px){.header{flex-direction:column;align-items:flex-start}.nav{flex-wrap:wrap}}
</style>
</head>
<body>
  <div class="container">
    <!-- HEADER -->
    <header class="header">
      <div class="brand">
        <div class="logo">LY</div>
        <div>
          <div class="title">LYCA</div>
          <div class="tagline">Local Youth Community Alerts</div>
        </div>
      </div>
      <nav class="nav" aria-label="Main navigation">
        <a href="index.php"><i class="fa-solid fa-house"></i> Home</a>
        <a href="blog_index.php"><i class="fa-regular fa-newspaper"></i> Blog</a>
       
        <a href="incidents_index.php"><i class="fa-solid fa-triangle-exclamation"></i> Incidents</a>
        <a href="ambulance_index.php"><i class="fa-solid fa-ambulance"></i> Ambulance</a>
      </nav>
    </header>

    <!-- MAIN CONTENT -->
    <h1 style="margin-bottom:12px">News</h1>
    <div class="grid">
      <?php if (empty($items)): ?>
        <div class="card"><em>No news yet.</em></div>
      <?php else: foreach ($items as $n): ?>
        <article class="card">
          <?php if (!empty($n['thumbnail_url'])): ?>
            <img class="thumb" src="<?= esc($n['thumbnail_url']) ?>" alt="<?= esc($n['title']) ?>">
          <?php else: ?>
            <div class="thumb" aria-hidden="true"></div>
          <?php endif; ?>
          <div class="title2"><?= esc($n['title']) ?></div>
          <div class="meta"><?= $n['published_at'] ? esc(date('M j, Y', strtotime($n['published_at']))) : '' ?></div>
          <p class="excerpt"><?= esc($n['short_description']) ?></p>
          <a class="read" href="news.php?slug=<?= urlencode($n['slug']) ?>">Read article →</a>
        </article>
      <?php endforeach; endif; ?>
    </div>

    <!-- FOOTER -->
    <footer class="footer">
      <div><strong>LYCA</strong> — Built by Agaba Olivier & Arinda Iradi, Kabale University.</div>
      <div class="meta">© <?= date('Y') ?> LYCA. All rights reserved.</div>
    </footer>
  </div>
</body>
</html>
