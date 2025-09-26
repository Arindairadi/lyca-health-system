<?php
// ambulance_request.php — view single ambulance request and allow status update via poster keyword
require __DIR__ . '/db.php';
session_start();
if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16));
function esc($s){ return htmlspecialchars($s ?? '', ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8'); }

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_status') {
    $token = $_POST['csrf'] ?? '';
    if (!hash_equals($_SESSION['csrf'], $token)) {
        $_SESSION['flash_error'] = "Invalid form submission.";
    } else {
        $slug = $_POST['slug'] ?? '';
        $keyword = trim($_POST['keyword'] ?? '');
        $new_status = $_POST['new_status'] ?? '';
        if ($keyword === '' || $new_status === '') {
            $_SESSION['flash_error'] = "Keyword and new status are required.";
        } else {
            $stmt = $pdo->prepare("SELECT id, secret_key_hash, status FROM ambulance_requests WHERE slug = ? LIMIT 1");
            $stmt->execute([$slug]);
            $row = $stmt->fetch();
            if (!$row) {
                $_SESSION['flash_error'] = "Request not found.";
            } else {
                if (!password_verify($keyword, $row['secret_key_hash'])) {
                    $_SESSION['flash_error'] = "Keyword did not match. Cannot update status.";
                } else {
                    $old = $row['status'];
                    $upd = $pdo->prepare("UPDATE ambulance_requests SET status = ?, updated_at = NOW() WHERE id = ?");
                    $upd->execute([$new_status, $row['id']]);
                    $log = $pdo->prepare("INSERT INTO ambulance_status_logs (request_id, old_status, new_status) VALUES (?, ?, ?)");
                    $log->execute([$row['id'], $old, $new_status]);
                    $_SESSION['flash_success'] = "Status updated from " . esc($old) . " to " . esc($new_status) . ".";
                    header('Location: ambulance_request.php?slug=' . urlencode($slug));
                    exit;
                }
            }
        }
    }
}

// Display request
$slug = $_GET['slug'] ?? '';
if (!$slug) { header('Location: ambulance_index.php'); exit; }

$stmt = $pdo->prepare("SELECT * FROM ambulance_requests WHERE slug = ? AND is_public = 1 LIMIT 1");
$stmt->execute([$slug]);
$req = $stmt->fetch();
if (!$req) { http_response_code(404); echo "Request not found."; exit; }

// fetch logs
$logs = $pdo->prepare("SELECT old_status, new_status, changed_at, changed_by FROM ambulance_status_logs WHERE request_id = ? ORDER BY changed_at ASC");
$logs->execute([$req['id']]);
$logs = $logs->fetchAll();

$flash_error = $_SESSION['flash_error'] ?? null;
$flash_success = $_SESSION['flash_success'] ?? null;
unset($_SESSION['flash_error'], $_SESSION['flash_success']);
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title><?= esc($req['area']) ?> — Ambulance Request | LYCA</title>
<meta name="viewport" content="width=device-width,initial-scale=1">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&family=Merriweather:wght@400;700&display=swap" rel="stylesheet">
<style>
body{font-family:Inter,system-ui,Arial;margin:0;background:#f6fafb;color:#072b3a}
header,footer{background:#0b74de;color:white;padding:15px 20px}
header .nav{display:flex;align-items:center;justify-content:space-between}
header .nav a{color:white;text-decoration:none;margin:0 10px;font-weight:600}
header .brand{font-family:Merriweather,serif;font-size:1.4rem;font-weight:bold}
.container{max-width:880px;margin:20px auto;background:white;padding:20px;border-radius:10px;box-shadow:0 12px 30px rgba(2,6,23,0.06)}
.title{font-family:Merriweather,serif;font-size:1.6rem;margin:6px 0}
.meta{color:#6b7280;font-size:0.9rem}
.info{background:#f1f5f9;border-radius:8px;padding:10px;margin:12px 0;color:#334155}
.form-row{display:flex;gap:12px;margin-bottom:8px}
.input,textarea,select{width:100%;padding:10px;border-radius:8px;border:1px solid #e6eef6}
textarea{min-height:120px;resize:vertical}
.btn{background:#0b74de;color:white;padding:10px 14px;border-radius:8px;border:0;cursor:pointer}
.flash-success{background:#eefdea;border:1px solid #c6f6d5;padding:10px;border-radius:8px;color:#154d1b;margin-bottom:10px}
.flash-error{background:#fff5f5;border:1px solid #fed7d7;padding:10px;border-radius:8px;margin-bottom:10px;color:#9b2c2c}
.log{background:#fbfdff;border-radius:6px;padding:8px;margin-top:10px;border:1px solid #eef6ff}
.note{font-size:0.9rem;color:#475569;margin-top:8px}
footer{text-align:center;font-size:0.85rem}
</style>
</head>
<body>
  <!-- Header -->
  <header>
    <div class="nav">
      <div class="brand">LYCA</div>
      <nav>
        <a href="index.php">Home</a>
        <a href="blog_index.php">Blog</a>
        <a href="news_index.php">News</a>
        <a href="incidents_index.php">Incidents</a>
        <a href="ambulance_index.php">Ambulance</a>
      </nav>
    </div>
    <div style="font-size:0.9rem;margin-top:4px">Lyca Community Aid — Quick response system</div>
  </header>

  <!-- Content -->
  <div class="container">
    <div class="meta">Posted <?= esc(date('M j, Y H:i', strtotime($req['posted_at']))) ?></div>
    <h1 class="title"><?= esc($req['area']) ?> — <?= $req['nearest_hospital'] ? esc($req['nearest_hospital']) : '' ?></h1>
    <div><a href="ambulance_index.php" style="color:#0b74de;text-decoration:none;font-weight:600">← Back to requests</a></div>

    <div class="info">
      <div><strong>Contact:</strong> <?= esc($req['phone']) ?></div>
      <div><strong>Posted by:</strong> <?= $req['is_anonymous'] ? 'Anonymous' : esc($req['requester_name'] ?? 'Anonymous') ?></div>
      <?php if ($req['location_text']): ?><div><strong>Location:</strong> <?= esc($req['location_text']) ?></div><?php endif; ?>
      <?php if ($req['latitude'] && $req['longitude']): ?><div><strong>Coordinates:</strong> <?= esc($req['latitude']) ?>, <?= esc($req['longitude']) ?></div><?php endif; ?>
      <?php if ($req['nearest_hospital']): ?><div><strong>Nearest hospital:</strong> <?= esc($req['nearest_hospital']) ?> (≈ <?= esc($req['distance_km']) ?> km)</div><?php endif; ?>
      <div style="margin-top:8px"><strong>Status:</strong> <em><?= esc(ucfirst($req['status'])) ?></em></div>
    </div>

    <div class="content"><?= nl2br(esc($req['description'])) ?></div>

    <?php if ($flash_error): ?><div class="flash-error"><?= esc($flash_error) ?></div><?php endif; ?>
    <?php if ($flash_success): ?><div class="flash-success"><?= esc($flash_success) ?></div><?php endif; ?>

    <section style="margin-top:18px">
      <h3>Update status (for the original poster)</h3>
      <p class="note">Enter the keyword you used when posting to update the status (e.g., mark 'arrived' or 'resolved').</p>
      <form method="post" action="ambulance_request.php?slug=<?= urlencode($req['slug']) ?>">
        <input type="hidden" name="action" value="update_status">
        <input type="hidden" name="csrf" value="<?= esc($_SESSION['csrf']) ?>">
        <input type="hidden" name="slug" value="<?= esc($req['slug']) ?>">
        <div class="form-row">
          <input name="keyword" class="input" placeholder="Your keyword" required>
          <select name="new_status" class="input" required>
            <option value="">Choose new status</option>
            <option value="enroute">Enroute</option>
            <option value="arrived">Arrived</option>
            <option value="resolved">Resolved</option>
            <option value="cancelled">Cancelled</option>
          </select>
        </div>
        <div>
          <button class="btn" type="submit">Update status</button>
        </div>
      </form>

      <?php if (!empty($logs)): ?>
        <h4 style="margin-top:18px">Status change history</h4>
        <?php foreach ($logs as $l): ?>
          <div class="log">
            <div><strong><?= esc($l['old_status']) ?> → <?= esc($l['new_status']) ?></strong></div>
            <div style="color:#6b7280;font-size:0.9rem"><?= esc(date('M j, Y H:i', strtotime($l['changed_at']))) ?><?= $l['changed_by'] ? ' — ' . esc($l['changed_by']) : '' ?></div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </section>
  </div>

  <!-- Footer -->
  <footer>
    <p>Built by Agaba Olivier &amp; Arinda Iradi, Kabale University</p>
    <p>&copy; <?= date('Y') ?> LYCA. All rights reserved.</p>
  </footer>
</body>
</html>
