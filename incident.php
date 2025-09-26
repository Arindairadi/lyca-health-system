<?php
// incident.php — view single incident and comments; allows public comments
require __DIR__ . '/db.php';
session_start();

function esc($s){ return htmlspecialchars($s ?? '', ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8'); }
if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16));

// Handle comment POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'comment') {
    $slug = $_POST['slug'] ?? '';
    $name = trim($_POST['name'] ?? '');
    $comment = trim($_POST['comment'] ?? '');
    $is_anonymous = !empty($_POST['anonymous']) ? 1 : 0;
    $token = $_POST['csrf'] ?? '';

    if (!hash_equals($_SESSION['csrf'], $token)) {
        $_SESSION['flash_error'] = "Invalid form submission.";
        header('Location: ' . $_SERVER['REQUEST_URI']);
        exit;
    }
    if ($comment === '') {
        $_SESSION['flash_error'] = "Please enter a comment.";
        header('Location: ' . $_SERVER['REQUEST_URI']);
        exit;
    }

    $stmt = $pdo->prepare("SELECT id FROM incidents WHERE slug = ? LIMIT 1");
    $stmt->execute([$slug]);
    $inc = $stmt->fetch();
    if (!$inc) {
        $_SESSION['flash_error'] = "Incident not found.";
        header('Location: incidents_index.php');
        exit;
    }

    $ip_bin = null;
    if (!empty($_SERVER['REMOTE_ADDR'])) {
        $ip_bin = inet_pton($_SERVER['REMOTE_ADDR']) ?: null;
    }

    $ins = $pdo->prepare("INSERT INTO incident_comments (incident_id, name, is_anonymous, comment, ip) VALUES (?, ?, ?, ?, ?)");
    $ins->execute([$inc['id'], $is_anonymous ? null : ($name?:null), $is_anonymous?1:0, $comment, $ip_bin]);

    $_SESSION['flash_success'] = "Thank you — your comment has been posted.";
    header('Location: incident.php?slug=' . urlencode($slug) . '#comments');
    exit;
}

// Display incident by slug
$slug = $_GET['slug'] ?? '';
if (!$slug) {
    header('Location: incidents_index.php');
    exit;
}
$stmt = $pdo->prepare("SELECT * FROM incidents WHERE slug = ? AND is_published = 1 LIMIT 1");
$stmt->execute([$slug]);
$incident = $stmt->fetch();
if (!$incident) {
    http_response_code(404);
    echo "Incident not found.";
    exit;
}

// fetch comments (approved)
$cstmt = $pdo->prepare("SELECT name, is_anonymous, comment, created_at FROM incident_comments WHERE incident_id = ? AND approved = 1 ORDER BY created_at ASC");
$cstmt->execute([$incident['id']]);
$comments = $cstmt->fetchAll();

