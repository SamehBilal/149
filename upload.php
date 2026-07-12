<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
$dir = __DIR__ . '/meeting_files';
if (!is_dir($dir)) @mkdir($dir, 0777, true);
if (!isset($_FILES['file'])) { echo json_encode(['ok'=>false,'error'=>'no file']); exit; }
$f = $_FILES['file'];
$name = time() . '_' . preg_replace('/[^A-Za-z0-9._-]/', '_', basename($f['name']));
if (move_uploaded_file($f['tmp_name'], $dir.'/'.$name)) {
  echo json_encode(['ok'=>true, 'path'=>'meeting_files/'.$name]);
} else {
  echo json_encode(['ok'=>false, 'error'=>'move failed — check folder permissions']);
}