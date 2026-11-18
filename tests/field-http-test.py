import urllib.request,urllib.error,http.cookiejar,json,os,sys
from pathlib import Path
base=os.environ.get('FIELD_TEST_URL','http://localhost:8081/')
jar=http.cookiejar.CookieJar();c=urllib.request.build_opener(urllib.request.HTTPCookieProcessor(jar));checks=[]
def req(action,data=None):
 h={'Content-Type':'application/json','X-CSRF-Token':csrf} if data is not None else {}
 r=urllib.request.Request(base+'demo-api.php?action='+action,data=json.dumps(data).encode() if data is not None else None,headers=h)
 try:
  with c.open(r,timeout=30) as x:return x.status,json.load(x)
 except urllib.error.HTTPError as e:return e.code,json.load(e)
def ck(name,v):
 assert v,name
 checks.append(name)
s,d=req('bootstrap');csrf=d['csrf'];ck('Public sample bootstrap works without login',s==200)
s,d=req('field-list');ck('Three initial jobs load',s==200 and len(d['jobs'])==3);j=d['jobs'][0];source=j['source_project_id']
s,d=req('field-action',{'id':j['id'],'version':j['version'],'role':'engineer','action':'depart'});ck('Wrong preview role cannot perform operator action',s==422)
s,d=req('field-action',{'id':j['id'],'version':j['version'],'role':'operator','action':'depart','note':'Fictional operator en route.'});j=d['job'];ck('Operator update saves',s==200 and j['field_state']==1)
s,d=req('projects');p=next(p for p in d['projects'] if p['id']==source);ck('Operations record receives coordination state',p['payload']['field_summary']['state']==1)
s,d=req('detail&id='+source);ck('Operations history receives handoff event',any(e['kind']=='Operator headed to site' for e in d['events']))
s,d=req('update',{'id':source,'version':p['version'],'stage':p['stage'],'lat':36.42,'lng':-94.12,'note':'Fictional corrected map pin.'});ck('Sample operations can update the linked pin',s==200)
s,d=req('field-list');j=next(x for x in d['jobs'] if x['source_project_id']==source);ck('Linked pin update appears on field map',j['lat']==36.42 and j['lng']==-94.12)
s,d=req('field-create',{'role':'operator','property':'Not allowed','county':'Benton'});ck('Only installer preview creates a dispatch request',s==422)
s,d=req('field-create',{'role':'installer','property':'HTTP test lot','county':'Benton','service':'Soil & site evaluation','lat':36.3,'lng':-94.1});created=d['job'];ck('Installer request creates a mapped job',s==201 and created['field_state']==0)
s,d=req('projects');ck('Installer-created lot is visible in sample operations',any(x['id']==created['source_project_id'] for x in d['projects']))
# A separate visitor receives an independent sample dataset, never somebody else's entries.
other=urllib.request.build_opener(urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar()))
with other.open(base+'demo-api.php?action=field-list',timeout=30) as r:otherjobs=json.load(r)['jobs']
ck('Separate visitors cannot read another preview session',len(otherjobs)==3 and all(x['property']!='HTTP test lot' for x in otherjobs))
s,d=req('field-reset',{});s,d=req('field-list');ck('Reset removes temporary field-created requests',len(d['jobs'])==3)
report={'passed':len(checks),'checks':checks,'tested_url':base,'scope':'Fictional isolated demo; no production customer data or external messages.'}
Path(__file__).with_name('field-http-results.json').write_text(json.dumps(report,indent=2));print(json.dumps(report,indent=2))
