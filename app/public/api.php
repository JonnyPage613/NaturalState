<?php
require dirname(__DIR__).'/src/bootstrap.php';
$a=$_GET['action']??'';
try {
 if($a==='bootstrap') {out(['csrf'=>$_SESSION['csrf'],'geocoding_available'=>$c['geocodio_key']!=='','request_key'=>bin2hex(random_bytes(24))]);}
 if($_SERVER['REQUEST_METHOD']==='POST')csrf();
 if($a==='login'&&$_SERVER['REQUEST_METHOD']==='POST') {
  rate('login',5,900);$d=readjson();$email=strtolower(textfield($d,'email',254));
  $u=query('SELECT * FROM users WHERE email=? AND active=1',[$email])->fetch();
  if($u){$token=bin2hex(random_bytes(32));query('INSERT INTO login_tokens (token_hash,user_id,expires) VALUES (?,?,?)',[hash('sha256',$token),$u['id'],time()+1200]);mailqueue($email,'Your Natural State sign-in link',"This link expires in 20 minutes. Only use it if you requested it.\n".$c['base_url'].'/staff.html#sign-in='.$token);}
  out(['message'=>'If this address has staff access, a sign-in link will arrive shortly.']);
 }
 if($a==='redeem'&&$_SERVER['REQUEST_METHOD']==='POST') {
  rate('redeem',15,900);$d=readjson();db()->beginTransaction();
  $hash=hash('sha256',textfield($d,'token',128));$t=query('SELECT * FROM login_tokens WHERE token_hash=? AND expires>?',[$hash,time()])->fetch();
  if(!$t){db()->rollBack();fail('This link has expired or was already used. Request another link.',401);}
  $deleted=query('DELETE FROM login_tokens WHERE token_hash=?',[$hash])->rowCount();if($deleted!==1){db()->rollBack();fail('Link already used.',401);}
  $s=bin2hex(random_bytes(32));query('INSERT INTO staff_sessions (token_hash,user_id,expires) VALUES (?,?,?)',[hash('sha256',$s),$t['user_id'],time()+2592000]);db()->commit();
  setcookie('ns_staff',$s,['expires'=>time()+2592000,'path'=>'/','secure'=>$secure,'httponly'=>true,'samesite'=>'Lax']);session_regenerate_id(true);out(['ok'=>true]);
 }
 if($a==='logout'&&$_SERVER['REQUEST_METHOD']==='POST'){query('DELETE FROM staff_sessions WHERE token_hash=?',[hash('sha256',$_COOKIE['ns_staff']??'')]);setcookie('ns_staff','',['expires'=>1,'path'=>'/','secure'=>$secure,'httponly'=>true,'samesite'=>'Lax']);out(['ok'=>true]);}
 if($a==='intake'&&$_SERVER['REQUEST_METHOD']==='POST') {
  rate('intake',6,3600);if(!empty($_POST['company_website']))fail('Unable to accept request.');
  $d=$_POST;$email=strtolower(textfield($d,'email',254,true));if(!filter_var($email,FILTER_VALIDATE_EMAIL))fail('Enter a valid email.');
  $contact=['name'=>textfield($d,'name',150,true),'email'=>$email,'phone'=>textfield($d,'phone',40),'role'=>textfield($d,'role',40,true),'service'=>textfield($d,'service',100,true),'notes'=>textfield($d,'notes',4000),'deadline'=>textfield($d,'deadline',30)];
  if(($d['consent']??'')!=='yes')fail('Please confirm permission to contact you about this request.');
  $lots=json_decode($d['lots']??'',true);if(!is_array($lots)||count($lots)<1||count($lots)>20)fail('Include between 1 and 20 individual lots.');
  $requestKey=textfield($d,'request_key',64,true);if(!preg_match('/^[a-f0-9]{48}$/',$requestKey))fail('Please refresh the form.');$existing=query('SELECT id FROM batches WHERE request_key=?',[$requestKey])->fetchColumn();if($existing){$ids=query('SELECT id FROM projects WHERE batch_id=?',[$existing])->fetchAll(PDO::FETCH_COLUMN);out(['reference'=>$existing,'project_ids'=>$ids,'message'=>'This request is already saved. Scope and appointments require team confirmation.']);}
  $clean=[];foreach($lots as $lot){if(!is_array($lot))fail('Invalid lot.');$l=['property'=>textfield($lot,'property',200,true),'county'=>textfield($lot,'county',100,true),'apn'=>textfield($lot,'apn',100),'bedrooms'=>textfield($lot,'bedrooms',20),'water'=>textfield($lot,'water',60),'lat'=>null,'lng'=>null];if(isset($lot['lat'],$lot['lng'])&&$lot['lat']!==''&&$lot['lng']!==''){if(!is_numeric($lot['lat'])||!is_numeric($lot['lng'])||abs((float)$lot['lat'])>90||abs((float)$lot['lng'])>180)fail('Invalid map pin.');$l['lat']=(float)$lot['lat'];$l['lng']=(float)$lot['lng'];}$clean[]=$l;}
  $stored=null;$doc=null;$file=$_FILES['document']??null;
  if($file&&$file['error']!==UPLOAD_ERR_NO_FILE){if($file['error']!==UPLOAD_ERR_OK||$file['size']>10*1024*1024)fail('Upload one PDF up to 10 MB.');$mime=(new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);$head=file_get_contents($file['tmp_name'],false,null,0,5);if($mime!=='application/pdf'||$head!=='%PDF-')fail('Only PDF documents are accepted.');$stored=uid().'.pdf';$dir=$c['storage'].'/uploads';if(!is_dir($dir)&&!mkdir($dir,0700,true))throw new RuntimeException('Upload directory unavailable');if(!move_uploaded_file($file['tmp_name'],$dir.'/'.$stored))throw new RuntimeException('Upload failed');chmod($dir.'/'.$stored,0600);$doc=['id'=>uid(),'name'=>substr(basename($file['name']),0,255),'bytes'=>$file['size']];}
  try {db()->beginTransaction();$batch=uid();query('INSERT INTO batches (id,request_key,contact,created) VALUES (?,?,?,?)',[$batch,$requestKey,json_encode($contact,JSON_THROW_ON_ERROR),time()]);$ids=[];foreach($clean as $l){$id=uid();$ids[]=$id;query('INSERT INTO projects (id,batch_id,payload,stage,version,created) VALUES (?,?,?,?,1,?)',[$id,$batch,json_encode($l,JSON_THROW_ON_ERROR),'Received',time()]);event($id,'customer','intake',[]);}if($doc)query('INSERT INTO documents (id,batch_id,original_name,stored_name,bytes,mime) VALUES (?,?,?,?,?,?)',[$doc['id'],$batch,$doc['name'],$stored,$doc['bytes'],'application/pdf']);mailqueue($c['staff_alert'],'New Natural State request '.$batch,'A request for '.count($ids).' lot(s) is saved. Review privately: '.$c['base_url'].'/staff.html');mailqueue($email,'Natural State request received','Your reference is '.$batch.'. Your request is saved. The team must confirm scope, pricing and any appointment.');db()->commit();}catch(Throwable $e){if(db()->inTransaction())db()->rollBack();if($stored)@unlink($c['storage'].'/uploads/'.$stored);throw $e;}
  out(['reference'=>$batch,'project_ids'=>$ids,'message'=>'Your request is saved. Scope and appointments require team confirmation.'],201);
 }
 $u=staff();
 if($a==='projects'){ $rows=query('SELECT p.*,b.contact FROM projects p JOIN batches b ON b.id=p.batch_id ORDER BY p.created DESC')->fetchAll();foreach($rows as &$r){$r['payload']=json_decode($r['payload'],true);$r['contact']=json_decode($r['contact'],true);}out(['user'=>['email'=>$u['email'],'role'=>$u['role']],'projects'=>$rows,'mail_pending'=>(int)query('SELECT COUNT(*) FROM outbox WHERE sent IS NULL')->fetchColumn(),'mail_failed'=>(int)query('SELECT COUNT(*) FROM outbox WHERE sent IS NULL AND attempts>=8')->fetchColumn()]);}
 if($a==='detail'){ $id=$_GET['id']??'';$p=query('SELECT * FROM projects WHERE id=?',[$id])->fetch();if(!$p)fail('Project not found.',404);$events=query('SELECT * FROM events WHERE project_id=? ORDER BY created,id',[$id])->fetchAll();$files=query('SELECT id,original_name,bytes FROM documents WHERE batch_id=?',[$p['batch_id']])->fetchAll();out(['events'=>$events,'files'=>$files]);}
 if($a==='document'){ $f=query('SELECT * FROM documents WHERE id=?',[$_GET['id']??''])->fetch();if(!$f)fail('Document not found.',404);$path=$c['storage'].'/uploads/'.$f['stored_name'];if(!is_file($path))fail('Document unavailable.',404);header('Content-Type: application/pdf');header('Content-Security-Policy: sandbox');header('Content-Disposition: attachment; filename="document.pdf"');header('Content-Length: '.filesize($path));readfile($path);exit;}
 if($a==='update'&&$_SERVER['REQUEST_METHOD']==='POST') {
  $d=readjson();db()->beginTransaction();$p=query('SELECT * FROM projects WHERE id=?',[$d['id']??''])->fetch();if(!$p){db()->rollBack();fail('Project not found.',404);}
  $payload=json_decode($p['payload'],true);$stage=textfield($d,'stage',40,true);
  $stages=['Received','Needs information','Quote sent','Quote accepted','Deposit pending','Deposit paid / waived','Pit digging scheduled','Visit confirmed','Fieldwork complete','Engineering review','Submitted for permit review','Authority decision recorded','Deliverables released','Invoiced','Closed'];
  if(!in_array($stage,$stages,true))fail('Invalid stage.');
  if(in_array($stage,['Submitted for permit review','Authority decision recorded','Deliverables released'],true)&&$u['role']!=='engineer')fail('An engineer must record this milestone.',403);
  $note=textfield($d,'note',4000);if($stage!==$p['stage'] && abs(array_search($stage,$stages,true)-array_search($p['stage'],$stages,true))>1 && $note==='')fail('Add a reason when skipping or reopening workflow checkpoints.');if(in_array($stage,['Submitted for permit review','Authority decision recorded','Deliverables released'],true)&&$note==='')fail('Record the receipt, authority decision or release reference.');
  foreach(['lat','lng'] as $k)if(array_key_exists($k,$d)){if(!is_numeric($d[$k])||abs((float)$d[$k])>($k==='lat'?90:180))fail('Invalid coordinates.');$payload[$k]=(float)$d[$k];}
  if(isset($d['pit'])&&is_array($d['pit'])){if($u['role']!=='engineer')fail('An engineer must record a pit log.',403);$pit=[];foreach(['label','depth','bedrock','water','loading_rate','source'] as $k)$pit[$k]=textfield($d['pit'],$k,300,$k==='label');$payload['pits'][]=$pit;}
  $n=query('UPDATE projects SET payload=?,stage=?,version=version+1 WHERE id=? AND version=?',[json_encode($payload,JSON_THROW_ON_ERROR),$stage,$p['id'],(int)($d['version']??0)])->rowCount();if(!$n){db()->rollBack();fail('Someone updated this project. Refresh before saving.',409);}
  if(db()->getAttribute(PDO::ATTR_DRIVER_NAME)==='pgsql'&&isset($payload['lat'],$payload['lng'])){query("DELETE FROM spatial_features WHERE project_id=? AND kind='pin'",[$p['id']]);query("INSERT INTO spatial_features(id,project_id,kind,geom) VALUES (?,?,'pin',ST_SetSRID(ST_MakePoint(?,?),4326))",[uid(),$p['id'],$payload['lng'],$payload['lat']]);}
  event($p['id'],$u['email'],'update',['from'=>$p['stage'],'to'=>$stage,'note'=>$note,'pit'=>$d['pit']??null,'location'=>['lat'=>$payload['lat']??null,'lng'=>$payload['lng']??null]]);db()->commit();out(['ok'=>true]);
 }
 if($a==='geocode'&&$_SERVER['REQUEST_METHOD']==='POST') {
  rate('geocode',30,3600);if(!$c['geocodio_key'])fail('Address lookup is not configured. Use a parcel reference or map pin.',503);$d=readjson();$address=textfield($d,'address',200,true);$url='https://api.geocod.io/v1.7/geocode?'.http_build_query(['q'=>$address,'api_key'=>$c['geocodio_key']]);$ctx=stream_context_create(['http'=>['timeout'=>12,'ignore_errors'=>true]]);$data=json_decode(file_get_contents($url,false,$ctx)?:'{}',true);if(empty($data['results']))fail('Address not found. Place a map pin instead.');out(['results'=>array_slice($data['results'],0,5)]);
 }
 fail('Not found.',404);
} catch(InvalidArgumentException $e){if(db()->inTransaction())db()->rollBack();fail($e->getMessage());}
catch(Throwable $e){if(db()->inTransaction())db()->rollBack();error_log('Natural State error: '.$e->getMessage());out(['error'=>'We could not complete this action. Please retry. A request is confirmed only when a reference is displayed.'],500);}
