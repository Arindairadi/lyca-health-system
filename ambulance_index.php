<?php
// ambulance_index.php — public listing of ambulance requests
require __DIR__ . '/db.php';

function esc($s) {
    return htmlspecialchars($s ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

// Fetch public ambulance requests
$stmt = $pdo->prepare("
    SELECT id, slug, requester_name, is_anonymous, phone, area, nearest_hospital, distance_km, status, posted_at 
    FROM ambulance_requests 
    WHERE is_public = 1 
    ORDER BY posted_at DESC 
    LIMIT 200
");
$stmt->execute();
$requests = $stmt->fetchAll();
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Ambulance Requests</title>
<meta name="viewport" content="width=device-width,initial-scale=1">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&family=Merriweather:wght@400;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
<style>
body{font-family:Inter,system-ui,Arial;margin:0;background:#f7fafc;color:#062a3b;padding:20px; display:flex; flex-direction:column; min-height:100vh;}
.container{max-width:1000px;margin:0 auto; flex:1;}
.header, footer{text-align:center; padding:20px; background:#333; color:white;}
.header{font-size:24px; font-family:Merriweather,serif;}
.grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(300px,1fr));gap:16px;margin-top:20px;}
.card{background:white;border-radius:10px;padding:14px;box-shadow:0 8px 24px rgba(2,6,23,0.06);}
.meta{color:#6b7280;font-size:0.9rem}
.badge{display:inline-block;padding:6px 10px;border-radius:999px;font-weight:700;color:white;background:#0b74de}
.status-open{background:#089981}
.status-enroute{background:#f59e0b}
.status-arrived{background:#10b981}
.status-resolved{background:#6b7280}
.status-cancelled{background:#ef4444}
.btn-link{color:#0b74de;text-decoration:none;font-weight:600}
.small{font-size:0.92rem;color:#334155}
.button-home{background-color:#4CAF50;color:white;padding:12px 24px;font-size:16px;border:none;border-radius:8px;cursor:pointer;transition:background 0.3s;}
.button-home:hover{background-color:#45a049;}
@media (max-width:640px){ .card{padding:12px} }
</style>
</head>
<body>

<header class="header">Ambulance Requests</header>

<div class="container">

    <!-- Requests Grid -->
    <div class="grid">
        <?php if (empty($requests)): ?>
            <div class="card"><em>No requests at the moment.</em></div>
        <?php else: ?>
            <?php foreach ($requests as $req): ?>
                <article class="card">
                    <div style="display:flex;justify-content:space-between;align-items:center">
                        <div>
                            <div style="font-weight:700"><?= esc($req['area']) ?></div>
                            <div class="meta">
                                <?= $req['nearest_hospital'] ? esc($req['nearest_hospital']) : '' ?>
                                <?php if($req['distance_km']!==''): ?> — <?= esc($req['distance_km']) ?> km<?php endif; ?>
                            </div>
                        </div>
                        <div>
                            <?php
                                $statusClass = match($req['status']) {
                                    'enroute' => 'status-enroute',
                                    'arrived' => 'status-arrived',
                                    'resolved' => 'status-resolved',
                                    'cancelled' => 'status-cancelled',
                                    default => 'status-open'
                                };
                            ?>
                            <span class="badge <?= $statusClass ?>"><?= esc(ucfirst($req['status'])) ?></span>
                        </div>
                    </div>

                    <div style="margin-top:10px" class="small">
                        Posted: <?= esc(date('M j, Y H:i', strtotime($req['posted_at']))) ?>
                    </div>

                    <div style="margin-top:10px">
                        Contact: <?= esc($req['phone']) ?> — Posted by: <?= $req['is_anonymous'] ? 'Anonymous' : esc($req['requester_name'] ?? 'Anonymous') ?>
                    </div>

                    <div style="margin-top:12px">
                        <a class="btn-link" href="ambulance_request.php?slug=<?= urlencode($req['slug']) ?>">View details →</a>
                    </div>
                </article>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Back to Home Button -->
    <div style="text-align: center; margin-top: 30px;">
        <a href="index.php" style="text-decoration: none;">
            <button type="button" class="button-home">
                <i class="fa-solid fa-house"></i> Back to Home
            </button>
        </a>
    </div>

</div>

<footer>&copy; 2025 My Website. All rights reserved.</footer>

</body>
</html>
