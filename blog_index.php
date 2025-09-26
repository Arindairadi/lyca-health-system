<?php
// Blog listing page
require __DIR__ . '/db.php';

$stmt = $pdo->prepare("SELECT id, slug, title, short_description, thumbnail_url, thumbnail_type, published_at
                       FROM posts
                       WHERE is_published = 1
                       ORDER BY COALESCE(published_at, created_at) DESC");
$stmt->execute();
$posts = $stmt->fetchAll();

function esc($s){ return htmlspecialchars($s ?? '', ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8'); }
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Blog - LYCA</title>
<meta name="viewport" content="width=device-width,initial-scale=1">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&family=Merriweather:wght@400;700&display=swap" rel="stylesheet">
<style>
body{font-family:Inter,system-ui,Arial;margin:0;background:#f7fafc;color:#062a3b}
header{background:#089981;color:white;padding:16px;text-align:center;animation:fadeInDown 1s ease}
header h1{margin:0;font-family:Merriweather;font-size:1.6rem}
header p{margin:4px 0 0;font-size:0.95rem}
nav{margin-top:10px}
nav a{color:white;margin:0 10px;text-decoration:none;font-weight:600;transition:color 0.3s}
nav a:hover{text-decoration:underline;color:#d1fae5}
.container{max-width:980px;margin:20px auto;padding:0 12px;animation:fadeIn 1s ease}
.page-head{display:flex;justify-content:space-between;align-items:center;margin-bottom:20px}
.page-head h2{margin:0;font-size:1.4rem}
.grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:20px}
.card{background:white;border-radius:12px;padding:14px;box-shadow:0 6px 20px rgba(2,6,23,0.06);
      transform:translateY(15px);opacity:0;animation:slideUp 0.8s forwards}
.card:nth-child(odd){animation-delay:0.1s}
.card:nth-child(even){animation-delay:0.3s}
.thumb{width:100%;height:160px;object-fit:cover;border-radius:8px;background:#eef2f7;transition:transform 0.4s ease}
.card:hover .thumb{transform:scale(1.05)}
.title{font-weight:700;margin:10px 0 6px;font-size:1.1rem}
.meta{color:#6b7280;font-size:0.9rem}
.excerpt{color:#334155;font-size:0.95rem;margin-top:8px;line-height:1.45}
.read{display:inline-block;margin-top:12px;color:#0b74de;text-decoration:none;font-weight:600;transition:all 0.3s}
.read:hover{color:#084c9e;transform:translateX(3px)}
@media (max-width:640px){ .thumb{height:140px} .card{padding:12px} }
footer{background:#062a3b;color:#dce7f1;text-align:center;padding:16px;margin-top:40px;font-size:0.85rem;animation:fadeInUp 1s ease}
footer a{color:#dce7f1;text-decoration:none;font-weight:600;transition:color 0.3s}
footer a:hover{text-decoration:underline;color:#a7f3d0}
@keyframes fadeIn{from{opacity:0}to{opacity:1}}
@keyframes fadeInDown{from{opacity:0;transform:translateY(-15px)}to{opacity:1;transform:translateY(0)}}
@keyframes fadeInUp{from{opacity:0;transform:translateY(15px)}to{opacity:1;transform:translateY(0)}}
@keyframes slideUp{to{transform:translateY(0);opacity:1}}
</style>
</head>
<body>
<header>
  <h1>LYCA</h1>
  <p>Life-saving Youth Community Ambulance Network</p>
  <nav>
    <a href="index.php">Home</a>
    <a href="blog_index.php">Blog</a>
    <a href="news_index.php">News</a>
    <a href="incidents_index.php">Incidents</a>
    <a href="ambulance_index.php">Ambulance</a>
  </nav>
</header>

<div class="container">
  <div class="page-head">
    <h2>Blog</h2>
    <div class="meta">Latest posts</div>
  </div>

  <div class="grid">
    <?php if (empty($posts)): ?>
      <div class="card"><em>No posts yet.</em></div>
    <?php else: foreach ($posts as $p): ?>
      <article class="card">
        <?php if (!empty($p['thumbnail_url'])): ?>
          <img class="thumb" src="<?= esc($p['thumbnail_url']) ?>" alt="<?= esc($p['title']) ?>">
        <?php else: ?>
          <div class="thumb" aria-hidden="true"></div>
        <?php endif; ?>
        <div class="title"><?= esc($p['title']) ?></div>
        <div class="meta"><?= $p['published_at'] ? esc(date('M j, Y', strtotime($p['published_at']))) : '' ?></div>
        <p class="excerpt"><?= esc($p['short_description']) ?></p>
        <a class="read" href="blog.php?slug=<?= urlencode($p['slug']) ?>">Read article →</a>
      </article>
    <?php endforeach; endif; ?>
  </div>
</div>

<footer>
  <p>Built by Agaba Olivier & Arinda Iradi, Kabale University</p>
  <p>&copy; <?= date('Y') ?> LYCA. All rights reserved.</p>
</footer>
</body>
</html>