$flash_error = $_SESSION['flash_error'] ?? null;
$flash_success = $_SESSION['flash_success'] ?? null;
unset($_SESSION['flash_error'], $_SESSION['flash_success']);
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title><?= esc($incident['title']) ?> — Incident</title>
<meta name="viewport" content="width=device-width,initial-scale=1">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&family=Merriweather:wght@400;700&display=swap" rel="stylesheet">
<style>
body{font-family:Inter,system-ui,Arial;margin:0;background:#f6fafb;color:#072b3a;padding:18px}
.container{max-width:880px;margin:0 auto;background:white;padding:20px;border-radius:10px;box-shadow:0 12px 30px rgba(2,6,23,0.06)}
.header{display:flex;justify-content:space-between;align-items:center}
.title{font-family:Merriweather,serif;font-size:1.6rem;margin:6px 0}
.meta{color:#6b7280;font-size:0.9rem}
.hero{width:100%;max-height:420px;object-fit:cover;border-radius:8px;margin:14px 0}
.content{line-height:1.6;color:#07324a}
.info{background:#f1f5f9;border-radius:8px;padding:10px;margin:12px 0;color:#334155}
.comments{margin-top:20px}
.comment{border-top:1px solid #eef2f7;padding:12px 0}
.comment .who{font-weight:700}
.form-row{display:flex;gap:12px;margin-bottom:8px}
.input, textarea{width:100%;padding:10px;border-radius:8px;border:1px solid #e6eef6}
textarea{min-height:120px;resize:vertical}
.btn{background:#0b74de;color:white;padding:10px 14px;border-radius:8px;border:0;cursor:pointer}
.flash-success{background:#eefdea;border:1px solid #c6f6d5;padding:10px;border-radius:8px;color:#154d1b;margin-bottom:10px}
.flash-error{background:#fff5f5;border:1px solid #fed7d7;padding:10px;border-radius:8px;margin-bottom:10px;color:#9b2c2c}
.note{font-size:0.9rem;color:#475569;margin-top:8px}
@media (max-width:640px){ .form-row{flex-direction:column} }
</style>
</head>
<body>
  <div class="container">
    <div class="header">
      <div>
        <div class="meta"><?= esc($incident['category']) ?> — reported <?= date('M j, Y H:i', strtotime($incident['reported_at'])) ?></div>
        <h1 class="title"><?= esc($incident['title']) ?></h1>
      </div>
      <div><a href="incidents_index.php" style="color:#0b74de;text-decoration:none;font-weight:600">← Back to reports</a></div>
    </div>

    <?php if (!empty($incident['thumbnail_url'])): ?>
      <img class="hero" src="<?= esc($incident['thumbnail_url']) ?>" alt="<?= esc($incident['title']) ?>">
    <?php endif; ?>

    <div class="info">
      <?php if ($incident['location_text']): ?><div><strong>Location:</strong> <?= esc($incident['location_text']) ?></div><?php endif; ?>
      <?php if ($incident['latitude'] && $incident['longitude']): ?><div><strong>Coordinates:</strong> <?= esc($incident['latitude']) ?>, <?= esc($incident['longitude']) ?></div><?php endif; ?>
      <?php if ($incident['severity']): ?><div><strong>Severity:</strong> <?= intval($incident['severity']) ?>/10</div><?php endif; ?>
      <div><strong>Reported by:</strong> <?= $incident['is_anonymous'] ? 'Anonymous' : esc($incident['reported_by'] ?? 'Anonymous') ?></div>
      <?php if ($incident['verified']): ?><div style="color:#0b6626;font-weight:700;margin-top:6px">Verified</div><?php endif; ?>
    </div>

    <div class="content">
      <?= nl2br(esc($incident['description'])) ?>
    </div>

    <?php if (!empty($incident['media_url'])): ?>
      <div style="margin-top:12px">
        <a href="<?= esc($incident['media_url']) ?>" target="_blank" rel="noopener">Open attached media</a>
      </div>
    <?php endif; ?>

    <section id="comments" class="comments">
      <h3>Comments (<?= count($comments) ?>)</h3>

      <?php if ($flash_error): ?><div class="flash-error"><?= esc($flash_error) ?></div><?php endif; ?>
      <?php if ($flash_success): ?><div class="flash-success"><?= esc($flash_success) ?></div><?php endif; ?>

      <?php if (empty($comments)): ?><p class="note">No comments yet.</p><?php else: foreach ($comments as $c): ?>
        <div class="comment">
          <div class="who"><?= $c['is_anonymous'] ? 'Anonymous' : esc($c['name'] ?? 'Anonymous') ?> <span style="color:#6b7280;font-weight:400;font-size:0.9rem"> — <?= date('M j, Y H:i', strtotime($c['created_at'])) ?></span></div>
          <div class="what"><?= nl2br(esc($c['comment'])) ?></div>
        </div>
      <?php endforeach; endif; ?>

      <div style="margin-top:14px">
        <h4>Leave a comment</h4>
        <form method="post" action="incident.php?slug=<?= urlencode($incident['slug']) ?>#comments">
          <input type="hidden" name="action" value="comment">
          <input type="hidden" name="slug" value="<?= esc($incident['slug']) ?>">
          <input type="hidden" name="csrf" value="<?= esc($_SESSION['csrf']) ?>">
          <div class="form-row">
            <input name="name" class="input" placeholder="Your name (optional)">
            <label style="display:inline-flex;align-items:center;gap:8px"><input type="checkbox" name="anonymous" value="1"> Post as anonymous</label>
          </div>
          <div style="margin-bottom:8px">
            <textarea name="comment" class="input" placeholder="Your comment" required></textarea>
          </div>
          <div>
            <button class="btn" type="submit">Post comment</button>
          </div>
        </form>
      </div>
    </section>
  </div>
</body>
</html>