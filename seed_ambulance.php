<?php
// seed_ambulance.php — use this to insert sample ambulance requests with generated keyword hashes.
// Usage: php seed_ambulance.php (ensure db.php is configured)

require __DIR__ . '/db.php';

$examples = [
    [
        'requester_name' => 'Agnes',
        'is_anonymous' => 0,
        'phone' => '+256700111222',
        'area' => 'Central Market',
        'location_text' => 'Stall 12 near the fruit vendors',
        'latitude' => 0.3136,
        'longitude' => 32.5811,
        'nearest_hospital' => "St. Mary's Clinic",
        'distance_km' => 1.8,
        'description' => 'Unconscious adult, breathing but unresponsive.',
        'keyword' => 'saveAgnes1'
    ],
    [
        'requester_name' => null,
        'is_anonymous' => 1,
        'phone' => '+256700333444',
        'area' => 'North Park',
        'location_text' => 'Near the playground behind the mall',
        'latitude' => 0.3250,
        'longitude' => 32.5670,
        'nearest_hospital' => 'North District Hospital',
        'distance_km' => 4.2,
        'description' => 'Severe bleeding after fall from height.',
        'keyword' => 'parkHelp'
    ],
    [
        'requester_name' => 'Moses',
        'is_anonymous' => 0,
        'phone' => '+256700555666',
        'area' => 'Riverside',
        'location_text' => 'Outside 12 Maple Rd, near school gate',
        'latitude' => 0.2987,
        'longitude' => 32.5900,
        'nearest_hospital' => 'Riverside Clinic',
        'distance_km' => 2.1,
        'description' => 'Elderly person with chest pain and difficulty breathing.',
        'keyword' => 'moses911'
    ],
    [
        'requester_name' => 'Linda',
        'is_anonymous' => 0,
        'phone' => '+256700777888',
        'area' => 'West District',
        'location_text' => 'Corner of Oak St and 5th',
        'latitude' => 0.3050,
        'longitude' => 32.5600,
        'nearest_hospital' => 'West General Hospital',
        'distance_km' => 5.6,
        'description' => 'Multiple injured after road traffic collision.',
        'keyword' => 'westHelp123'
    ],
    [
        'requester_name' => null,
        'is_anonymous' => 1,
        'phone' => '+256700999000',
        'area' => 'Hilltop',
        'location_text' => 'By the water tower',
        'latitude' => 0.2900,
        'longitude' => 32.6000,
        'nearest_hospital' => 'Hilltop Health Center',
        'distance_km' => 3.3,
        'description' => 'Person collapsed, possible seizure.',
        'keyword' => 'hilltopaid'
    ],
];

$insert = $pdo->prepare("INSERT INTO ambulance_requests
 (slug, requester_name, is_anonymous, phone, area, location_text, latitude, longitude, nearest_hospital, distance_km, description, secret_key_hash, is_public)
 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)");

foreach ($examples as $ex) {
    // slug base
    $slug_base = preg_replace('/[^a-z0-9\-]/i','-', strtolower(substr($ex['area'] . '-' . ($ex['requester_name'] ?? 'req'), 0, 80)));
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
    $hash = password_hash($ex['keyword'], PASSWORD_DEFAULT);
    $insert->execute([$slug, $ex['requester_name'], $ex['is_anonymous'], $ex['phone'], $ex['area'], $ex['location_text'], $ex['latitude'], $ex['longitude'], $ex['nearest_hospital'], $ex['distance_km'], $ex['description'], $hash]);
    echo "Inserted: $slug (keyword: " . $ex['keyword'] . ")\n";
}