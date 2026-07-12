<?php
// save.php — writes building_data.json (works on XAMPP/PHP servers only)
header('Content-Type: application/json; charset=utf-8');

// Basic same-origin CORS (adjust if needed)
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit; }

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  echo json_encode(['ok' => false, 'error' => 'POST only']);
  exit;
}

// Read raw JSON body
$raw = file_get_contents('php://input');
if ($raw === false || $raw === '') {
  http_response_code(400);
  echo json_encode(['ok' => false, 'error' => 'empty body']);
  exit;
}

// Validate it's real JSON before writing
$data = json_decode($raw, true);
if ($data === null && json_last_error() !== JSON_ERROR_NONE) {
  http_response_code(400);
  echo json_encode(['ok' => false, 'error' => 'invalid json: ' . json_last_error_msg()]);
  exit;
}

// Pretty-print and write
$pretty = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
$file = __DIR__ . '/building_data.json';

// Optional: keep a timestamped backup before overwriting
@copy($file, __DIR__ . '/building_data.backup.json');

if (file_put_contents($file, $pretty) === false) {
  http_response_code(500);
  echo json_encode(['ok' => false, 'error' => 'write failed (check folder permissions)']);
  exit;
}

echo json_encode(['ok' => true, 'bytes' => strlen($pretty)]);