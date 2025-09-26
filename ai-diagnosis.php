<?php
session_start();
/*
  ai-diagnosis.php — Gemini (generateContent) UI + server proxy + smart local fallback
  - Debug output removed (no debug_attempts or raw provider debug returned or shown).
  - Gemini-only generation using generateContent endpoint with X-goog-api-key header.
  - Correct request shape: generationConfig contains temperature/maxOutputTokens/etc.
  - Session-based key storage (save/clear).
  - Rich patient form (age, sex, duration, severity, onset, travel, vaccines, meds, allergies, pregnancy).
  - Responsive, polished UI with Inter + Merriweather fonts.
  - Client-side formatter renders headings, lists and paragraphs cleanly (no asterisks).
  - Robust error handling; server returns only user-friendly errors (no provider debug).
  - Intended for local/XAMPP use only. Do NOT commit real API keys.
*/

/* ---------- Helpers ---------- */
function json_exit($data, $status = 200) {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data);
    exit;
}
function safe_json_decode($s) {
    $r = json_decode($s, true);
    return is_array($r) ? $r : null;
}

/* ---------- AJAX handlers ---------- */
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$contentType = $_SERVER['CONTENT_TYPE'] ?? '';

if ($method === 'POST' && (strpos($contentType, 'application/json') !== false || isset($_POST['action']))) {
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        parse_str($raw, $data);
        if (!is_array($data)) $data = $_POST;
    }
    $action = $data['action'] ?? '';

    // Diagnose
    if ($action === 'diagnose') {
        $age = trim($data['age'] ?? '');
        $sex = trim($data['sex'] ?? '');
        $symptoms = trim($data['symptoms'] ?? '');
        $duration = trim($data['duration'] ?? '');
        $severity = trim($data['severity'] ?? '');
        $onset = trim($data['onset'] ?? '');
        $travel = trim($data['travel'] ?? '');
        $vaccines = trim($data['vaccines'] ?? '');
        $meds = trim($data['meds'] ?? '');
        $allergies = trim($data['allergies'] ?? '');
        $pregnant = !empty($data['pregnant']);
        $additional = trim($data['additional'] ?? '');

        if ($symptoms === '') json_exit(['error'=>'Please provide symptoms.'],400);

        $apiKey = "AIzaSyAkOQOAU5ShTETg_7dEZDZN1L5dOhpchh0";

        // Build patient summary & prompt
        $patientSummary = [
            "Age: " . ($age ?: 'unspecified'),
            "Sex: " . ($sex ?: 'unspecified'),
            "Duration: " . ($duration ?: 'unspecified'),
            "Severity (1-10): " . ($severity ?: 'unspecified'),
            "Onset: " . ($onset ?: 'unspecified'),
            "Recent travel: " . ($travel ?: 'none'),
            "Vaccinations: " . ($vaccines ?: 'unspecified'),
            "Pregnant: " . ($pregnant ? 'yes' : 'no'),
            "Current meds: " . ($meds ?: 'none'),
            "Allergies: " . ($allergies ?: 'none'),
            "Other: " . ($additional ?: 'none'),
            "Symptoms: " . $symptoms
        ];
        $patientBlock = implode("\n- ", $patientSummary);

        $prompt = <<<PROMPT
You are a cautious, responsible, and empathetic medical assistant. Given the patient details below, produce a concise well-structured diagnostic-style response using the headings: Summary, Possible conditions (prioritized), Recommended investigations (informational), Safe next steps (non-prescriptive), Urgency guidance, Note. Provide 3-5 possible conditions with a one-line rationale and a confidence level (low/medium/high). If mentioning common OTC medicines include typical adult doses only when safe. End with: "This is not medical advice. Consult a qualified healthcare professional."
Patient summary:
- {$patientBlock}
Respond in short sections with clear headings. Be concise and avoid procedural instructions.
PROMPT;

        // If API key present -> call Gemini generateContent (X-goog-api-key header)
        if ($apiKey) {
            $endpoint = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent";
            $payload = [
                "contents" => [
                    [
                        "parts" => [
                            ["text" => $prompt]
                        ]
                    ]
                ],
                "generationConfig" => [
                    "temperature" => 0.2,
                    "topK" => 40,
                    "topP" => 0.95,
                    "maxOutputTokens" => 900
                ]
            ];
            $body = json_encode($payload);

            $ch = curl_init($endpoint);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/json',
                    'X-goog-api-key: ' . $apiKey
                ],
                CURLOPT_POSTFIELDS => $body,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_CONNECTTIMEOUT => 10,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_SSL_VERIFYHOST => 2,
            ]);
            $resp = curl_exec($ch);
            $errno = curl_errno($ch);
            $err = curl_error($ch);
            $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($resp === false || $errno !== 0) {
                json_exit(['error' => 'Network error contacting Gemini. Please try again later.'], 502);
            }

            $parsed = safe_json_decode($resp);
            if (!is_array($parsed)) {
                json_exit(['error' => 'Could not parse provider response. Try again later.'], 502);
            }

            // Extract readable text from common shapes: candidates -> content -> parts[].text
            $extracted = null;
            if (!empty($parsed['candidates']) && is_array($parsed['candidates'])) {
                $cand = $parsed['candidates'][0];
                if (!empty($cand['content']['parts']) && is_array($cand['content']['parts'])) {
                    $parts = array_map(function($p){ return $p['text'] ?? ''; }, $cand['content']['parts']);
                    $txt = trim(implode("\n", array_filter($parts)));
                    if ($txt !== '') $extracted = $txt;
                } elseif (!empty($cand['content']['text'])) {
                    $extracted = (string)$cand['content']['text'];
                } elseif (!empty($cand['text'])) {
                    $extracted = (string)$cand['text'];
                }
            }
            if ($extracted !== null) {
                json_exit(['ok'=>true,'ai_response'=>$extracted]);
            } else {
                json_exit(['error' => 'Provider returned no text output.'], 502);
            }
        }

        // No key saved or provider unreachable => local smart fallback
        $local = generate_smarter_fallback($age,$sex,$symptoms,$duration,$severity,$onset,$pregnant,$additional);
        json_exit(['ok'=>true,'ai_response'=>$local]);
    }

    json_exit(['error'=>'Unknown action.'],400);
}

