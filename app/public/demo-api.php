<?php
declare(strict_types=1);
header('Content-Type: application/json');header('Cache-Control: no-store');header('X-Content-Type-Options: nosniff');header('Referrer-Policy: no-referrer');
session_name('ns_walkthrough');session_set_cookie_params(['lifetime'=>86400,'path'=>'/','secure'=>!empty($_SERVER['HTTPS'])&&$_SERVER['HTTPS']!=='off','httponly'=>true,'samesite'=>'Lax']);ini_set('session.use_strict_mode','1');session_start();
function reply(array $d,int $code=200):never{http_response_code($code);echo json_encode($d,JSON_THROW_ON_ERROR);exit;}
function samples():array {$out=[];$examples=json_decode(file_get_contents(__DIR__.'/assets/data/sample-locations.json'),true,512,JSON_THROW_ON_ERROR);foreach($examples as $i=>$payload){$payload['apn']='';$out[]=['id'=>'SAMPLE-'.($i+1),'batch_id'=>'SAMPLE-BUILDER','payload'=>$payload,'contact'=>['name'=>'Sample customer','email'=>'sample@example.test','phone'=>'','service'=>'Soil & site evaluation','deadline'=>''],'stage'=>['Received','Quote sent','Engineering review'][$i],'version'=>1,'created'=>time()-86400*($i+1)];}return $out;}
$_SESSION['csrf']??=bin2hex(random_bytes(24));$_SESSION['projects']??=samples();$_SESSION['events']??=[];
// Update only the known demonstration locations; preserve visitor-entered work orders.
if(empty($_SESSION['public_location_examples_v4'])){foreach($_SESSION['projects'] as &$p){foreach(samples() as $seed)if($p['id']===$seed['id']){$p['payload']=array_replace($p['payload'],$seed['payload']);$p['version']++;}if(in_array($p['payload']['property']??'',['Sample home site 1','Boone Co. · 3-acre family homesite'],true)){$seed=samples()[0];$p['payload']=array_replace($p['payload'],$seed['payload']);$p['version']++;}}unset($p);foreach($_SESSION['field_jobs']??[] as $id=>$j){foreach($j['events'] as &$event)if(str_contains($event['note']??'','60/40'))$event['note']='Additional site-service request flagged for a separate scope review.';unset($event);$_SESSION['field_jobs'][$id]=$j;}foreach($_SESSION['events'] as &$events){foreach($events as &$event)if(str_contains($event['payload']??'','60/40'))$event['payload']='Additional site-service request flagged for a separate scope review.';unset($event);}unset($events);$_SESSION['public_location_examples_v4']=true;}
if(empty($_SESSION['sample_pit_locations_v5'])){foreach($_SESSION['field_jobs']??[] as $id=>$j){if(in_array($j['source_project_id']??'',['SAMPLE-1','SAMPLE-2','SAMPLE-3'],true)&&!empty($j['pits'])){$origin=$j['pits'][0];if(abs($origin['lat']-$j['lat'])+abs($origin['lng']-$j['lng'])>0.01){foreach($j['pits'] as &$pit)if(str_starts_with($pit['label']??'','Sample pit')){$pit['lat']=$j['lat']+($pit['lat']-$origin['lat'])+0.00015;$pit['lng']=$j['lng']+($pit['lng']-$origin['lng'])+0.00015;}unset($pit);$_SESSION['field_jobs'][$id]=$j;}}}$_SESSION['sample_pit_locations_v5']=true;}
$a=$_GET['action']??'';
if($_SERVER['REQUEST_METHOD']==='POST'&&!hash_equals($_SESSION['csrf'],$_SERVER['HTTP_X_CSRF_TOKEN']??$_POST['csrf']??''))reply(['error'=>'Refresh the walkthrough and try again.'],403);
if($a==='bootstrap')reply(['csrf'=>$_SESSION['csrf'],'geocoding_available'=>false,'request_key'=>bin2hex(random_bytes(24)),'demo'=>true]);
if(str_starts_with($a,'field-')){require __DIR__.'/field-model.php';field_demo_handle($a);}
if($a==='projects')reply(['user'=>['email'=>'Fictional walkthrough','role'=>'engineer'],'projects'=>array_values($_SESSION['projects']),'mail_pending'=>0,'mail_failed'=>0,'demo'=>true]);
if($a==='intake'&&$_SERVER['REQUEST_METHOD']==='POST'){
 if(count($_SESSION['projects'])>100)reply(['error'=>'Reset your samples before adding more.'],429);
 $lots=json_decode($_POST['lots']??'',true);if(!is_array($lots)||count($lots)<1||count($lots)>20)reply(['error'=>'Include 1–20 individual lots.'],422);
 $key=(string)($_POST['request_key']??'');if(isset($_SESSION['requests'][$key]))reply($_SESSION['requests'][$key]);
 $batch='SAMPLE-'.strtoupper(bin2hex(random_bytes(4)));$ids=[];
 foreach($lots as $lot){$id='SAMPLE-'.strtoupper(bin2hex(random_bytes(5)));$ids[]=$id;$payload=[];foreach(['property','county','apn','bedrooms','water'] as $k)$payload[$k]=substr(trim((string)($lot[$k]??'')),0,200);if(!$payload['property']||!$payload['county'])reply(['error'=>'Include a property reference and county.'],422);foreach(['lat','lng'] as $k)$payload[$k]=isset($lot[$k])&&$lot[$k]!==''&&is_numeric($lot[$k])?(float)$lot[$k]:null;
 $contact=[];foreach(['name','email','phone','service','deadline','notes','requested_date','requested_time','visit_type'] as $k)$contact[$k]=substr((string)($_POST[$k]??''),0,1000);
 $_SESSION['projects'][]=['id'=>$id,'batch_id'=>$batch,'payload'=>$payload,'contact'=>$contact,'stage'=>'Received','version'=>1,'created'=>time()];$_SESSION['events'][$id]=[['actor'=>'sample inquiry','kind'=>'sample intake','created'=>time(),'payload'=>'No real email, appointment or business record was created.']];
 }
 $r=['reference'=>$batch,'project_ids'=>$ids,'message'=>'Your sample request is ready. Open it in the walkthrough. No email was sent and no appointment was booked.'];$_SESSION['requests'][$key]=$r;reply($r,201);
}
if($a==='detail')reply(['events'=>$_SESSION['events'][$_GET['id']??'']??[],'files'=>[]]);
if($a==='document')reply(['error'=>'The walkthrough does not retain uploaded PDFs.'],404);
if($a==='geocode')reply(['error'=>'Address lookup is optional and not enabled in this public preview. Place a map pin instead.'],503);
if($a==='reset'&&$_SERVER['REQUEST_METHOD']==='POST'){$_SESSION['projects']=samples();$_SESSION['events']=[];$_SESSION['requests']=[];reply(['ok'=>true]);}
if($a==='update'&&$_SERVER['REQUEST_METHOD']==='POST'){
 $d=json_decode(file_get_contents('php://input'),true);if(!is_array($d))reply(['error'=>'Invalid sample update.'],422);
 foreach($_SESSION['projects'] as &$p)if($p['id']===($d['id']??'')){
  if($p['version']!==(int)($d['version']??0))reply(['error'=>'This sample changed. Refresh before saving.'],409);
  $stage=substr((string)($d['stage']??$p['stage']),0,40);$p['stage']=$stage;$p['version']++;
  foreach(['lat','lng'] as $k)if(isset($d[$k])&&is_numeric($d[$k])&&abs((float)$d[$k])<=($k==='lat'?90:180))$p['payload'][$k]=(float)$d[$k];
  if(isset($d['pit'])&&is_array($d['pit'])){if(count($p['payload']['pits']??[])>=30)reply(['error'=>'Reset samples before adding more pit logs.'],422);$pit=[];foreach(['label','depth','bedrock','water','loading_rate','source'] as $k)$pit[$k]=substr((string)($d['pit'][$k]??''),0,300);$p['payload']['pits'][]=$pit;}
  $_SESSION['events'][$p['id']][]=['actor'=>'sample reviewer','kind'=>'sample update','created'=>time(),'payload'=>substr((string)($d['note']??''),0,4000).' · '.$stage];reply(['ok'=>true]);
 }reply(['error'=>'Sample not found.'],404);
}
reply(['error'=>'Not found.'],404);
