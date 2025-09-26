<?php
// report_incident.php — public form to submit a new incident
require __DIR__ . '/db.php';
session_start();

function esc($s){ return htmlspecialchars($s ?? '', ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8'); }
if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16));

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'report') {
    $token = $_POST['csrf'] ?? '';
    if (!hash_equals($_SESSION['csrf'], $token)) {
        $errors[] = 'Invalid form submission.';
    } else {
        $title = trim($_POST['title'] ?? '');
        $category = trim($_POST['category'] ?? 'other');
        $short = trim($_POST['short_description'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $location_text = trim($_POST['location_text'] ?? '');
        $latitude = trim($_POST['latitude'] ?? '');
        $longitude = trim($_POST['longitude'] ?? '');
        $severity = is_numeric($_POST['severity'] ?? null) ? intval($_POST['severity']) : null;
        $reported_by = trim($_POST['reported_by'] ?? '');
        $is_anonymous = !empty($_POST['anonymous']) ? 1 : 0;
        $contact = trim($_POST['contact'] ?? '');
        $media_url = trim($_POST['media_url'] ?? '');
        $thumbnail_url = trim($_POST['thumbnail_url'] ?? '');
        $thumbnail_type = 'url';

        if ($title === '' || $description === '') $errors[] = 'Title and description are required.';

        // handle thumbnail upload if provided
        if (empty($errors) && !empty($_FILES['thumbnail_file']) && $_FILES['thumbnail_file']['error'] === UPLOAD_ERR_OK) {
            $up = $_FILES['thumbnail_file'];
            $ext = pathinfo($up['name'], PATHINFO_EXTENSION);
            $safe = bin2hex(random_bytes(8)) . '.' . preg_replace('/[^a-z0-9_.-]/i','',$ext);
            $uploads_dir = __DIR__ . '/uploads/incidents';
            if (!is_dir($uploads_dir)) mkdir($uploads_dir, 0755, true);
            $dest = $uploads_dir . '/' . $safe;
            if (move_uploaded_file($up['tmp_name'], $dest)) {
                $thumbnail_url = 'uploads/incidents/' . $safe;
                $thumbnail_type = 'upload';
            } else {
                $errors[] = 'Failed to save uploaded thumbnail.';
            }
        }

        if (empty($errors)) {
            // create slug
            $slug_base = preg_replace('/[^a-z0-9\-]/i','-', strtolower(substr($title,0,80)));
            $slug_base = preg_replace('/-+/', '-', trim($slug_base, '-'));
            $slug = $slug_base;
            // ensure unique slug
            $i = 1;
            while (true) {
                $s = $pdo->prepare("SELECT id FROM incidents WHERE slug = ? LIMIT 1");
                $s->execute([$slug]);
                if (!$s->fetch()) break;
                $slug = $slug_base . '-' . $i;
                $i++;
            }

            $ins = $pdo->prepare("INSERT INTO incidents 
                (slug, title, category, short_description, description, location_text, latitude, longitude, severity, reported_by, is_anonymous, contact, media_url, thumbnail_type, thumbnail_url, is_published, verified)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, 0)");
            $lat = $latitude !== '' ? $latitude : null;
            $lon = $longitude !== '' ? $longitude : null;
            $ins->execute([$slug, $title, $category, $short, $description, $location_text, $lat, $lon, $severity, $reported_by ?: null, $is_anonymous ? 1 : 0, $contact ?: null, $media_url ?: null, $thumbnail_type, $thumbnail_url ?: null]);

            $success = true;
            // redirect to created incident
            $newId = $pdo->lastInsertId();
            $stmt = $pdo->prepare("SELECT slug FROM incidents WHERE id = ? LIMIT 1");
            $stmt->execute([$newId]);
            $row = $stmt->fetch();
            if ($row) header('Location: incident.php?slug=' . urlencode($row['slug']));
            exit;
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>LYCA — Report an Incident</title>
<meta name="viewport" content="width=device-width,initial-scale=1">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&family=Merriweather:wght@400;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
:root{--bg:#f7fbfd;--card:#fff;--accent:#0b74de;--muted:#6b7280;--accent2:#089981;--radius:12px}
*{box-sizing:border-box}
body{margin:0;font-family:Inter,system-ui,-apple-system,Segoe UI,Roboto,Arial;background:linear-gradient(180deg,#f2f9ff 0,#f7fbfd 100%);color:#072b4f}
.container{max-width:880px;margin:28px auto;padding:20px;background:var(--card);border-radius:var(--radius);box-shadow:0 12px 30px rgba(2,6,23,0.08);animation:fadeIn 0.8s ease}
@keyframes fadeIn{from{opacity:0;transform:translateY(10px)}to{opacity:1;transform:translateY(0)}}
.header{display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:22px}
.brand{display:flex;align-items:center;gap:12px}
.logo{width:50px;height:50px;border-radius:10px;background:linear-gradient(180deg,var(--accent),#0a63c0);display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;font-family:Merriweather,serif;transition:transform .3s}
.logo:hover{transform:rotate(-8deg) scale(1.05)}
.title{font-family:Merriweather,serif;font-size:1.35rem}
.tagline{color:var(--muted);font-size:0.95rem}
.nav{display:flex;gap:10px;align-items:center}
.nav a{color:var(--accent);text-decoration:none;font-weight:600;padding:8px 12px;border-radius:10px;transition:all .3s}
.nav a:hover{background:#eef6ff;transform:translateY(-2px)}
form label{font-weight:600;display:block;margin:6px 0 4px}
.input, textarea, select{width:100%;padding:12px;border-radius:8px;border:1px solid #d6e2f0;margin-bottom:12px;transition:all .3s}
.input:focus, textarea:focus, select:focus{border-color:var(--accent);box-shadow:0 0 0 3px rgba(11,116,222,0.15);outline:none}
textarea{min-height:140px;resize:vertical}
.btn{background:var(--accent2);color:white;padding:12px 18px;border-radius:8px;border:0;cursor:pointer;font-weight:600;transition:all .3s}
.btn:hover{background:#056f60;transform:translateY(-2px);box-shadow:0 6px 16px rgba(0,0,0,0.1)}
.err{background:#fff5f5;border:1px solid #fed7d7;padding:10px;border-radius:8px;margin-bottom:10px;color:#9b2c2c}
.note{font-size:0.9rem;color:#475569}
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
        <a href="news_index.php"><i class="fa-solid fa-earth-americas"></i> News</a>
        <a href="incidents_index.php"><i class="fa-solid fa-triangle-exclamation"></i> Incidents</a>
        <a href="ambulance_index.php"><i class="fa-solid fa-ambulance"></i> Ambulance</a>
      </nav>
    </header>

    <!-- MAIN CONTENT -->
    <h1 style="margin-bottom:12px">Report an Incident</h1>
    <div class="note" style="margin-bottom:12px">Reports are public and visible to everyone. Provide a location so responders can find the scene.</div>

    <?php if ($errors): foreach ($errors as $e): ?><div class="err"><?= esc($e) ?></div><?php endforeach; endif; ?>

    <form method="post" enctype="multipart/form-data">
      <input type="hidden" name="action" value="report">
      <input type="hidden" name="csrf" value="<?= esc($_SESSION['csrf']) ?>">

      <label>Title</label>
      <input name="title" class="input" required>

      <label>Category</label>
      <select name="category" class="input">
        <option value="accident">Accident</option>
        <option value="epidemic">Epidemic / Outbreak</option>
        <option value="fire">Fire</option>
        <option value="chemical">Chemical / Hazard</option>
        <option value="other">Other</option>
      </select>

      <label>Short description (one-line)</label>
      <input name="short_description" class="input">

      <label>Full description (what happened)</label>
      <textarea name="description" class="input" required></textarea>

      <label>Location (address, landmark)</label>
      <input name="location_text" class="input" placeholder="e.g., Main St & 2nd Ave, near the post office" required>

      <label>Latitude (optional)</label>
      <input name="latitude" class="input" placeholder="e.g., -1.234567">

      <label>Longitude (optional)</label>
      <input name="longitude" class="input" placeholder="e.g., 36.123456">

      <label>Severity (1-10)</label>
      <input name="severity" class="input" type="number" min="1" max="10">

      <label>Your name (optional)</label>
      <input name="reported_by" class="input">

      <label><input type="checkbox" name="anonymous" value="1"> Report as anonymous (hides your name)</label>

      <label>Contact info (optional)</label>
      <input name="contact" class="input" placeholder="phone or email (optional)">

      <label>Media URL (photo/video link) or thumbnail URL</label>
      <input name="media_url" class="input" placeholder="https://...">

      <label>Or upload a thumbnail image (optional)</label>
      <input type="file" name="thumbnail_file" accept="image/*" class="input">

      <div style="margin-top:8px">
        <button class="btn" type="submit">Submit report</button>
      </div>
    </form>

    <p class="note" style="margin-top:12px">By submitting you confirm you are reporting a real event. Do not post private or identifying health data. For emergencies call local emergency services immediately.</p>

    <!-- FOOTER -->
    <footer class="footer">
      <div><strong>LYCA</strong> — Built by Agaba Olivier & Arinda Iradi, Kabale University.</div>
      <div class="meta">© <?= date('Y') ?> LYCA. All rights reserved.</div>
    </footer>
  </div>
</body>
</html>
