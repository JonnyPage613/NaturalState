<?php
declare(strict_types=1);
require dirname(__DIR__).'/hosting/public_html/field-model.php';
$passed=[];
function check(string $name,bool $value):void {global $passed;if(!$value)throw new RuntimeException($name);$passed[]=$name;}
function rejects(string $name,callable $f):void {$failed=false;try{$f();}catch(DomainException $e){$failed=true;}check($name,$failed);}
function update(array $j,string $a,string $r,array $extra=[]):array{return field_apply($j,$a,$r,['version'=>$j['version'],'note'=>'Fictional test observation.']+$extra);}
$j=field_seed()['FIELD-101'];
rejects('Engineer cannot mark operator travel',fn()=>update($j,'depart','engineer'));
rejects('Evaluation cannot precede pit readiness',fn()=>update($j,'evaluate','engineer'));
rejects('Operator cannot self-authorize backfill',fn()=>update($j,'authorize_backfill','operator'));
rejects('Installer cannot record authority approval',fn()=>update($j,'construction_permit','installer',['reference'=>'TEST-1']));
rejects('Stale sample update rejected',fn()=>field_apply($j,'depart','operator',['version'=>0]));
$j=update($j,'depart','operator');check('Travel creates an operator event',$j['field_state']===1&&end($j['events'])['actor']==='operator');
$j=update($j,'pits_ready','operator',['pits'=>[['label'=>'Pit 1','lat'=>36.3,'lng'=>-94.1],['label'=>'Pit 2','lat'=>36.3001,'lng'=>-94.1001]]]);check('Pit readiness stores individual pit locations',count($j['pits'])===2&&$j['field_state']===2);
rejects('Backfill cannot precede evaluation and authorization',fn()=>update($j,'backfill_start','operator'));
$j=update($j,'evaluate','engineer',['depth'=>'48 in','bedrock'=>'Not encountered','seasonal_water'=>'Fictional observation']);check('Engineer observation is saved',$j['soil_log']['depth']==='48 in'&&$j['field_state']===3);
$j=update($j,'authorize_backfill','engineer');check('Pit-backfill authorization does not issue a construction permit',$j['field_state']===4&&$j['design']==='Not submitted');
$j=update($j,'backfill_start','operator');check('Separate backfill return is recorded',$j['field_state']===5);
$j=update($j,'backfill_done','operator');check('Closing pits does not issue operation approval',$j['field_state']===6&&$j['operation_ref']==='');
$j=update($j,'acknowledge','installer');check('Installer can acknowledge the field assignment',$j['field_ack']);
rejects('Field acknowledgement alone cannot close installed-system project',fn()=>update($j,'close','installer'));
rejects('Application submission requires a reference',fn()=>update($j,'submit_design','engineer'));
$j=update($j,'submit_design','engineer',['reference'=>'TEST-SUBMISSION']);
check('Application submission differs from permit approval',$j['design']==='Application submitted');
rejects('Installation cannot start on a submitted application alone',fn()=>update($j,'install_start','installer'));
$j=update($j,'construction_permit','engineer',['reference'=>'TEST-CONSTRUCTION-PERMIT']);
$j=update($j,'request_change','installer',['change_type'=>'Tank size change']);$cid=$j['changes'][0]['id'];
check('Installer request creates a separate open review',$j['changes'][0]['status']==='Review requested');
rejects('Open design review holds installation',fn()=>update($j,'install_start','installer'));
rejects('Operator cannot respond as the engineer',fn()=>update($j,'review_change','operator',['change_id'=>$cid,'outcome'=>'revision']));
$j=update($j,'review_change','engineer',['change_id'=>$cid,'outcome'=>'revision']);check('Revised design waits for authority approval',$j['design']==='Revision awaiting authority review');
rejects('Engineering revision alone does not release installation',fn()=>update($j,'install_start','installer'));
$j=update($j,'change_approval','engineer',['change_id'=>$cid,'reference'=>'TEST-REVISION-APPROVAL']);
$j=update($j,'install_start','installer');$j=update($j,'install_done','installer');$j=update($j,'inspection','installer');
check('Installer records final inspection request without granting approval',$j['installation']==='Final inspection requested'&&$j['operation_ref']==='');
$j=update($j,'operation_approval','engineer',['reference'=>'TEST-OPERATION-APPROVAL']);
rejects('Closeout blocked without a retained archive',fn()=>update($j,'close','installer'));
$archive=['complete'=>true,'reference'=>'TEST-PORTAL-1','packetIds'=>['PACKET-TEST'],'storage'=>'browser-preview','files'=>array_map(fn($kind)=>['kind'=>$kind,'name'=>'fixture.pdf','bytes'=>100,'sha256'=>str_repeat('a',64)],['Application record','Design/layout','Portal confirmation','Authority decision'])];
$missing=$archive;array_pop($missing['files']);rejects('Closeout blocked without final decision file',fn()=>update($j,'close','installer',['archive_manifest'=>$missing]));
$j=update($j,'close','installer',['archive_manifest'=>$archive]);
check('Project closes after the separate milestones',$j['closed']);
$j=update($j,'request_change','installer',['change_type'=>'Additional soil evaluation']);$cid=end($j['changes'])['id'];$j=update($j,'review_change','engineer',['change_id'=>$cid,'outcome'=>'fieldwork']);
check('Additional fieldwork reopens a visit without erasing history',$j['field_state']===0&&$j['visit']===2&&!$j['closed']&&count($j['events'])>15);
$j=update($j,'opportunity','operator');check('Siteworks flag is separate from work authorization',$j['opportunity']&&$j['field_state']===0);
$j=update($j,'message','installer');check('Team note keeps its actor and timestamp',count($j['messages'])===1&&$j['messages'][0]['actor']==='installer');
$report=['passed'=>count($passed),'checks'=>$passed,'scope'=>'Fictional role-preview state machine. No business database, actual permits or external messages.'];file_put_contents(__DIR__.'/field-model-results.json',json_encode($report,JSON_PRETTY_PRINT));echo json_encode($report,JSON_PRETTY_PRINT)."\n";