/* ---------- Local smart fallback (same logic) ---------- */
function generate_smarter_fallback($age,$sex,$symptoms,$duration,$severity,$onset,$pregnant,$additional) {
    $duration_days = parse_duration_to_days($duration);
    $severity_n = is_numeric($severity) ? intval($severity) : null;
    $s = strtolower($symptoms . ' ' . $additional);

    $lines = [];
    $lines[] = "Summary:";
    $lines[] = "Age: " . ($age ?: 'unspecified') . " | Sex: " . ($sex ?: 'unspecified') . " | Duration: " . ($duration ?: 'unspecified') . " | Severity: " . ($severity ?: 'unspecified');
    $lines[] = "";

    $urgent = false;
    if ($severity_n !== null && $severity_n >= 8) $urgent = true;
    if (preg_match('/\b(chest pain|severe shortness of breath|loss of consciousness|seizure)\b/',$s)) $urgent = true;
    $chronic_flag = ($duration_days !== null && $duration_days >= 30);

    $conditions = [];

    if ($chronic_flag && preg_match('/\b(fever|night sweats|weight loss)\b/',$s)) {
        $conditions[] = ["Fever of unknown origin / chronic infection (e.g., TB, occult abscess)", "medium-high", "Prolonged fever suggests chronic infectious/inflammatory/neoplastic etiologies."];
        $conditions[] = ["Autoimmune / inflammatory disease", "medium", "Systemic inflammatory disorders can present with prolonged constitutional symptoms."];
        $conditions[] = ["Malignancy (e.g., lymphoma)", "low-medium", "Consider when other causes are excluded or with weight loss, night sweats."];
    } else {
        if (preg_match('/\b(fever|temperature|chills)\b/',$s)) {
            $conf = "medium";
            if (preg_match('/\b(cough|sputum|wheeze|shortness of breath)\b/',$s)) $conf = "high";
            $conditions[] = ["Febrile illness (viral or bacterial)", $conf, "Fever commonly reflects infection; focal features suggest source."];
        }
        if (preg_match('/\b(cough|sore throat|wheeze|shortness of breath)\b/',$s)) {
            $conditions[] = ["Upper/lower respiratory infection", "medium", "Respiratory symptoms are often infectious."];
        }
        if (preg_match('/\b(headache|neck stiffness|confusion)\b/',$s)) {
            $conditions[] = ["Neurologic causes to consider (meningitis/encephalitis)", "low-medium", "Severe headache, neck stiffness or confusion require urgent review."];
        }
    }

    if (empty($conditions)) {
        $conditions[] = ["Common viral or self-limited illness", "low", "Many mild illnesses resolve with rest and fluids; monitor for red flags."];
    }

    $lines[] = "Possible conditions (prioritized):";
    $i = 0;
    foreach ($conditions as $c) {
        $i++;
        $lines[] = "{$i}. {$c[0]} — {$c[1]} confidence. Rationale: {$c[2]}";
        if ($i >= 6) break;
    }
    $lines[] = "";

    $lines[] = "Recommended investigations (informational):";
    $inv = [];
    $inv[] = "CBC with differential, CRP/ESR, basic metabolic panel (electrolytes, renal function)";
    if (preg_match('/\b(fever|chills|night sweats|weight loss)\b/',$s) || $chronic_flag) {
        $inv[] = "Blood cultures if febrile with systemic features; chest X-ray; urine analysis/culture as indicated";
    }
    if ($chronic_flag) {
        $inv[] = "Consider TB testing (IGRA), HIV testing where appropriate, autoimmune serologies, and targeted imaging (CT) guided by exam";
    }
    if (preg_match('/\b(cough|wheeze|sputum|shortness of breath)\b/',$s)) {
        $inv[] = "Chest X-ray; consider respiratory viral panels or sputum testing based on context";
    }
    if (preg_match('/\b(neck stiffness|confusion|focal weakness|seizure)\b/',$s)) {
        $inv[] = "Urgent clinician assessment; neuroimaging or lumbar puncture may be needed as directed by clinician";
    }
    foreach ($inv as $it) $lines[] = "- " . $it;
    $lines[] = "";

    $lines[] = "Safe next steps (non-prescriptive):";
    $lines[] = "- Monitor for red flags: severe/worsening shortness of breath, chest pain, fainting, severe dehydration, persistent high fever (>39°C/102°F), persistent vomiting, confusion, or seizure. If any occur, seek emergency care immediately.";
    if (!$chronic_flag) {
        $lines[] = "- For mild fever or pain (if no contraindication): paracetamol (acetaminophen) 500–1000 mg every 4–6 hours as needed (do not exceed ~3–4 g/day depending on product); ibuprofen 200–400 mg every 4–6 hours (max ~1200 mg/day OTC). Confirm with clinician/pharmacist if on other medicines.";
    } else {
        $lines[] = "- For prolonged/chronic symptoms: avoid repeated self-medication without clinician review; prioritize diagnostic evaluation.";
    }
    $lines[] = "- Maintain hydration, rest, and avoid strenuous activity while unwell.";
    $lines[] = "";

    if ($urgent || ($chronic_flag && $severity_n !== null && $severity_n >= 7)) {
        $lines[] = "Urgency guidance:";
        $lines[] = "- Given high severity or alarming features, seek urgent in-person clinical assessment (same-day or emergency department).";
    } elseif ($chronic_flag) {
        $lines[] = "Urgency guidance:";
        $lines[] = "- Symptoms are prolonged. Arrange timely (within days) primary care or specialty review to investigate chronic causes.";
    } else {
        $lines[] = "Urgency guidance:";
        $lines[] = "- If no red flags and symptoms are mild-moderate: monitor closely and seek primary care review within 48–72 hours if not improving or sooner if worsening.";
    }

    $lines[] = "";
    $lines[] = "Note: This is not medical advice. Consult a qualified healthcare professional for personalized evaluation.";

    return implode("\n", $lines);
}

