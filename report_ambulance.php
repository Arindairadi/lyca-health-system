<?php
// report_ambulance.php — public form to post an ambulance request
// Requires db.php (PDO) in same folder.

require __DIR__ . '/db.php';
session_start();

if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16));
function esc($s){ return htmlspecialchars($s ?? '', ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8'); }

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'report') {
    $token = $_POST['csrf'] ?? '';
    if (!hash_equals($_SESSION['csrf'], $token)) {
        $errors[] = 'Invalid form submission.';
    } else {
        $name = trim($_POST['requester_name'] ?? '');
        $is_anonymous = !empty($_POST['anonymous']) ? 1 : 0;
        $phone = trim($_POST['phone'] ?? '');
        $area = trim($_POST['area'] ?? '');
        $location_text = trim($_POST['location_text'] ?? '');
        $latitude = trim($_POST['latitude'] ?? '');
        $longitude = trim($_POST['longitude'] ?? '');
        $nearest_hospital = trim($_POST['nearest_hospital'] ?? '');
        $distance_km = trim($_POST['distance_km'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $keyword = trim($_POST['keyword'] ?? '');

        if ($phone === '' || $area === '' || $keyword === '') {
            $errors[] = 'Phone, area and keyword are required.';
        }

        // create slug safely
        $slug_base = preg_replace('/[^a-z0-9\-]/i','-', strtolower(substr($area . '-' . ($name ?: 'request'), 0, 80)));
        $slug_base = preg_replace('/-+/', '-', trim($slug_base, '-'));
        $slug = $slug_base;
        $i = 1;
        while (true) {
            $s = $pdo->prepare("SELECT id FROM ambulance_requests WHERE slug = ? LIMIT 1");
            $s->execute([$slug]);
            if (!$s->fetch()) break;
            $slug = $slug_base . '-' . $i;
            $i++;
        }

        if (empty($errors)) {
            // hash keyword using password_hash
            $hash = password_hash($keyword, PASSWORD_DEFAULT);

            $ins = $pdo->prepare("INSERT INTO ambulance_requests
                (slug, requester_name, is_anonymous, phone, area, location_text, latitude, longitude, nearest_hospital, distance_km, description, secret_key_hash, is_public)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)");
            $lat = $latitude !== '' ? $latitude : null;
            $lon = $longitude !== '' ? $longitude : null;
            $dist = $distance_km !== '' ? $distance_km : null;

            $ins->execute([$slug, $name ?: null, $is_anonymous ? 1 : 0, $phone, $area, $location_text ?: null, $lat, $lon, $nearest_hospital ?: null, $dist, $description ?: null, $hash]);

            $success = true;
            $posted_slug = $slug;
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Request an Ambulance - LYCA</title>
<meta name="viewport" content="width=device-width,initial-scale=1">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&family=Merriweather:wght@400;700&display=swap" rel="stylesheet">
<style>
body{font-family:Inter,system-ui,Arial;margin:0;background:#f7fafc;color:#062a3b}
header{background:#089981;color:white;padding:16px;text-align:center}
header h1{margin:0;font-family:Merriweather;font-size:1.6rem}
header p{margin:4px 0 0;font-size:0.95rem}
nav{margin-top:10px}
nav a{color:white;margin:0 10px;text-decoration:none;font-weight:600}
nav a:hover{text-decoration:underline}
.container{max-width:820px;margin:20px auto;background:white;padding:18px;border-radius:10px;box-shadow:0 12px 30px rgba(2,6,23,0.06)}
.input, textarea, select{width:100%;padding:10px;border-radius:8px;border:1px solid #e6eef6;margin-bottom:10px}
textarea{min-height:120px;resize:vertical}
.btn{background:#089981;color:white;padding:10px 14px;border-radius:8px;border:0;cursor:pointer}
.btn:hover{background:#05785f}
.err{background:#fff5f5;border:1px solid #fed7d7;padding:10px;border-radius:8px;margin-bottom:10px;color:#9b2c2c}
.success{background:#eefdfa;border:1px solid #c9f0e3;padding:10px;border-radius:8px;margin-bottom:10px;color:#0b6626}
.note{font-size:0.9rem;color:#475569}
footer{background:#062a3b;color:#dce7f1;text-align:center;padding:16px;margin-top:40px;font-size:0.85rem}
footer a{color:#dce7f1;text-decoration:none;font-weight:600}
footer a:hover{text-decoration:underline}
</style>
</head>
<body>
<header>
  <h1>LYCA</h1>
  <p>Life-saving Youth Community Ambulance Network</p>
  <nav>
    <a href="index.php">Home</a>
    <a href="blog.php">Blog</a>
    <a href="news_index.php">News</a>
    <a href="incidents_index.php">Incidents</a>
    <a href="ambulance_index.php">Ambulance</a>
  </nav>
</header>

<div class="container">
  <h2>Request an Ambulance</h2>
  <p class="note">Requests are public so ambulance owners/operators can see and respond. Keep the keyword safe — it's required to update the status later.</p>

  <?php if ($errors): foreach ($errors as $e): ?>
    <div class="err"><?= esc($e) ?></div>
  <?php endforeach; endif; ?>

  <?php if ($success): ?>
    <div class="success">
      Request posted successfully. Share this link with local ambulance owners:  
      <a href="ambulance_request.php?slug=<?= esc($posted_slug) ?>">ambulance_request.php?slug=<?= esc($posted_slug) ?></a>
      <div style="margin-top:8px"><strong>Important:</strong> Keep your keyword private. Use it to update status when you get the ambulance.</div>
    </div>
  <?php endif; ?>

  <form method="post" action="">
    <input type="hidden" name="action" value="report">
    <input type="hidden" name="csrf" value="<?= esc($_SESSION['csrf']) ?>">

    <label>Your name (optional)</label>
    <input name="requester_name" class="input" placeholder="e.g., John">

    <label><input type="checkbox" name="anonymous" value="1"> Post as anonymous (hides name)</label>

    <label>Phone (required)</label>
    <input name="phone" class="input" required placeholder="+256700000000">

    <label>Area / Neighborhood (required)</label>
    <input name="area" class="input" required placeholder="e.g., Central Market, Zone A">

    <label>Address / location (landmark)</label>
    <input name="location_text" class="input" placeholder="e.g., near post office">

    <label>Latitude (optional)</label>
    <input name="latitude" class="input" placeholder="e.g., -1.292066">

    <label>Longitude (optional)</label>
    <input name="longitude" class="input" placeholder="e.g., 36.821945">

    <label>Nearest hospital (optional)</label>
    <input name="nearest_hospital" class="input" placeholder="e.g., St. Mary's Hospital">

    <label>Approx distance to hospital (km)</label>
    <input name="distance_km" class="input" type="number" step="0.1" min="0" placeholder="e.g., 3.5">

    <label>Describe the situation</label>
    <textarea name="description" class="input" placeholder="Short details that help ambulance owners decide"></textarea>

    <label>Keyword (required)</label>
    <input name="keyword" class="input" required placeholder="your-secret-keyword">

    <div style="margin-top:8px"><button class="btn" type="submit">Post request</button></div>
  </form>
</div>

<footer>
  <p>Built by Agaba Olivier & Arinda Iradi, Kabale University</p>
  <p>&copy; <?= date('Y') ?> LYCA. All rights reserved.</p>
</footer>
</body>
</html>
