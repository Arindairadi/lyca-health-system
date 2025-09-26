<?php
// Individual blog post + comments + simple comment form (anonymous allowed).
require __DIR__ . '/db.php';
session_start();

function esc($s){ return htmlspecialchars($s ?? '', ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8'); }

// CSRF token (simple)
if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16));

// Handle POST comment submission
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

    // Find post id by slug
    $stmt = $pdo->prepare("SELECT id FROM posts WHERE slug = ? LIMIT 1");
    $stmt->execute([$slug]);
    $post = $stmt->fetch();
    if (!$post) {
        $_SESSION['flash_error'] = "Post not found.";
        header('Location: blog_index.php');
        exit;
    }

    $post_id = $post['id'];
    $store_name = $is_anonymous ? null : ($name ?: null);

    // Save comment
    $ip_bin = null;
    if (!empty($_SERVER['REMOTE_ADDR'])) {
        $ip = $_SERVER['REMOTE_ADDR'];
        $ip_bin = inet_pton($ip) ?: null;
    }

    $ins = $pdo->prepare("INSERT INTO comments (post_id, name, is_anonymous, comment, ip) VALUES (?, ?, ?, ?, ?)");
    $ins->execute([$post_id, $store_name, $is_anonymous ? 1 : 0, $comment, $ip_bin]);

    $_SESSION['flash_success'] = "Thank you — your comment has been posted.";
    header('Location: blog.php?slug=' . urlencode($slug) . '#comments');
    exit;
}

// GET: display post by slug
$slug = $_GET['slug'] ?? '';
if (!$slug) {
    header('Location: blog_index.php');
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM posts WHERE slug = ? AND is_published = 1 LIMIT 1");
$stmt->execute([$slug]);
$post = $stmt->fetch();

if (!$post) {
    http_response_code(404);
    echo "Post not found.";
    exit;
}

// load comments
$cstmt = $pdo->prepare("SELECT id, name, is_anonymous, comment, created_at FROM comments WHERE post_id = ? AND approved = 1 ORDER BY created_at ASC");
$cstmt->execute([$post['id']]);
$comments = $cstmt->fetchAll();

$flash_error = $_SESSION['flash_error'] ?? null;
$flash_success = $_SESSION['flash_success'] ?? null;
unset($_SESSION['flash_error'], $_SESSION['flash_success']);
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title><?= esc($post['title']) ?></title>
<meta name="viewport" content="width=device-width,initial-scale=1">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&family=Merriweather:wght@400;700&display=swap" rel="stylesheet">
<style>
body{font-family:Inter,system-ui,Arial;margin:0;background:#f6fafb;color:#072b3a;padding:18px;display:flex;flex-direction:column;min-height:100vh}
.container{flex:1;max-width:880px;margin:0 auto;background:white;padding:20px;border-radius:10px;box-shadow:0 12px 30px rgba(2,6,23,0.06);animation:fadeInUp 0.8s ease}
.header{display:flex;justify-content:space-between;align-items:center}
.title{font-family:Merriweather,serif;font-size:1.8rem;margin:8px 0;transition:color 0.3s}
.title:hover{color:#0b74de}
.meta{color:#6b7280;font-size:0.9rem}
.hero{width:100%;max-height:420px;object-fit:cover;border-radius:8px;margin:16px 0;animation:fadeIn 1s ease}
.content{line-height:1.7;color:#07324a;animation:fadeIn 1.2s ease}
.comments{margin-top:26px}
.comment{border-top:1px solid #eef2f7;padding:12px 0;animation:fadeIn 0.8s ease}
.comment .who{font-weight:700}
.form-row{display:flex;gap:12px;margin-bottom:8px}
.input, textarea{width:100%;padding:10px;border-radius:8px;border:1px solid #e6eef6;transition:border 0.3s,box-shadow 0.3s}
.input:focus, textarea:focus{border:1px solid #0b74de;box-shadow:0 0 6px rgba(11,116,222,0.2);outline:none}
textarea{min-height:120px;resize:vertical}
.btn{background:#0b74de;color:white;padding:10px 14px;border-radius:8px;border:0;cursor:pointer;transition:background 0.3s,transform 0.2s}
.btn:hover{background:#095ab3;transform:translateY(-2px)}
.note{font-size:0.9rem;color:#475569;margin-top:8px}
.flash-success{background:#eefdea;border:1px solid #c6f6d5;padding:10px;border-radius:8px;color:#154d1b;margin-bottom:10px;animation:fadeIn 0.6s ease}
.flash-error{background:#fff5f5;border:1px solid #fed7d7;padding:10px;border-radius:8px;color:#9b2c2c;margin-bottom:10px;animation:fadeIn 0.6s ease}
footer{background:#062a3b;color:white;text-align:center;padding:16px;margin-top:28px;border-radius:10px 10px 0 0;font-size:0.9rem;animation:fadeInUp 1s ease}
@media (max-width:640px){ .form-row{flex-direction:column} }
@keyframes fadeIn{from{opacity:0}to{opacity:1}}
@keyframes fadeInUp{from{opacity:0;transform:translateY(20px)}to{opacity:1;transform:translateY(0)}}
</style>
</head>
<body>
  <div class="container">
    <div class="header">
      <div>
        <div class="meta"><?= $post['published_at'] ? date('M j, Y', strtotime($post['published_at'])) : '' ?></div>
        <h1 class="title"><?= esc($post['title']) ?></h1>
      </div>
      <div><a href="blog_index.php" style="color:#0b74de;text-decoration:none;font-weight:600">← Back to blog</a></div>
    </div>

    <?php if (!empty($post['thumbnail_url'])): ?>
      <img class="hero" src="<?= esc($post['thumbnail_url']) ?>" alt="<?= esc($post['title']) ?>">
    <?php endif; ?>

    <div class="content">
      <?= $post['content'] ?>
    </div>

    <section id="comments" class="comments">
      <h3>Comments (<?= count($comments) ?>)</h3>

      <?php if ($flash_error): ?>
        <div class="flash-error"><?= esc($flash_error) ?></div>
      <?php endif; ?>
      <?php if ($flash_success): ?>
        <div class="flash-success"><?= esc($flash_success) ?></div>
      <?php endif; ?>

      <?php if (empty($comments)): ?>
        <p class="note">No comments yet — be the first to comment.</p>
      <?php else: foreach ($comments as $c): ?>
        <div class="comment">
          <div class="who">
            <?= $c['is_anonymous'] ? 'Anonymous' : esc($c['name'] ?? 'Anonymous') ?>
            <span style="color:#6b7280;font-weight:400;font-size:0.9rem"> — <?= date('M j, Y H:i', strtotime($c['created_at'])) ?></span>
          </div>
          <div class="what"><?= nl2br(esc($c['comment'])) ?></div>
        </div>
      <?php endforeach; endif; ?>

      <div style="margin-top:18px">
        <h4>Leave a comment</h4>
        <form method="post" action="blog.php?slug=<?= urlencode($post['slug']) ?>#comments">
          <input type="hidden" name="action" value="comment">
          <input type="hidden" name="slug" value="<?= esc($post['slug']) ?>">
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
        <p class="note">By posting you agree to our terms. This site does not require login for comments; choose "Post as anonymous" to hide your name.</p>
      </div>
    </section>
  </div>

  <footer>
    <p>Built by Agaba Olivier &amp; Arinda Iradi — Kabale University</p>
    <p>&copy; <?= date('Y') ?> LYCA. All rights reserved.</p>
  </footer>
</body>
</html>
