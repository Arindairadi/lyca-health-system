<?php
session_start();
require_once __DIR__ . '/config/data_base.php';

$report_success = '';
$report_error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $location = trim($_POST['location'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $latitude = trim($_POST['latitude'] ?? '');
    $longitude = trim($_POST['longitude'] ?? '');
    
    $image_path = null;
    $video_path = null;

    // File upload handling
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $image_path = "uploads/traffic_images/" . uniqid() . "." . $ext;
        move_uploaded_file($_FILES['image']['tmp_name'], $image_path);
    }

    if (isset($_FILES['video']) && $_FILES['video']['error'] == 0) {
        $ext = pathinfo($_FILES['video']['name'], PATHINFO_EXTENSION);
        $video_path = "uploads/traffic_videos/" . uniqid() . "." . $ext;
        move_uploaded_file($_FILES['video']['tmp_name'], $video_path);
    }

    if (empty($location) || empty($description) || empty($latitude) || empty($longitude)) {
        $report_error = "All fields including location are required.";
    } else {
        $stmt = $conn->prepare("INSERT INTO traffic_updates (location, description, latitude, longitude, image, video, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
        $stmt->bind_param("ssddss", $location, $description, $latitude, $longitude, $image_path, $video_path);

        if ($stmt->execute()) {
            $report_success = "✅ Your traffic report has been submitted! Thank you.";
            $_POST = [];
        } else {
            $report_error = "⚠️ Failed to submit report. Try again.";
        }

        $stmt->close();
        $conn->close();
    }
}

// Fetch latest traffic updates
$traffic_updates = [];
$result = $conn->query("SELECT * FROM traffic_updates ORDER BY created_at DESC LIMIT 10");
if ($result) {
    $traffic_updates = $result->fetch_all(MYSQLI_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Traffic Updates - Lyca Health</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
<style>
body { font-family: Arial, sans-serif; margin:0; padding:0; background:#f7f9fc; }
header { display:flex; justify-content:space-between; align-items:center; padding:15px 30px; background:#f7f9fc; box-shadow:0 2px 8px rgba(0,0,0,0.1); }
header .logo-container { display:flex; align-items:center; gap:10px; text-decoration:none; }
header .logo-container img { height:50px; }
header nav a { margin-left:20px; color:#0077b6; text-decoration:none; font-weight:500; }
header nav a:hover { color:#005f87; }
.section { max-width:800px; margin:40px auto; padding:30px; background:#fff; border-radius:12px; box-shadow:0 4px 15px rgba(0,0,0,0.1); }
.section h2 { text-align:center; color:#0077b6; margin-bottom:20px; }
input, textarea { width:100%; padding:12px 15px; margin-bottom:15px; border:1px solid #ccc; border-radius:8px; font-size:16px; }
textarea { resize:vertical; min-height:80px; }
button { width:100%; background:#0077b6; color:#fff; padding:12px; font-size:18px; border:none; border-radius:8px; cursor:pointer; transition:0.3s; }
button:hover { background:#005f87; }
.success-msg { padding:12px; background:#d4edda; color:#155724; border-radius:8px; margin-bottom:15px; text-align:center; }
.error-msg { padding:12px; background:#f8d7da; color:#721c24; border-radius:8px; margin-bottom:15px; text-align:center; }
.update-card { padding:15px; border-bottom:1px solid #eee; }
.update-card:last-child { border-bottom:none; }
.update-card h4 { margin:0; color:#0077b6; }
.update-card p { margin:5px 0; color:#555; }
.update-card img { max-width:100%; margin-top:10px; border-radius:8px; }
.update-card video { max-width:100%; margin-top:10px; border-radius:8px; }
footer { text-align:center; padding:20px; background:#0077b6; color:#fff; margin-top:50px; }
footer a { color:#fff; text-decoration:none; margin:0 8px; }
footer a:hover { text-decoration:underline; }
</style>
</head>
<body>

<header>
  <a href="index.php" class="logo-container">
    <img src="images/logo.jpg" alt="Lyca Logo">
    <span>LYCA</span>
  </a>
  <nav>
    <a href="index.php"><i class="fas fa-home"></i> Home</a>
    <a href="services.php"><i class="fas fa-stethoscope"></i> Services</a>
    <a href="blog.php"><i class="fas fa-blog"></i> Blog</a>
    <a href="contact.php"><i class="fas fa-envelope"></i> Contact</a>
  </nav>
</header>

<section class="section">
  <h2>🚦 Report a Traffic Issue</h2>

  <?php if(!empty($report_success)): ?>
    <div class="success-msg"><?php echo $report_success; ?></div>
  <?php endif; ?>
  <?php if(!empty($report_error)): ?>
    <div class="error-msg"><?php echo $report_error; ?></div>
  <?php endif; ?>

  <form action="" method="POST" id="traffic-form" enctype="multipart/form-data">
    <input type="text" name="location" placeholder="Location" required>
    <textarea name="description" placeholder="Describe the traffic issue..." required></textarea>
    <label>Upload an image (optional)</label>
    <input type="file" name="image" accept="image/*">
    <label>Upload a video (optional)</label>
    <input type="file" name="video" accept="video/*">
    <input type="hidden" name="latitude" id="latitude">
    <input type="hidden" name="longitude" id="longitude">
    <button type="submit"><i class="fas fa-road"></i> Submit Traffic Report</button>
  </form>
</section>

<section class="section">
  <h2>🛣️ Latest Traffic Updates</h2>
  <?php if(!empty($traffic_updates)): ?>
    <?php foreach($traffic_updates as $update): ?>
      <div class="update-card">
        <h4><?php echo htmlspecialchars($update['location']); ?></h4>
        <p><?php echo htmlspecialchars($update['description']); ?></p>
        <small><?php echo htmlspecialchars($update['created_at']); ?></small>
        <?php if(!empty($update['image'])): ?>
            <img src="<?php echo htmlspecialchars($update['image']); ?>" alt="Traffic Image">
        <?php endif; ?>
        <?php if(!empty($update['video'])): ?>
            <video controls>
              <source src="<?php echo htmlspecialchars($update['video']); ?>" type="video/mp4">
            </video>
        <?php endif; ?>
      </div>
    <?php endforeach; ?>
  <?php else: ?>
    <p>No traffic updates available at the moment.</p>
  <?php endif; ?>
</section>

<footer>
  <p>&copy; <?php echo date("Y"); ?> Lyca Health. All rights reserved.</p>
  <div>
    <a href="privacy.php">Privacy Policy</a> |
    <a href="terms.php">Terms of Service</a> |
    <a href="contact.php">Contact</a>
  </div>
</footer>

<script>
window.onload = function(){
    if(navigator.geolocation){
        navigator.geolocation.getCurrentPosition(pos=>{
            document.getElementById("latitude").value = pos.coords.latitude;
            document.getElementById("longitude").value = pos.coords.longitude;
        });
    }
}
</script>

</body>
</html>
