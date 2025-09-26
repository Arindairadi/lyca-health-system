<?php
session_start();
require_once __DIR__ . '/config/data_base.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Report Outbreak - Lyca Health</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
<link rel="stylesheet" href="style.css">
<style>
  /* Simple styling for form */
  .report-outbreak { max-width:600px; margin:50px auto; padding:20px; border:1px solid #ccc; border-radius:10px; background:#f9f9f9; }
  .report-outbreak input, .report-outbreak textarea { width:100%; padding:10px; margin:10px 0; border-radius:5px; border:1px solid #ccc; }
  .report-outbreak button { padding:10px 20px; border:none; background:#0077b6; color:#fff; border-radius:5px; cursor:pointer; }
  #image-preview { display:none; max-width:200px; margin-top:10px; }
  #report-message { margin-top:15px; font-weight:bold; }
</style>
</head>
<body>

<header>
  <a href="index.php" class="logo-container" style="display:flex; align-items:center; gap:10px; text-decoration:none;">
    <img src="images/logo.jpg" alt="Lyca Logo" style="height:50px;">
    <span style="font-size:24px; font-weight:bold; color:#0077b6;">LYCA</span>
  </a>
  <nav>
    <a href="index.php"><i class="fas fa-home"></i> Home</a>
    <a href="services.php"><i class="fas fa-stethoscope"></i> Services</a>
    <a href="blog.php"><i class="fas fa-blog"></i> Blog</a>
    <a href="contact.php"><i class="fas fa-envelope"></i> Contact</a>
  </nav>
</header>

<section class="report-outbreak">
  <h2>Report an Outbreak</h2>
  <form id="report-form" method="post" enctype="multipart/form-data">
      <input type="text" name="name" placeholder="Your Name" required>
      <input type="email" name="email" placeholder="Your Email" required>
      <textarea name="description" placeholder="Describe the outbreak" rows="5" required></textarea>
      <input type="file" name="image" id="image" accept="image/*">
      <img id="image-preview" src="#" alt="Image Preview">
      <input type="hidden" name="latitude" id="latitude">
      <input type="hidden" name="longitude" id="longitude">
      <button type="submit"><i class="fas fa-paper-plane"></i> Submit Report</button>
  </form>
  <p id="report-message"></p>
</section>

<script>
const form = document.getElementById('report-form');
const msg = document.getElementById('report-message');
const imageInput = document.getElementById('image');
const imagePreview = document.getElementById('image-preview');

// Image preview
imageInput.addEventListener('change', function() {
    const file = this.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            imagePreview.src = e.target.result;
            imagePreview.style.display = 'block';
        }
        reader.readAsDataURL(file);
    } else {
        imagePreview.src = '#';
        imagePreview.style.display = 'none';
    }
});

// Get user's location
if (navigator.geolocation) {
    navigator.geolocation.getCurrentPosition(position => {
        document.getElementById('latitude').value = position.coords.latitude;
        document.getElementById('longitude').value = position.coords.longitude;
    }, error => {
        console.warn("Could not get location:", error);
        msg.textContent = "Please allow location access to report outbreak.";
    });
} else {
    console.warn("Geolocation not supported");
    msg.textContent = "Geolocation not supported by your browser.";
}

// AJAX form submission
form.addEventListener('submit', function(e) {
    e.preventDefault();
    const lat = document.getElementById('latitude').value;
    const lng = document.getElementById('longitude').value;

    if (!lat || !lng) {
        msg.textContent = "Unable to get your location. Please allow location access.";
        return;
    }

    const formData = new FormData(this);

    fetch('submit-outbreak.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.text())
    .then(data => {
        msg.textContent = data;
        form.reset();
        imagePreview.style.display = 'none';
    })
    .catch(err => {
        msg.textContent = 'Something went wrong. Please try again.';
        console.error(err);
    });
});
</script>

</body>
</html>
