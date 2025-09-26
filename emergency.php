<?php
session_start();
require_once __DIR__ . '/config/data_base.php';

$emergency_success = '';
$emergency_error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $latitude = trim($_POST['latitude'] ?? '');
    $longitude = trim($_POST['longitude'] ?? '');

    if (empty($name) || empty($phone) || empty($description) || empty($latitude) || empty($longitude)) {
        $emergency_error = "All fields including location are required.";
    } else {
        $stmt = $conn->prepare("INSERT INTO emergencies (name, phone, email, description, latitude, longitude, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
        $stmt->bind_param("ssssdd", $name, $phone, $email, $description, $latitude, $longitude);

        if ($stmt->execute()) {
            $emergency_success = "✅ Your emergency request has been received! Redirecting to home page...";
            $_POST = []; // clear form
        } else {
            $emergency_error = "⚠️ Failed to submit request. Try again.";
        }

        $stmt->close();
        $conn->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Emergency Ambulance - Lyca Health</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
<style>
body { font-family: Arial, sans-serif; margin:0; padding:0; background:#f7f9fc; }
header { display:flex; justify-content:space-between; align-items:center; padding:15px 30px; background:#f7f9fc; box-shadow:0 2px 8px rgba(0,0,0,0.1); }
header .logo-container { display:flex; align-items:center; gap:10px; text-decoration:none; }
header .logo-container img { height:50px; }
header nav a { margin-left:20px; color:#0077b6; text-decoration:none; font-weight:500; }
header nav a:hover { color:#005f87; }
.form-section { max-width:600px; margin:50px auto; padding:30px; background:#fff; border-radius:12px; box-shadow:0 4px 15px rgba(0,0,0,0.1); }
.form-section h2 { text-align:center; color:#0077b6; margin-bottom:10px; }
.form-section p { text-align:center; margin-bottom:20px; color:#555; }
.form-section input, .form-section textarea { width:100%; padding:12px 15px; margin-bottom:15px; border:1px solid #ccc; border-radius:8px; font-size:16px; }
.form-section textarea { resize:vertical; min-height:100px; }
.form-section button { width:100%; background:#0077b6; color:#fff; padding:12px; font-size:18px; border:none; border-radius:8px; cursor:pointer; transition:0.3s; }
.form-section button:hover { background:#005f87; }
#location-status { font-weight:bold; margin-top:10px; text-align:center; }
.success-msg { padding:12px; background:#d4edda; color:#155724; border-radius:8px; margin-bottom:15px; text-align:center; }
.error-msg { padding:12px; background:#f8d7da; color:#721c24; border-radius:8px; margin-bottom:15px; text-align:center; }
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

<section class="form-section">
  <h2>🚑 Request Emergency Ambulance</h2>
  <p>Please fill in your details. Your location will be captured automatically.</p>

  <?php if(!empty($emergency_success)): ?>
    <div class="success-msg"><?php echo $emergency_success; ?></div>
    <script>
        setTimeout(()=>{window.location.href="index.php";}, 3000);
    </script>
  <?php endif; ?>

  <?php if(!empty($emergency_error)): ?>
    <div class="error-msg"><?php echo $emergency_error; ?></div>
  <?php endif; ?>

  <form action="" method="POST" id="emergency-form">
    <input type="text" name="name" placeholder="Full Name" required>
    <input type="tel" name="phone" placeholder="Phone Number" required>
    <input type="email" name="email" placeholder="Email (optional)">
    <textarea name="description" placeholder="Describe the emergency..." required></textarea>
    <input type="hidden" name="latitude" id="latitude">
    <input type="hidden" name="longitude" id="longitude">
    <button type="submit"><i class="fas fa-ambulance"></i> Request Ambulance</button>
  </form>
  <p id="location-status"></p>
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
window.onload = function() {
  const status = document.getElementById("location-status");
  if(navigator.geolocation){
    navigator.geolocation.getCurrentPosition(pos=>{
      document.getElementById("latitude").value = pos.coords.latitude;
      document.getElementById("longitude").value = pos.coords.longitude;
      status.textContent = "✅ Location captured.";
      status.style.color = "green";
    },()=>{
      status.textContent = "⚠️ Could not get location. Enable GPS.";
      status.style.color = "red";
    });
  } else {
    status.textContent = "⚠️ Geolocation not supported.";
    status.style.color = "red";
  }
}
</script>
</body>
</html>
