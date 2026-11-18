"""Real HTTP checks against isolated SQLite storage. Does not contact customers."""
import tempfile, pathlib, subprocess, os, time, json, urllib.request, urllib.error, http.cookiejar, sqlite3, re, secrets
ROOT=pathlib.Path(__file__).resolve().parents[1]
PHP=os.environ.get('PHP_BIN','/opt/homebrew/bin/php')
with tempfile.TemporaryDirectory() as t:
 t=pathlib.Path(t);config=t/'config.php';db=t/'db.sqlite';storage=t/'storage';storage.mkdir()
 config.write_text("<?php return "+"['dsn'=>'sqlite:"+str(db)+"','db_user'=>'','db_password'=>'','base_url'=>'http://127.0.0.1:18881','storage'=>'"+str(storage)+"','smtp_host'=>'','smtp_port'=>1025,'smtp_user'=>'','smtp_password'=>'','smtp_security'=>'','mail_from'=>'test@example.test','staff_alert'=>'team@example.test','geocodio_key'=>''];")
 env={**os.environ,'APP_CONFIG':str(config)}
 subprocess.run([PHP,'bin/setup.php'],cwd=ROOT,env=env,check=True,capture_output=True)
 for email,role in [('engineer@example.test','engineer'),('ops@example.test','operations')]:subprocess.run([PHP,'bin/staff.php',email,role],cwd=ROOT,env=env,check=True,capture_output=True)
 log=open(t/'server.log','w');server=subprocess.Popen([PHP,'-S','127.0.0.1:18881','-t','public'],cwd=ROOT,env=env,stdout=log,stderr=log)
 jar=http.cookiejar.CookieJar();opener=urllib.request.build_opener(urllib.request.HTTPCookieProcessor(jar));checks=[]
 def req(action,data=None,raw=None,headers=None):
  h=headers or {};body=raw
  if data is not None:body=json.dumps(data).encode();h={**h,'Content-Type':'application/json','X-CSRF-Token':csrf}
  r=urllib.request.Request('http://127.0.0.1:18881/api.php?action='+action,data=body,headers=h)
  try:
   with opener.open(r) as x:return x.status,x.read()
  except urllib.error.HTTPError as e:return e.code,e.read()
 def check(label,ok):
  assert ok,label
  checks.append(label)
 def multipart(fields,file=None):
  boundary='NS'+secrets.token_hex(12);chunks=[]
  for k,v in fields.items():chunks += [f'--{boundary}\r\nContent-Disposition: form-data; name="{k}"\r\n\r\n{v}\r\n'.encode()]
  if file:chunks += [f'--{boundary}\r\nContent-Disposition: form-data; name="document"; filename="survey.pdf"\r\nContent-Type: application/pdf\r\n\r\n'.encode()+file+b'\r\n']
  chunks += [f'--{boundary}--\r\n'.encode()];return b''.join(chunks),{'Content-Type':'multipart/form-data; boundary='+boundary}
 try:
  for _ in range(50):
   try:s,b=req('bootstrap');break
   except urllib.error.URLError:time.sleep(.1)
  boot=json.loads(b);csrf=boot['csrf'];check('Anonymous bootstrap supplies CSRF and request nonce',s==200 and len(boot['request_key'])==48)
  check('Anonymous project reads denied',req('projects')[0]==401)
  check('CSRF-less writes denied',req('login',raw=b'{}',headers={'Content-Type':'application/json'})[0]==403)
  fields={'csrf':csrf,'request_key':boot['request_key'],'name':'Test builder','email':'builder@example.test','role':'Builder / developer','service':'Soil evaluation','consent':'yes','lots':json.dumps([{'property':f'Lot {i}','county':'Benton','apn':f'TEST-{i}','bedrooms':'3','water':'Unknown','lat':36.3,'lng':-94.1} for i in range(1,4)])}
  pdf=b'%PDF-1.4\n1 0 obj\n<< /Type /Catalog >>\nendobj\ntrailer\n<< /Root 1 0 R >>\n%%EOF\n';body,h=multipart(fields,pdf);s,b=req('intake',raw=body,headers=h);result=json.loads(b);check('Intake and PDF saved with three linked lot records',s==201 and len(result['project_ids'])==3)
  s,b=req('intake',raw=body,headers=h);check('Retried inquiry is idempotent',s==200 and json.loads(b)['reference']==result['reference'])
  fields['request_key']=secrets.token_hex(24);bad,h2=multipart(fields,b'<script>alert(1)</script>');check('Fake PDF rejected',req('intake',raw=bad,headers=h2)[0]==422)
  def login(email):
   check('Sign-in email queued for '+email,req('login',{'email':email})[0]==200)
   con=sqlite3.connect(db);mail=con.execute('SELECT body FROM outbox WHERE recipient=? ORDER BY rowid DESC',(email,)).fetchone()[0];con.close();token=re.search(r'sign-in=([a-f0-9]+)',mail).group(1);check('Single-use email link grants access',req('redeem',{'token':token})[0]==200);check('Used link cannot be replayed',req('redeem',{'token':token})[0]==401)
  login('ops@example.test');s,b=req('projects');d=json.loads(b);check('Shared dashboard reads all three independent lots',s==200 and len(d['projects'])==3 and d['user']['role']=='operations');p=d['projects'][0]
  check('Operations cannot record engineer release',req('update',{'id':p['id'],'version':1,'stage':'Deliverables released','note':'test'})[0]==403)
  check('Project pin and next stage persist',req('update',{'id':p['id'],'version':1,'stage':'Needs information','lat':36.4,'lng':-94.2,'note':'Need survey'})[0]==200)
  check('Stale writes rejected',req('update',{'id':p['id'],'version':1,'stage':'Quote sent'})[0]==409)
  s,b=req('detail&id='+p['id']);detail=json.loads(b);check('PDF available to authenticated staff',req('document&id='+detail['files'][0]['id'])[0]==200)
  req('logout',{});check('Private PDF denied after logout',req('document&id='+detail['files'][0]['id'])[0]==401)
  login('engineer@example.test');s,b=req('projects');p=next(x for x in json.loads(b)['projects'] if x['id']==p['id']);check('Saved pin shared between staff accounts',p['payload']['lat']==36.4)
  check('Engineer can add observed pit log',req('update',{'id':p['id'],'version':p['version'],'stage':p['stage'],'pit':{'label':'Pit 1','depth':'48 in','bedrock':'Not encountered','water':'Not observed','source':'Test observer / fictional evidence'}})[0]==200)
  check('Authority milestone requires evidence reference',req('update',{'id':p['id'],'version':p['version']+1,'stage':'Authority decision recorded','note':''})[0]==422)
  check('Unconfigured geocoding fails honestly',req('geocode',{'address':'Test'})[0]==503)
  subprocess.run([PHP,'bin/revoke.php','engineer@example.test'],cwd=ROOT,env=env,check=True,capture_output=True)
  check('Revoking staff immediately invalidates remembered access',req('projects')[0]==401)
  con=sqlite3.connect(db);check('Only one batch, three lots and one PDF after retry',con.execute('SELECT COUNT(*) FROM batches').fetchone()[0]==1 and con.execute('SELECT COUNT(*) FROM projects').fetchone()[0]==3 and con.execute('SELECT COUNT(*) FROM documents').fetchone()[0]==1);con.close()
  # Deliver through a local SMTP sink; no external messages are sent.
  import smtpd, asyncore, threading
  delivered=[]
  class Sink(smtpd.SMTPServer):
   def process_message(self, peer, mailfrom, rcpttos, data, **kw):delivered.append((mailfrom,rcpttos,data))
  sink=Sink(('127.0.0.1',18882),None);stop=threading.Event()
  def smtp_loop():
   while not stop.is_set():asyncore.loop(timeout=.05,count=1)
  thread=threading.Thread(target=smtp_loop,daemon=True);thread.start()
  config.write_text(config.read_text().replace("'smtp_host'=>''","'smtp_host'=>'127.0.0.1'").replace("'smtp_port'=>1025","'smtp_port'=>18882"))
  subprocess.run([PHP,'bin/mail-worker.php'],cwd=ROOT,env=env,check=True,capture_output=True)
  stop.set();thread.join(2);sink.close();con=sqlite3.connect(db)
  check('SMTP worker delivers queued messages to local sink and records success',len(delivered)==4 and con.execute('SELECT COUNT(*) FROM outbox WHERE sent IS NOT NULL').fetchone()[0]==4);con.close()
  report={'passed':len(checks),'checks':checks,'scope':'PHP HTTP integration using isolated SQLite. Local SMTP sink delivery tested; MySQL/PostGIS and Docker unverified. Browser GIS checked separately.'};(ROOT/'tests/results.json').write_text(json.dumps(report,indent=2));print(json.dumps(report,indent=2))
 finally:server.terminate();server.wait();log.close()
