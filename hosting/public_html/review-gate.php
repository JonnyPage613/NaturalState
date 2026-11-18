<?php
/* Public sample walkthrough. Real records are authorized independently by api.php. */
declare(strict_types=1);
header('Cache-Control: no-store');header('X-Robots-Tag: noindex, nofollow, noarchive');header('Referrer-Policy: no-referrer');header('X-Content-Type-Options: nosniff');
header("Content-Security-Policy: default-src 'self'; script-src 'self'; style-src 'self' 'unsafe-inline'; img-src 'self' data: blob: https://basemap.nationalmap.gov https://elevation.nationalmap.gov https://sdmdataaccess.sc.egov.usda.gov; connect-src 'self' https://gis.arkansas.gov https://sdmdataaccess.sc.egov.usda.gov https://basemap.nationalmap.gov https://elevation.nationalmap.gov; worker-src blob:; object-src 'none'; base-uri 'self'; frame-ancestors 'none'; form-action 'self'");
$path=parse_url($_SERVER['REQUEST_URI'],PHP_URL_PATH);if(!is_string($path)){http_response_code(400);exit;}
$relative=rawurldecode(ltrim($path,'/'));if($relative==='')$relative='index.html';
$allowed=['system.html'=>'text/html; charset=utf-8','platform.html'=>'text/html; charset=utf-8','gis-plan.html'=>'text/html; charset=utf-8','receptionist.html'=>'text/html; charset=utf-8','index.html'=>'text/html; charset=utf-8','engineering.html'=>'text/html; charset=utf-8','workspace.html'=>'text/html; charset=utf-8','staff.html'=>'text/html; charset=utf-8','field.html'=>'text/html; charset=utf-8','proposal.html'=>'text/html; charset=utf-8','robots.txt'=>'text/plain'];
$ext=strtolower(pathinfo($relative,PATHINFO_EXTENSION));$types=['css'=>'text/css','js'=>'text/javascript','svg'=>'image/svg+xml','png'=>'image/png','jpg'=>'image/jpeg','jpeg'=>'image/jpeg','txt'=>'text/plain'];
if(!isset($allowed[$relative])&&!(str_starts_with($relative,'assets/')&&isset($types[$ext]))){http_response_code(404);exit;}
$base=realpath(__DIR__);$file=realpath(__DIR__.'/'.$relative);if(!$file||!str_starts_with($file,$base.DIRECTORY_SEPARATOR)||!is_file($file)){http_response_code(404);exit;}
header('Content-Type: '.($allowed[$relative]??$types[$ext]));if($_SERVER['REQUEST_METHOD']!=='HEAD')readfile($file);