function parse_duration_to_days($duration) {
    if (!$duration) return null;
    $d = strtolower(trim($duration));
    if (preg_match('/(\d+(?:\.\d+)?)\s*min/', $d, $m)) return max(1, intval(ceil(floatval($m[1]) / 60 / 24)));
    if (preg_match('/(\d+(?:\.\d+)?)\s*hr|hours|h\b/', $d, $m)) return max(1, intval(ceil(floatval($m[1]) / 24)));
    if (preg_match('/(\d+(?:\.\d+)?)\s*day/', $d, $m)) return intval(round(floatval($m[1])));
    if (preg_match('/(\d+(?:\.\d+)?)\s*week/', $d, $m)) return intval(round(floatval($m[1]) * 7));
    if (preg_match('/(\d+(?:\.\d+)?)\s*month/', $d, $m)) return intval(round(floatval($m[1]) * 30));
    if (preg_match('/(\d+)\s*$/', $d, $m)) return intval($m[1]);
    return null;
}

/* ---------- Render HTML UI ---------- */

?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>AI Medical Diagnosis Assistant</title>
<meta name="viewport" content="width=device-width,initial-scale=1">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<style>
:root {
  --primary: #2563eb;
  --primary-light: #3b82f6;
  --primary-dark: #1d4ed8;
  --secondary: #10b981;
  --secondary-light: #34d399;
  --secondary-dark: #059669;
  --accent: #f59e0b;
  --danger: #ef4444;
  --danger-light: #f87171;
  --warning: #f59e0b;
  --success: #10b981;
  --info: #3b82f6;
  
  --bg-primary: #ffffff;
  --bg-secondary: #f8fafc;
  --bg-tertiary: #f1f5f9;
  --bg-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  --bg-medical: linear-gradient(135deg, #667eea 0%, #764ba2 50%, #f093fb 100%);
  
  --text-primary: #1e293b;
  --text-secondary: #475569;
  --text-muted: #64748b;
  --text-light: #94a3b8;
  
  --border: #e2e8f0;
  --border-light: #f1f5f9;
  --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
  --shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1);
  --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1);
  --shadow-xl: 0 25px 50px -12px rgb(0 0 0 / 0.25);
  
  --radius: 12px;
  --radius-lg: 16px;
  --radius-xl: 20px;
}

* {
  box-sizing: border-box;
  margin: 0;
  padding: 0;
}

body {
  font-family: 'Inter', system-ui, -apple-system, sans-serif;
  background: var(--bg-medical);
  color: var(--text-primary);
  line-height: 1.6;
  min-height: 100vh;
  animation: fadeIn 0.8s ease-out;
}

@keyframes fadeIn {
  from { opacity: 0; transform: translateY(20px); }
  to { opacity: 1; transform: translateY(0); }
}

@keyframes slideInLeft {
  from { opacity: 0; transform: translateX(-30px); }
  to { opacity: 1; transform: translateX(0); }
}

@keyframes slideInRight {
  from { opacity: 0; transform: translateX(30px); }
  to { opacity: 1; transform: translateX(0); }
}

