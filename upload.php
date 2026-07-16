<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
$allowedFolders = ['meeting_files', 'expense_files'];
$folder = $_POST['folder'] ?? 'meeting_files';
if (!in_array($folder, $allowedFolders, true)) { $folder = 'meeting_files'; }
$dir = __DIR__ . '/' . $folder;
if (!is_dir($dir)) @mkdir($dir, 0777, true);
if (!isset($_FILES['file'])) { echo json_encode(['ok'=>false,'error'=>'no file']); exit; }
$f = $_FILES['file'];
$name = time() . '_' . preg_replace('/[^A-Za-z0-9._-]/', '_', basename($f['name']));
if (move_uploaded_file($f['tmp_name'], $dir.'/'.$name)) {
  echo json_encode(['ok'=>true, 'path'=>$folder.'/'.$name]);
} else {
  echo json_encode(['ok'=>false, 'error'=>'move failed — check folder permissions']);
}