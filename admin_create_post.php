<?php
// Simple admin "create post" page with thumbnail upload OR URL.
// NOT intended for public use. For local/testing only.
// Protect with a simple admin key — set a strong key and visit with ?admin_key=THE_KEY
require __DIR__ . '/db.php';

// Set this to a secret value and pass as ?admin_key=SECRET when accessing the page.
$ADMIN_KEY = 'changeme123'; // change this before use!

$provided_key = $_REQUEST['admin_key'] ?? '';
if ($provided_key !== $ADMIN_KEY) {
    http_response_code(403);
    echo "Forbidden. Provide correct admin_key in query (for local testing only).";
    exit;
}

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $slug = trim($_POST['slug'] ?? '');
    $title = trim($_POST['title'] ?? '');
    $short = trim($_POST['short_description'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $is_published = !empty($_POST['is_published']) ? 1 : 0;
    $published_at = !empty($_POST['published_at']) ? $_POST['published_at'] : null;
    $thumbnail_url = trim($_POST['thumbnail_url'] ?? '');

    if ($slug === '' || $title === '' || $content === '') {
        $errors[] = "Slug, title and content are required.";
    } else {
        // handle uploaded file if present
        $thumb_type = 'url';
        if (!empty($_FILES['thumbnail_file']) && $_FILES['thumbnail_file']['error'] === UPLOAD_ERR_OK) {
            $up = $_FILES['thumbnail_file'];
            $ext = pathinfo($up['name'], PATHINFO_EXTENSION);
            $safe = bin2hex(random_bytes(8)) . '.' . preg_replace('/[^a-z0-9_.-]/i','',$ext);
            $uploads_dir = __DIR__ . '/uploads';
            if (!is_dir($uploads_dir)) mkdir($uploads_dir, 0755, true);
            $dest = $uploads_dir . '/' . $safe;
            if (move_uploaded_file($up['tmp_name'], $dest)) {
                // store relative path to be used in <img src="">
                $thumbnail_url = 'uploads/' . $safe;
                $thumb_type = 'upload';
            } else {
                $errors[] = "Failed to move uploaded file.";
            }
        } elseif ($thumbnail_url !== '') {
            $thumb_type = 'url';
        } else {
            $thumbnail_url = null;
            $thumb_type = 'url';
        }

        if (empty($errors)) {
            $ins = $pdo->prepare("INSERT INTO posts (slug, title, short_description, content, thumbnail_url, thumbnail_type, is_published, published_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $ins->execute([$slug, $title, $short, $content, $thumbnail_url, $thumb_type, $is_published, $published_at ?: null]);
            $success = true;
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Create post (admin)</title>
<meta name="viewport" content="width=device-width,initial-scale=1">
<style>
body{font-family:Arial;padding:18px;background:#f6fafb}
.form{max-width:800px;background:white;padding:18px;border-radius:8px;box-shadow:0 10px 30px rgba(2,6,23,0.05)}
.input,textarea{width:100%;padding:10px;border:1px solid #e6eef6;border-radius:8px}
textarea{min-height:200px}
.btn{padding:10px 14px;border-radius:8px;background:#0b74de;color:white;border:0}
.notice{background:#eefdfa;border:1px solid #c9f0e3;padding:10px;border-radius:8px;margin-bottom:10px}
.err{background:#fff5f5;border:1px solid #fed7d7;padding:10px;border-radius:8px;margin-bottom:10px}
</style>
</head>
<body>
  <div class="form">
    <h2>Create post</h2>
    <?php if ($success): ?><div class="notice">Post created successfully.</div><?php endif; ?>
    <?php if ($errors): foreach ($errors as $e): ?><div class="err"><?= htmlspecialchars($e) ?></div><?php endforeach; endif; ?>
    <form method="post" enctype="multipart/form-data" action="?admin_key=<?= urlencode($ADMIN_KEY) ?>">
      <label>Slug (unique)</label><br><input name="slug" class="input" placeholder="slug-for-post"><br><br>
      <label>Title</label><br><input name="title" class="input"><br><br>
      <label>Short description</label><br><input name="short_description" class="input"><br><br>
      <label>Thumbnail URL (or upload below)</label><br><input name="thumbnail_url" class="input" placeholder="https://..."><br><br>
      <label>Or upload thumbnail (jpg/png)</label><br><input name="thumbnail_file" type="file" accept="image/*"><br><br>
      <label>Content (HTML)</label><br><textarea name="content"></textarea><br><br>
      <label>Publish now? <input type="checkbox" name="is_published" checked></label><br><br>
      <label>Published at (optional, e.g. 2025-09-24 15:00:00)</label><br><input name="published_at" class="input"><br><br>
      <button class="btn" type="submit">Create post</button>
    </form>
  </div>
</body>
</html>