@keyframes pulse {
  0%, 100% { opacity: 1; }
  50% { opacity: 0.7; }
}

@keyframes bounce {
  0%, 20%, 53%, 80%, 100% { transform: translate3d(0,0,0); }
  40%, 43% { transform: translate3d(0,-8px,0); }
  70% { transform: translate3d(0,-4px,0); }
  90% { transform: translate3d(0,-2px,0); }
}

@keyframes shimmer {
  0% { transform: translateX(-100%); }
  100% { transform: translateX(100%); }
}

.container {
  max-width: 1400px;
  margin: 0 auto;
  padding: 2rem;
  min-height: 100vh;
}

.header {
  text-align: center;
  margin-bottom: 3rem;
  animation: slideInLeft 0.8s ease-out;
}

.header h1 {
  font-family: 'Poppins', sans-serif;
  font-size: 3rem;
  font-weight: 700;
  background: linear-gradient(135deg, #667eea, #764ba2, #f093fb);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
  margin-bottom: 0.5rem;
  text-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.header .subtitle {
  font-size: 1.2rem;
  color: var(--text-secondary);
  opacity: 0.9;
}

.medical-icon {
  display: inline-block;
  width: 60px;
  height: 60px;
  background: linear-gradient(135deg, var(--secondary), var(--secondary-light));
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-size: 1.5rem;
  margin: 0 auto 1rem;
  box-shadow: var(--shadow-lg);
  animation: bounce 2s infinite;
}

.grid {
  display: grid;
  grid-template-columns: 450px 1fr;
  gap: 2rem;
  align-items: start;
}

.panel {
  background: var(--bg-primary);
  border-radius: var(--radius-xl);
  padding: 2rem;
  box-shadow: var(--shadow-xl);
  border: 1px solid var(--border-light);
  backdrop-filter: blur(10px);
  position: relative;
  overflow: hidden;
  transition: all 0.3s ease;
}

.panel::before {
  content: '';
  position: absolute;
  top: 0;
  left: -100%;
  width: 100%;
  height: 100%;
  background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
  transition: left 0.5s;
}

.panel:hover::before {
  left: 100%;
}

.panel:hover {
  transform: translateY(-2px);
  box-shadow: 0 32px 64px -12px rgba(0,0,0,0.2);
}

.left-panel {
  animation: slideInLeft 0.8s ease-out;
}

.right-panel {
  animation: slideInRight 0.8s ease-out;
}

.section-header {
  display: flex;
  align-items: center;
  gap: 1rem;
  margin-bottom: 1.5rem;
  padding-bottom: 1rem;
  border-bottom: 2px solid var(--border-light);
}

.section-icon {
  width: 48px;
  height: 48px;
  background: linear-gradient(135deg, var(--primary), var(--primary-light));
  border-radius: var(--radius);
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-size: 1.2rem;
  box-shadow: var(--shadow);
}

.section-title {
  font-family: 'Poppins', sans-serif;
  font-size: 1.3rem;
  font-weight: 600;
  color: var(--text-primary);
}

.section-subtitle {
  color: var(--text-muted);
  font-size: 0.9rem;
  margin-top: 0.2rem;
}

.form-group {
  margin-bottom: 1.5rem;
}

.form-label {
  display: block;
  font-weight: 500;
  color: var(--text-primary);
  margin-bottom: 0.5rem;
  font-size: 0.95rem;
}

.form-input, .form-select, .form-textarea {
  width: 100%;
  padding: 0.75rem 1rem;
  border: 2px solid var(--border);
  border-radius: var(--radius);
  background: var(--bg-primary);
  color: var(--text-primary);
  font-size: 1rem;
  transition: all 0.3s ease;
  position: relative;
}

.form-input:focus, .form-select:focus, .form-textarea:focus {
  outline: none;
  border-color: var(--primary);
  box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
  transform: translateY(-1px);
}

.form-textarea {
  min-height: 120px;
  resize: vertical;
  font-family: inherit;
}

.form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1rem;
}

.checkbox-group {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin-top: 1rem;
}

.checkbox {
  width: 20px;
  height: 20px;
  border: 2px solid var(--border);
  border-radius: 4px;
  position: relative;
  cursor: pointer;
  transition: all 0.3s ease;
}

.checkbox:checked {
  background: var(--primary);
  border-color: var(--primary);
}

.checkbox:checked::after {
  content: '✓';
  position: absolute;
  color: white;
  font-size: 12px;
  font-weight: bold;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
}

.btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  padding: 0.75rem 1.5rem;
  border: none;
  border-radius: var(--radius);
  font-weight: 600;
  font-size: 1rem;
  cursor: pointer;
  transition: all 0.3s ease;
  position: relative;
  overflow: hidden;
}

.btn-primary {
  background: linear-gradient(135deg, var(--primary), var(--primary-light));
  color: white;
  box-shadow: var(--shadow);
}

.btn-primary:hover {
  transform: translateY(-2px);
  box-shadow: var(--shadow-lg);
}

.btn-primary:active {
  transform: translateY(0);
}

