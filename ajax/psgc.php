<?php
require_once __DIR__.'/../models/Psgc.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: public, max-age=3600');
try {
    $level=is_string($_GET['level']??null)?$_GET['level']:'';
    $parent=is_string($_GET['parent']??null)?$_GET['parent']:'';
    $province=is_string($_GET['province']??null)?$_GET['province']:'';
    if (!in_array($level,['regions','provinces','cities','barangays'],true)||!preg_match('/^(?:\d{10})?$/D',$parent)||!preg_match('/^(?:\d{10}|none)?$/D',$province)) {http_response_code(400);echo '{"error":"Invalid address query"}';exit;}
    echo json_encode(Psgc::options($level,$parent,$province),JSON_UNESCAPED_UNICODE);
} catch(Throwable $error) {http_response_code(503);echo '{"error":"Address choices are unavailable. Please retry."}';}
