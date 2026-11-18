<?php
declare(strict_types=1);
require dirname(__DIR__).'/hosting/public_html/field-model.php';
$checks=[];
function ok(string $name,bool $condition):void{global $checks;if(!$condition)throw new RuntimeException($name);$checks[]=$name;}
function fails(string $name,callable $fn):void{try{$fn();}catch(DomainException $e){ok($name,true);return;}throw new RuntimeException($name);}
function applyAction(array $j,string $a,string $role,array $extra=[]):array{return field_apply($j,$a,$role,['version'=>$j['version'],'note'=>'Preview test']+$extra);}
$j=field_seed()['FIELD-101'];
fails('Installer cannot save engineering inputs',fn()=>applyAction($j,'engineering_record','installer',['record'=>[]]));
fails('Operator cannot save engineering inputs',fn()=>applyAction($j,'engineering_record','operator',['record'=>[]]));
$r=['primary_bedrock'=>48,'primary_bswt'=>24,'primary_loading'=>.3,'secondary_bedrock'=>'','stub_elev'=>1000.5,'tank_elev'=>999.75,'system_type'=>'Pumped','treatment'=>'STD','disposal'=>'LPD','tank_gal'=>1250,'approval_date'=>'2026-11-16'];
$j=applyAction($j,'engineering_record','engineer',['record'=>$r]);ok('Primary and secondary observations are distinct',$j['engineering_record']['primary_bedrock']===48.0&&$j['engineering_record']['secondary_bedrock']===null);
ok('Configuration differs from treatment and disposal',$j['engineering_record']['system_type']==='Pumped'&&$j['engineering_record']['treatment']==='STD'&&$j['engineering_record']['disposal']==='LPD');
$j=applyAction($j,'engineering_record','engineer',['record'=>$r+['benchmark'=>'BM-01']]);ok('Engineering revisions are retained',count($j['engineering_revisions'])===2);
fails('Negative depth rejected',fn()=>ns_engineering_record(['primary_bedrock'=>-1]));fails('Invalid approval date rejected',fn()=>ns_engineering_record(['approval_date'=>'2026-02-30']));
$f=['id'=>'PIN-1','kind'=>'Water Well','label'=>'W-01','note'=>'Observed reference','source'=>'Map placement','accuracy_ft'=>null,'verified'=>true,'geometry'=>['type'=>'Point','coordinates'=>[-93.9,36.28]]];
fails('Installer cannot place engineering site features',fn()=>applyAction($j,'site_features','installer',['features'=>[$f]]));
$j=applyAction($j,'site_features','operator',['features'=>[$f]]);ok('Operator cannot self-verify a well',!$j['site_features'][0]['verified']);
$f=$j['site_features'][0];$f['verified']=true;$j=applyAction($j,'site_features','engineer',['features'=>[$f]]);ok('Engineer can record verification',$j['site_features'][0]['verified']);
$f=$j['site_features'][0];$f['note']='Changed location evidence';$j=applyAction($j,'site_features','operator',['features'=>[$f]]);ok('Operator edits clear engineering verification',!$j['site_features'][0]['verified']);
$bad=$f;$bad['geometry']['coordinates']=[-190,95];fails('Invalid feature coordinates rejected',fn()=>ns_features([$bad],'engineer'));
$bad=$f;$bad['geometry']=['type'=>'LineString','coordinates'=>[[-93.9,36.2],[-93.91,36.21]]];fails('Well requires point geometry',fn()=>ns_features([$bad],'engineer'));
$j=applyAction($j,'depart','operator',['eta_minutes'=>60]);ok('Departure queues on-way hook with ETA',$j['notifications'][0]['kind']==='On the Way'&&$j['notifications'][0]['eta_minutes']===60&&$j['notifications'][0]['window_minutes']===15);
$j=applyAction($j,'photo_recorded','operator');ok('Photo hook is job-linked',end($j['notifications'])['kind']==='Pit Photo Uploaded');
$j=applyAction($j,'pits_ready','operator');$j=applyAction($j,'evaluate','engineer');ok('Evaluation queues completion hook',end($j['notifications'])['kind']==='Eval Complete');
fails('Unsafe payment URL rejected',fn()=>applyAction($j,'billing','operator',['invoice_url'=>'javascript:alert(1)','method'=>'Unpaid']));
$j=applyAction($j,'billing','operator',['invoice_url'=>'https://example.com/invoice/test','method'=>'Check','reference'=>'CHECK-001']);ok('Check payment retained independently from invoice link',$j['billing']['paid']&&$j['billing']['method']==='Check'&&$j['billing']['reference']==='CHECK-001');
$j=applyAction($j,'customer_update','operator',['kind'=>'Invoice Ready']);ok('Invoice notification keeps separate HTTPS link',end($j['notifications'])['invoice_url']==='https://example.com/invoice/test');
ok('Preview hooks never claim delivery',count(array_filter($j['notifications'],fn($n)=>$n['delivery']!=='Preview only — not sent'))===0);
fails('Invalid ETA rejected',fn()=>applyAction($j,'customer_update','operator',['kind'=>'Delayed','eta_minutes'=>-1]));
$report=['passed'=>count($checks),'checks'=>$checks];file_put_contents(__DIR__.'/engineering-model-results.json',json_encode($report,JSON_PRETTY_PRINT));echo json_encode($report,JSON_PRETTY_PRINT)."\n";
