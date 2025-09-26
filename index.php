<?php
// index.php — LYCA project homepage
function esc($s){ return htmlspecialchars($s ?? '', ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8'); }
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>LYCA — Community Health & Alerts</title>
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="description" content="LYCA — Community health resources and public reporting. Built by Agaba Olivier & Arinda Iradi, Kabale University.">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&family=Merriweather:wght@400;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />

<style>
:root{
  --bg:#f7f9fb;--card:#fff;--accent:#0ea5a4;--accent2:#2563eb;
  --muted:#6b7280;--danger:#ef4444;--success:#10b981;
  --radius:14px;--shadow:0 6px 20px rgba(0,0,0,0.06);
}
*{box-sizing:border-box;margin:0;padding:0}
body{
  font-family:'Inter',system-ui,sans-serif;
  background:linear-gradient(180deg,var(--bg),#fff 100%);
  color:#0f172a;line-height:1.55;
}
.container{max-width:1100px;margin:28px auto;padding:20px}
.header{
  display:flex;align-items:center;justify-content:space-between;gap:16px;
  padding:18px 24px;background:rgba(255,255,255,0.7);backdrop-filter:blur(6px);
  border-radius:var(--radius);box-shadow:var(--shadow);margin-bottom:24px;
}
.brand{display:flex;align-items:center;gap:14px}
.logo{
  width:54px;height:54px;border-radius:12px;
  background:linear-gradient(135deg,var(--accent),var(--accent2));
  display:flex;align-items:center;justify-content:center;
  font-weight:800;font-family:'Merriweather',serif;color:#fff;font-size:1.1rem;
}
.title{font-family:'Merriweather',serif;font-size:1.4rem;font-weight:700}
.tagline{color:var(--muted);font-size:0.92rem;margin-top:2px}
.nav{display:flex;gap:12px;flex-wrap:wrap}
.nav a{
  color:var(--accent2);font-weight:600;text-decoration:none;
  padding:8px 14px;border-radius:10px;transition:.2s;
}
.nav a:hover{background:rgba(37,99,235,0.06)}
.badge{
  background:var(--accent);color:#fff!important;
  border-radius:999px;padding:8px 14px;font-weight:700
}
.hero{display:grid;grid-template-columns:1fr 360px;gap:20px;align-items:start}
@media(max-width:960px){.hero{grid-template-columns:1fr}}
.card{
  background:var(--card);border-radius:var(--radius);padding:20px;
  box-shadow:var(--shadow)
}
.lead{font-size:1.05rem;color:#07324a;line-height:1.65;margin-bottom:12px}
.meta-row{display:flex;flex-wrap:wrap;gap:10px;align-items:center;margin:14px 0}
.small{font-size:0.9rem;color:var(--muted)}
.link-btn{
  display:flex;align-items:center;gap:10px;
  padding:12px 14px;border-radius:10px;
  background:linear-gradient(180deg,#fff,#f9fbfd);
  border:1px solid #e5eaf0;text-decoration:none;
  color:#0f172a;font-weight:600;transition:.2s
}
.link-btn:hover{border-color:var(--accent2);color:var(--accent2)}
.features{
  display:grid;grid-template-columns:repeat(auto-fit,minmax(230px,1fr));
  gap:14px;margin-top:16px
}
.feature{
  padding:14px;border-radius:12px;
  background:linear-gradient(180deg,#fff,#f9fbfd);
  box-shadow:0 6px 16px rgba(0,0,0,0.04)
}
.feature h4{margin:0 0 6px;font-size:1.05rem;color:#0f172a}
.contact{display:flex;flex-wrap:wrap;gap:12px;margin-top:12px}
.contact a{
  flex:1 1 140px;display:flex;align-items:center;gap:10px;
  padding:12px;border-radius:12px;background:#fff;
  border:1px solid #e5eaf0;text-decoration:none;color:#0f172a;
  font-size:0.95rem;transition:.2s
}
.contact a:hover{border-color:var(--accent)}
.footer{
  margin-top:28px;font-size:0.9rem;color:var(--muted);
  padding:20px;border-radius:var(--radius);background:#fff;box-shadow:var(--shadow)
}
.footer strong{color:#0f172a}
</style>
</head>
<body>
<div class="container">

  <header class="header">
    <div class="brand">
      <div class="logo">LY</div>
      <div>
        <div class="title">LYCA</div>
        <div class="tagline">Local Youth Community Alerts — health, safety and urgent reports</div>
      </div>
    </div>
    <nav class="nav" aria-label="Main navigation">
      <a href="blog_index.php"><i class="fa-regular fa-newspaper"></i> Blog</a>
      <a href="news_index.php"><i class="fa-solid fa-earth-americas"></i> News</a>
      <a href="incidents_index.php"><i class="fa-solid fa-triangle-exclamation"></i> Incidents</a>
      <a href="ambulance_index.php"><i class="fa-solid fa-ambulance"></i> Ambulance</a>
     <a href="ai-diagnosis.php"><i class="fa-solid fa-brain"></i> AI Self Diagnosis</a>

      <a href="report_incident.php" class="badge"><i class="fa-solid fa-bullhorn"></i> Report</a>
    </nav>
  </header>

  <section class="hero">
    <div class="card">
      <div class="lead">
        Welcome to LYCA — a student-driven project by Agaba Olivier and Arinda Iradi (Kabale University). LYCA helps communities share health information, public alerts, and request urgent help quickly.
      </div>

      <div class="meta-row">
        <span class="small">Quick actions:</span>
        <a class="link-btn" href="report_incident.php"><i class="fa-solid fa-bullhorn"></i> Report an incident</a>
        <a class="link-btn" href="report_ambulance.php"><i class="fa-solid fa-ambulance"></i> Request an ambulance</a>
        <a class="link-btn" href="blog_index.php"><i class="fa-regular fa-pen-to-square"></i> Read blog</a>
      </div>

      <div class="features">
        <div class="feature">
          <h4><i class="fa-solid fa-hospital"></i> Public health info</h4>
          <div class="small">Trusted tips, diagnostics guidance and community posts to help with first response and care-seeking decisions.</div>
        </div>
        <div class="feature">
          <h4><i class="fa-solid fa-shield-halved"></i> Community alerts</h4>
          <div class="small">Real-time incident reporting — accidents, outbreaks, hazards — shared publicly to keep people informed.</div>
        </div>
        <div class="feature">
          <h4><i class="fa-solid fa-car-side"></i> Ambulance coordination</h4>
          <div class="small">Community can post ambulance requests with phone, area and hospital so operators can respond.</div>
        </div>
        <div class="feature">
          <h4><i class="fa-solid fa-comments"></i> Public discussion</h4>
          <div class="small">Each post supports comments; anonymous posting available so people can share safely.</div>
        </div>
      </div>
    </div>

    <aside class="card quick-links" aria-label="Quick links and contact">
      <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:12px;flex-wrap:wrap">
        <div>
          <div style="font-weight:700">Get in touch</div>
          <div class="small">Contact the project team</div>
        </div>
        <div style="text-align:right">
          <div class="small">Project by</div>
          <div style="font-weight:700">Agaba Olivier & Arinda Iradi</div>
          <div class="small">Kabale University</div>
        </div>
      </div>

      <div class="contact" role="group" aria-label="Contact methods">
        <a href="https://wa.me/250782094897" target="_blank" rel="noopener noreferrer">
          <i class="fa-brands fa-whatsapp" style="color:#25D366"></i>
          <div><div style="font-weight:700">WhatsApp</div><div class="small">+256750065243
            +250782094897</div></div>
        </a>
        <a href="tel:+250782094897">
          <i class="fa-solid fa-phone" style="color:var(--accent2)"></i>
          <div><div style="font-weight:700">Call</div><div class="small">+250 782 094 897
            0781695725</div></div>
        </a>
        <div>
  <div style="font-weight:700">Email</div>
  <div class="small">
    <a href="mailto:agabaolivier85@gmail.com">agabaolivier85@gmail.com</a>
  </div>
</div>
      </div>

      <div style="margin-top:16px">
        <div style="font-weight:700;margin-bottom:8px">Useful links</div>
        <div style="display:flex;flex-direction:column;gap:8px">
          <a class="link-btn" href="blog_index.php"><i class="fa-regular fa-newspaper"></i> Blog</a>
          <a class="link-btn" href="news_index.php"><i class="fa-solid fa-earth-americas"></i> News</a>
          <a class="link-btn" href="incidents_index.php"><i class="fa-solid fa-triangle-exclamation"></i> Incidents</a>
          <a class="link-btn" href="ambulance_index.php"><i class="fa-solid fa-ambulance"></i> Ambulance</a>
        </div>
      </div>
    </aside>
  </section>

  <footer class="footer">
    <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:14px">
      <div>
        <strong>LYCA</strong> — Built by <strong>Agaba Olivier</strong> & <strong>Arinda Iradi</strong>, students at Kabale University.
        <div class="small">Created for community health and safety awareness. For emergencies always call local emergency services.</div>
      </div>
      <div style="text-align:right">
        <div class="small">Follow-up / contact</div>
        <div style="margin-top:6px"><a href="mailto:agabaolivier85@gmail.com" style="color:var(--accent2)">iradiarinda63@gmail.com</a></div>
      </div>
    </div>
    <div style="margin-top:14px;color:#94a3b8;font-size:0.85rem">© <?= date('Y') ?> LYCA — All rights reserved.</div>
  </footer>

</div>
</body>
</html>