.btn-secondary {
  background: var(--bg-tertiary);
  color: var(--text-primary);
  border: 1px solid var(--border);
}

.btn-secondary:hover {
  background: var(--border);
  transform: translateY(-1px);
}

.btn:disabled {
  opacity: 0.7;
  cursor: not-allowed;
  transform: none !important;
}

.btn-group {
  display: flex;
  gap: 0.75rem;
  margin-top: 1.5rem;
  flex-wrap: wrap;
}

.status-indicator {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.5rem 1rem;
  border-radius: var(--radius);
  font-size: 0.9rem;
  font-weight: 500;
  margin-top: 1rem;
}

.status-success {
  background: rgba(16, 185, 129, 0.1);
  color: var(--success);
  border: 1px solid rgba(16, 185, 129, 0.2);
}

.status-warning {
  background: rgba(245, 158, 11, 0.1);
  color: var(--warning);
  border: 1px solid rgba(245, 158, 11, 0.2);
}

.pulse-dot {
  width: 8px;
  height: 8px;
  background: currentColor;
  border-radius: 50%;
  animation: pulse 2s infinite;
}

.result-area {
  min-height: 400px;
  position: relative;
}

.result-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 2rem;
  gap: 1rem;
}

.result-title {
  font-family: 'Poppins', sans-serif;
  font-size: 1.4rem;
  font-weight: 600;
  color: var(--text-primary);
}

.result-subtitle {
  color: var(--text-muted);
  font-size: 0.95rem;
  margin-top: 0.25rem;
}

.tech-badges {
  display: flex;
  gap: 0.5rem;
  flex-wrap: wrap;
}

.tech-badge {
  padding: 0.4rem 0.8rem;
  border-radius: 20px;
  font-size: 0.8rem;
  font-weight: 500;
  background: linear-gradient(135deg, var(--accent), #f59e0b);
  color: white;
  box-shadow: var(--shadow-sm);
}

.ai-output {
  font-size: 1.05rem;
  line-height: 1.7;
  color: var(--text-primary);
  white-space: pre-wrap;
  animation: fadeIn 0.5s ease-out;
}

.ai-output h3 {
  font-family: 'Poppins', sans-serif;
  color: var(--primary-dark);
  margin: 1.5rem 0 0.75rem 0;
  font-size: 1.2rem;
  font-weight: 600;
  padding-left: 1rem;
  border-left: 4px solid var(--primary);
}

.ai-output ul, .ai-output ol {
  margin: 1rem 0 1.5rem 1.5rem;
}

.ai-output li {
  margin-bottom: 0.5rem;
  padding-left: 0.5rem;
}

.ai-output p {
  margin-bottom: 1rem;
}

.loading-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 3rem;
  color: var(--text-muted);
}

