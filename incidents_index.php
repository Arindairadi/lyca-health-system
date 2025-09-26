<?php
// incidents_index.php — public listing of reported incidents
require __DIR__ . '/db.php';

$stmt = $pdo->prepare("SELECT id, slug, title, category, short_description, thumbnail_url, reported_at, severity, location_text
                       FROM incidents
                       WHERE is_published = 1
                       ORDER BY reported_at DESC
                       LIMIT 200");
$stmt->execute();
$incidents = $stmt->fetchAll();

function esc($s){ return htmlspecialchars($s ?? '', ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8'); }
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Incidents — Public Reports</title>
<meta name="viewport" content="width=device-width,initial-scale=1">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&family=Merriweather:wght@400;700&display=swap" rel="stylesheet">
<style>
/* General */
body {
  font-family: Inter, system-ui, Arial, sans-serif;
  margin: 0;
  background: #f7fafc;
  color: #062a3b;
  line-height: 1.6;
}

/* Shared Header */
.site-header {
  background: linear-gradient(135deg, #0b74de, #0a58ca);
  color: #fff;
  padding: 16px 24px;
  box-shadow: 0 3px 8px rgba(0,0,0,0.15);
}
.site-header .container {
  max-width: 1100px;
  margin: 0 auto;
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
}
.site-title {
  font-family: Merriweather, serif;
  font-size: 1.5rem;
  font-weight: 700;
}
.site-nav a {
  color: #fff;
  text-decoration: none;
  font-weight: 600;
  margin-left: 18px;
  transition: opacity 0.2s ease;
}
.site-nav a:hover { opacity: 0.8; }

/* Main content */
.container {
  max-width: 1100px;
  margin: 20px auto;
  padding: 0 20px;
}
.header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 22px;
  flex-wrap: wrap;
  gap: 14px;
}
.title {
  font-family: Merriweather, serif;
  font-size: 1.7rem;
  font-weight: 700;
  color: #0f172a;
}
.small {
  font-size: 0.95rem;
  color: #475569;
  margin-top: 2px;
}
.report-btn {
  background: linear-gradient(135deg, #089981, #0bbfa8);
  color: #fff;
  padding: 10px 18px;
  border-radius: 10px;
  text-decoration: none;
  font-weight: 600;
  transition: all 0.3s ease;
  box-shadow: 0 4px 12px rgba(8, 153, 129, 0.25);
}
.report-btn:hover {
  background: linear-gradient(135deg, #0bbfa8, #089981);
  transform: translateY(-2px);
}

/* Grid */
.grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(310px, 1fr));
  gap: 20px;
}

/* Card */
.card {
  background: #fff;
  border-radius: 14px;
  overflow: hidden;
  box-shadow: 0 6px 24px rgba(2, 6, 23, 0.08);
  display: flex;
  flex-direction: column;
  transition: transform 0.2s ease, box-shadow 0.2s ease;
}
.card:hover {
  transform: translateY(-4px);
  box-shadow: 0 10px 28px rgba(2, 6, 23, 0.12);
}
.thumb {
  width: 100%;
  height: 180px;
  object-fit: cover;
  background: #e2e8f0;
}
.card-content {
  padding: 16px;
}
.cat {
  display: inline-block;
  background: #eef2ff;
  color: #1e3a8a;
  padding: 6px 12px;
  border-radius: 999px;
  font-weight: 600;
  font-size: 0.85rem;
  margin-bottom: 6px;
}
.incident-title {
  margin: 8px 0 6px;
  font-size: 1.15rem;
  font-weight: 700;
  color: #0f172a;
}
.meta {
  color: #6b7280;
  font-size: 0.9rem;
}
.loc {
  color: #334155;
  font-size: 0.92rem;
  margin-top: 6px;
}
.small-text {
  font-size: 0.94rem;
  color: #475569;
  margin-top: 8px;
}
.sep { height: 10px; }
.details-link {
  display: inline-block;
  margin-top: 4px;
  color: #0b74de;
  font-weight: 600;
  text-decoration: none;
  transition: color 0.2s ease;
}
.details-link:hover { color: #064aa3; }

/* Shared Footer */
.site-footer {
  margin-top: 40px;
  background: #0f172a;
  color: #cbd5e1;
  text-align: center;
  padding: 18px 20px;
  font-size: 0.9rem;
}
.site-footer a {
  color: #38bdf8;
  text-decoration: none;
  margin: 0 8px;
}
.site-footer a:hover { text-decoration: underline; }

/* Responsive */
@media (max-width: 640px) {
  .thumb { height: 150px; }
  .card-content { padding: 12px; }
}
</style>
</head>
<body>

  <!-- Shared Header -->
  <header class="site-header">
    <div class="container">
      <div class="site-title">LYCA System</div>
      <nav class="site-nav">
        <a href="index.php">Home</a>
        <a href="news_index.php">News</a>
        <a href="incidents_index.php">Incidents</a>
        <a href="report_incident.php">Report</a>
      </nav>
    </div>
  </header>

  <!-- Main -->
  <main class="container">
    <div class="header">
      <div>
        <div class="title">Public Incident Reports</div>
        <div class="small">Accidents, outbreaks and other incidents reported by the community (public view)</div>
      </div>
      <div>
        <a class="report-btn" href="report_incident.php">+ Report an incident</a>
      </div>
    </div>

    <div class="grid">
      <?php if (empty($incidents)): ?>
        <div class="card"><em>No incidents reported yet.</em></div>
      <?php else: foreach ($incidents as $it): ?>
        <article class="card">
          <?php if (!empty($it['thumbnail_url'])): ?>
            <img class="thumb" src="<?= esc($it['thumbnail_url']) ?>" alt="<?= esc($it['title']) ?>">
          <?php else: ?>
            <div class="thumb" aria-hidden="true"></div>
          <?php endif; ?>
          <div class="card-content">
            <div class="cat"><?= esc(ucfirst($it['category'])) ?></div>
            <h3 class="incident-title"><?= esc($it['title']) ?></h3>
            <div class="meta">
              <?= esc(date('M j, Y H:i', strtotime($it['reported_at']))) ?>
              <?php if($it['severity']): ?> — Severity <?= intval($it['severity']) ?><?php endif;?>
            </div>
            <?php if($it['location_text']): ?>
              <div class="loc">📍 <?= esc($it['location_text']) ?></div>
            <?php endif; ?>
            <p class="small-text"><?= esc($it['short_description']) ?></p>
            <div class="sep"></div>
            <a class="details-link" href="incident.php?slug=<?= urlencode($it['slug']) ?>">View details →</a>
          </div>
        </article>
      <?php endforeach; endif; ?>
    </div>
  </main>

  <!-- Shared Footer -->
  <footer class="site-footer">
    <div>© <?= date('Y') ?> LYCA System. All rights reserved.</div>
    <div>
      <a href="report_ambulance.php">Ambulance</a> | 
      <a href="ai-diagnosis.php">AI self ai-diagnosis</a> | 
      <a href="blog.php">Blog</a>
    </div>
  </footer>

</body>
</html>