.loading-spinner {
  width: 40px;
  height: 40px;
  border: 3px solid var(--border);
  border-top: 3px solid var(--primary);
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin-bottom: 1rem;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

.error-message {
  color: var(--danger);
  background: rgba(239, 68, 68, 0.1);
  border: 1px solid rgba(239, 68, 68, 0.2);
  border-radius: var(--radius);
  padding: 1rem;
  margin: 1rem 0;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.disclaimer {
  background: linear-gradient(135deg, rgba(16, 185, 129, 0.1), rgba(16, 185, 129, 0.05));
  border: 1px solid rgba(16, 185, 129, 0.2);
  border-radius: var(--radius);
  padding: 1rem;
  margin-top: 2rem;
  font-size: 0.9rem;
  color: var(--text-secondary);
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.disclaimer-icon {
  width: 24px;
  height: 24px;
  background: var(--success);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-size: 0.8rem;
  flex-shrink: 0;
}

.floating-elements {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  pointer-events: none;
  z-index: -1;
  overflow: hidden;
}

.floating-element {
  position: absolute;
  opacity: 0.1;
  animation: float 20s infinite linear;
}

.floating-element:nth-child(1) {
  top: 20%;
  left: 10%;
  animation-delay: 0s;
  color: var(--primary);
}

.floating-element:nth-child(2) {
  top: 60%;
  right: 15%;
  animation-delay: -5s;
  color: var(--secondary);
}

.floating-element:nth-child(3) {
  bottom: 30%;
  left: 20%;
  animation-delay: -10s;
  color: var(--accent);
}

@keyframes float {
  0% { transform: translateY(0px) rotate(0deg); opacity: 0.1; }
  50% { transform: translateY(-20px) rotate(180deg); opacity: 0.2; }
  100% { transform: translateY(0px) rotate(360deg); opacity: 0.1; }
}

.severity-slider {
  width: 100%;
  -webkit-appearance: none;
  appearance: none;
  height: 8px;
  border-radius: 4px;
  background: linear-gradient(to right, var(--success) 0%, var(--warning) 50%, var(--danger) 100%);
  outline: none;
  margin: 0.5rem 0;
}

.severity-slider::-webkit-slider-thumb {
  -webkit-appearance: none;
  appearance: none;
  width: 24px;
  height: 24px;
  border-radius: 50%;
  background: white;
  cursor: pointer;
  box-shadow: 0 4px 8px rgba(0,0,0,0.2);
  border: 3px solid var(--primary);
  transition: all 0.3s ease;
}

.severity-slider::-webkit-slider-thumb:hover {
  transform: scale(1.1);
}

.severity-labels {
  display: flex;
  justify-content: space-between;
  font-size: 0.8rem;
  color: var(--text-muted);
  margin-top: 0.5rem;
}

/* Responsive Design */
@media (max-width: 1200px) {
  .grid {
    grid-template-columns: 1fr;
    gap: 1.5rem;
  }
  
  .container {
    padding: 1.5rem;
  }
}

@media (max-width: 768px) {
  .header h1 {
    font-size: 2rem;
  }
  
  .header .subtitle {
    font-size: 1rem;
  }
  
  .panel {
    padding: 1.5rem;
    border-radius: var(--radius);
  }
  
  .form-row {
    grid-template-columns: 1fr;
  }
  
  .btn-group {
    flex-direction: column;
  }
  
  .result-header {
    flex-direction: column;
    gap: 1rem;
  }
  
  .tech-badges {
    justify-content: center;
  }
}

@media (max-width: 480px) {
  .container {
    padding: 1rem;
  }
  
  .header h1 {
    font-size: 1.8rem;
  }
  
  .panel {
    padding: 1rem;
  }
  
  .section-header {
    flex-direction: column;
    text-align: center;
    gap: 0.5rem;
  }
}

/* Dark mode support */
@media (prefers-color-scheme: dark) {
  :root {
    --bg-primary: #1e293b;
    --bg-secondary: #0f172a;
    --bg-tertiary: #334155;
    --text-primary: #f1f5f9;
    --text-secondary: #cbd5e1;
    --text-muted: #94a3b8;
    --border: #334155;
    --border-light: #475569;
  }
  
  body {
    background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #334155 100%);
  }
}

/* Print styles */
@media print {
  .left-panel,
  .floating-elements,
  .btn-group {
    display: none;
  }
  
  .container {
    max-width: none;
    padding: 0;
  }
  
  .panel {
    box-shadow: none;
    border: 1px solid #ccc;
  }
}
</style>
</head>
<body>
  <div class="floating-elements">
    <i class="fas fa-heartbeat floating-element" style="font-size: 60px;"></i>
    <i class="fas fa-user-md floating-element" style="font-size: 50px;"></i>
    <i class="fas fa-stethoscope floating-element" style="font-size: 55px;"></i>
  </div>

  <div class="container"><header class="header">
      <div class="medical-icon">
        <i class="fas fa-heartbeat"></i>
      </div>
      <h1><i class="fas fa-brain" style="margin-right: 0.5rem;"></i>AI Medical Diagnosis Assistant</h1>
      <p class="subtitle">Advanced AI-powered medical consultation system</p>
    </header>

    <div class="grid">
      <aside class="panel left-panel">

        <div class="section-header" style="margin-top: 2rem;">
          <div class="section-icon">
            <i class="fas fa-clipboard-list"></i>
          </div>
          <div>
            <div class="section-title">Patient Information</div>
            <div class="section-subtitle">Complete medical assessment form</div>
          </div>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label class="form-label" for="age">Age</label>
            <input id="age" class="form-input" type="text" placeholder="e.g., 23">
          </div>
          <div class="form-group">
            <label class="form-label" for="sex">Sex</label>
            <select id="sex" class="form-select">
              <option value="">Select sex</option>
              <option value="female">Female</option>
              <option value="male">Male</option>
              <option value="other">Other</option>
            </select>
          </div>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label class="form-label" for="duration">Duration</label>
            <input id="duration" class="form-input" type="text" placeholder="e.g., 3 days, 2 weeks">
          </div>
          <div class="form-group">
            <label class="form-label" for="onset">Onset</label>
            <select id="onset" class="form-select">
              <option value="">Select onset</option>
              <option value="sudden">Sudden</option>
              <option value="gradual">Gradual</option>
            </select>
          </div>
        </div>

        <div class="form-group">
          <label class="form-label" for="severity">Severity (1-10)</label>
          <input id="severity" class="severity-slider" type="range" min="1" max="10" value="5">
          <div class="severity-labels">
            <span>1 - Mild</span>
            <span>5 - Moderate</span>
            <span>10 - Severe</span>
          </div>
        </div>

        <div class="form-group">
          <label class="form-label" for="travel">Recent Travel</label>
          <input id="travel" class="form-input" type="text" placeholder="Countries visited recently">
        </div>

        <div class="form-group">
          <label class="form-label" for="vaccines">Vaccination Status</label>
          <input id="vaccines" class="form-input" type="text" placeholder="COVID-19, flu, other vaccines">
        </div>

        <div class="form-group">
          <label class="form-label" for="meds">Current Medications</label>
          <input id="meds" class="form-input" type="text" placeholder="List current medications">
        </div>

        <div class="form-group">
          <label class="form-label" for="allergies">Known Allergies</label>
          <input id="allergies" class="form-input" type="text" placeholder="Drug or food allergies">
        </div>

        <div class="form-group">
          <label class="form-label" for="symptoms">
            <i class="fas fa-notes-medical" style="margin-right: 0.5rem;"></i>Primary Symptoms
          </label>
          <textarea id="symptoms" class="form-textarea" placeholder="Describe your symptoms in detail..."></textarea>
        </div>

        <div class="form-group">
          <label class="form-label" for="additional">Additional Context</label>
          <textarea id="additional" class="form-textarea" placeholder="Any other relevant medical information..."></textarea>
        </div>

        <div class="checkbox-group">
          <input id="pregnant" class="checkbox" type="checkbox">
          <label class="form-label" for="pregnant" style="margin-bottom: 0;">
            <i class="fas fa-baby" style="margin-right: 0.5rem;"></i>Currently Pregnant
          </label>
        </div>

        <div class="btn-group">
          <button id="consultBtn" class="btn btn-primary" style="width: 100%;">
            <i class="fas fa-stethoscope"></i>
            Get AI Medical Consultation
          </button>
        </div>

        <div class="disclaimer">
          <div class="disclaimer-icon">
            <i class="fas fa-info"></i>
          </div>
          <div>
            <strong>Medical Disclaimer:</strong> This tool provides informational guidance only. 
            For urgent symptoms, call emergency services immediately. Always consult qualified healthcare professionals.
          </div>
        </div>
      </aside>

      <main class="panel right-panel">
        <div class="result-area">
          <div class="result-header">
            <div>
              <div class="result-title">
                <i class="fas fa-robot" style="margin-right: 0.5rem; color: var(--primary);"></i>
                AI Medical Consultation Report
              </div>
              <div class="result-subtitle">
                Comprehensive analysis with prioritized differentials and recommendations
              </div>
            </div>
            <div class="tech-badges">
              <div class="tech-badge">
                <i class="fas fa-brain" style="margin-right: 0.3rem;"></i>
                LYCA HEALTH SYSTEM
              </div>
              <div class="tech-badge">
                <i class="fas fa-shield-alt" style="margin-right: 0.3rem;"></i>
                FAST AND SECURE
              </div>
            </div>
          </div>

          <div id="aiResponse" class="ai-output">
            <div class="loading-state">
              <i class="fas fa-clipboard-list" style="font-size: 3rem; color: var(--text-light); margin-bottom: 1rem;"></i>
              <h3 style="color: var(--text-muted); font-weight: 500;">Ready for Medical Consultation</h3>
              <p style="text-align: center; color: var(--text-light);">
                Complete the patient information form and describe your symptoms to receive 
                a comprehensive AI-powered medical analysis with prioritized differential diagnoses.
              </p>
            </div>
          </div>
        </div>
      </main>
    </div>
  </div>



  <div style="text-align: center; margin-top: 30px;">
  <a href="index.php" style="text-decoration: none;">
    <button type="button" 
            style="background-color: #4CAF50; /* green */ 
                   color: white; 
                   padding: 12px 24px; 
                   font-size: 16px; 
                   border: none; 
                   border-radius: 8px; 
                   cursor: pointer; 
                   transition: background 0.3s;">
      <i class="fa-solid fa-house"></i> Back to Home
    </button>
  </a>
</div>

<!-- Optional hover effect -->
<style>
  button:hover {
    background-color: #45a049;
  }
</style>


<script>
// Helper: POST JSON and return parsed if possible
async function postJson(payload){
  const res = await fetch(window.location.href, {
    method: 'POST',
    credentials: 'same-origin',
    headers: {'Content-Type': 'application/json'},
    body: JSON.stringify(payload)
  });
  const text = await res.text();
  let json = null;
  try { json = JSON.parse(text); } catch(e) { /* leave null */ }
  return { status: res.status, ok: res.ok, json: json, raw: text };
}

// Update severity display
const severitySlider = document.getElementById('severity');
const severityLabels = document.querySelector('.severity-labels');

severitySlider.addEventListener('input', function() {
  const value = this.value;
  let label = 'Moderate';
  let color = 'var(--warning)';
  
  if (value <= 3) {
    label = 'Mild';
    color = 'var(--success)';
  } else if (value >= 8) {
    label = 'Severe';
    color = 'var(--danger)';
  }
  
  severityLabels.innerHTML = `
    <span>1 - Mild</span>
    <span style="color: ${color}; font-weight: 600;">${value} - ${label}</span>
    <span>10 - Severe</span>
  `;
});

// Formatting: render headings, numbered/bulleted lists, paragraphs
function escapeHtml(s){ return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }

function formatResponse(text) {
  if (!text) return '<em>(no content)</em>';
  text = text.replace(/\r\n/g,'\n').replace(/\r/g,'\n');
  const lines = text.split('\n');
  let html = '';
  let inOl=false, inUl=false;
  let paragraph=[];

  function flushParagraph(){
    if (paragraph.length){
      html += '<p>' + paragraph.join('<br>') + '</p>';
      paragraph = [];
    }
  }

  for (let raw of lines) {
    let line = raw.trim();
    if (line === '') {
      flushParagraph();
      if (inOl) { html += '</ol>'; inOl = false; }
      if (inUl) { html += '</ul>'; inUl = false; }
      continue;
    }

    // Headings like "Summary:" optionally surrounded by asterisks
    let hmatch = line.match(/^\*{0,2}\s*([A-Za-z0-9 \-()\/]+?)\s*\*{0,2}\s*[:]\s*$/);
    if (hmatch) {
      flushParagraph();
      if (inOl) { html += '</ol>'; inOl = false; }
      if (inUl) { html += '</ul>'; inUl = false; }
      html += `<h3>${escapeHtml(hmatch[1])}</h3>`;
      continue;
    }

    // Numbered list
    let nmatch = line.match(/^\d+\.\s+(.*)$/);
    if (nmatch) {
      flushParagraph();
      if (!inOl) { if (inUl) { html += '</ul>'; inUl = false; } html += '<ol>'; inOl = true; }
      html += `<li>${escapeHtml(nmatch[1])}</li>`;
      continue;
    }

    // Bullets
    let bmatch = line.match(/^[-\*\u2022]\s+(.*)$/);
    if (bmatch) {
      flushParagraph();
      if (!inUl) { if (inOl) { html += '</ol>'; inOl = false; } html += '<ul>'; inUl = true; }
      html += `<li>${escapeHtml(bmatch[1])}</li>`;
      continue;
    }

    // Regular paragraph line
    paragraph.push(escapeHtml(line));
  }

  flushParagraph();
  if (inOl) html += '</ol>';
  if (inUl) html += '</ul>';

  if (!/not medical advice/i.test(text)) {
    html += '<div class="disclaimer" style="margin-top: 1.5rem;"><div class="disclaimer-icon"><i class="fas fa-exclamation-triangle"></i></div><div><strong>Medical Disclaimer:</strong> This is not medical advice. Consult a qualified healthcare professional for personalized evaluation.</div></div>';
  }
  
  return html;
}

// Success/Error messaging
function showSuccess(message) {
  // Could implement toast notifications here
  console.log('Success:', message);
}

function showError(message) {
  // Could implement toast notifications here
  console.error('Error:', message);
}

// Consult button handler
document.getElementById('consultBtn').addEventListener('click', async () => {
  const aiArea = document.getElementById('aiResponse');
  const symptoms = document.getElementById('symptoms').value.trim();
  
  if (!symptoms) { 
    aiArea.innerHTML = '<div class="error-message"><i class="fas fa-exclamation-triangle"></i><span>Please describe your symptoms to proceed with the consultation.</span></div>'; 
    return; 
  }

  // Show loading state
  aiArea.innerHTML = `
    <div class="loading-state">
      <div class="loading-spinner"></div>
      <h3 style="color: var(--primary);">Analyzing Your Symptoms</h3>
      <p>Our AI is processing your medical information and generating a comprehensive consultation report...</p>
    </div>
  `;

  const payload = {
    action: 'diagnose',
    age: document.getElementById('age').value.trim(),
    sex: document.getElementById('sex').value,
    symptoms: symptoms,
    duration: document.getElementById('duration').value.trim(),
    severity: document.getElementById('severity').value.trim(),
    onset: document.getElementById('onset').value,
    travel: document.getElementById('travel').value.trim(),
    vaccines: document.getElementById('vaccines').value.trim(),
    meds: document.getElementById('meds').value.trim(),
    allergies: document.getElementById('allergies').value.trim(),
    pregnant: document.getElementById('pregnant').checked,
    additional: document.getElementById('additional').value.trim()
  };

  const btn = document.getElementById('consultBtn');
  const orig = btn.innerHTML;
  btn.disabled = true;
  btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Analyzing...';

  try {
    const res = await postJson(payload);
    if (!res.ok || !res.json) {
      const errMsg = (res.json && res.json.error) ? res.json.error : `Request failed (${res.status})`;
      aiArea.innerHTML = `<div class="error-message"><i class="fas fa-exclamation-triangle"></i><span>${escapeHtml(errMsg)}</span></div>`;
    } else if (res.json.ai_response) {
      aiArea.innerHTML = formatResponse(res.json.ai_response);
    } else if (res.json.error) {
      aiArea.innerHTML = `<div class="error-message"><i class="fas fa-exclamation-triangle"></i><span>${escapeHtml(res.json.error)}</span></div>`;
    } else {
      aiArea.innerHTML = '<div class="error-message"><i class="fas fa-exclamation-triangle"></i><span>Unexpected server response.</span></div>';
    }
  } catch (e) {
    aiArea.innerHTML = `<div class="error-message"><i class="fas fa-exclamation-triangle"></i><span>Network error: ${escapeHtml(e.message)}</span></div>`;
  } finally {
    btn.disabled = false;
    btn.innerHTML = orig;
  }
});

// Add smooth scrolling for better UX
document.addEventListener('DOMContentLoaded', function() {
  // Add subtle entrance animations with staggered delays
  const panels = document.querySelectorAll('.panel');
  panels.forEach((panel, index) => {
    panel.style.animationDelay = `${index * 0.2}s`;
  });
});
</script>
</body>
</html>