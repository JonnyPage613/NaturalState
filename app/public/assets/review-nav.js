/* Partner-review navigation is separate from the future public customer site. */
(() => {
 'use strict';
 if(document.body.dataset.mode==='live')return;
 const path=location.pathname.split('/').pop()||'index.html';
 const area=path==='engineering.html'||path==='siteworks.html'?'customer':path==='field.html'?'field':path==='workspace.html'?'records':path==='proposal.html'||path==='platform.html'?'proposal':'home';
 const labels={customer:'Customer experience',field:'Internal GIS & dispatch',records:'Internal job records',proposal:'Business proposal',home:'For Tyler'};
 const bar=document.createElement('div');bar.className='ns-review-nav';bar.setAttribute('role','navigation');bar.setAttribute('aria-label','Partner review navigator');
 bar.innerHTML=`<a class="ns-review-home" href="index.html"><img src="assets/brand/engineering-mark.png" width="31" height="30" alt=""><span>PROJECT REVIEW<span class="ns-review-context">${labels[area]}</span></span></a><div class="ns-review-links"><a class="ns-review-tab" data-area="customer" href="engineering.html">Customer</a><a class="ns-review-tab" data-area="field" href="field.html">Field app</a><a class="ns-review-tab" data-area="records" href="workspace.html">Records</a><a class="ns-review-tab" data-area="proposal" href="proposal.html">Proposal</a><details class="ns-review-menu"><summary>Guides ▾</summary><div class="ns-review-dropdown"><a href="index.html">Review home & all pages</a><a href="system.html">Complete system guide</a><a href="gis-plan.html">GIS & vehicle tracking plan</a><a href="receptionist.html">AI receptionist plan</a><a href="platform.html">Digital scope & service offers</a><a href="siteworks.html">Siteworks customer concept</a><a href="walkthrough.html">Original detailed walkthrough</a><small>Review tools are excluded from the public customer launch.</small></div></details></div>`;
 document.body.prepend(bar);document.body.classList.add('ns-review-mode');
 const active=bar.querySelector(`[data-area="${area}"]`);if(active)active.setAttribute('aria-current','page');
 const menu=bar.querySelector('details');document.addEventListener('click',e=>{if(!menu.contains(e.target))menu.open=false;});document.addEventListener('keydown',e=>{if(e.key==='Escape')menu.open=false;});
 const remembered=()=>{try{return localStorage.getItem('ns-review-last-project')||'';}catch(e){return '';}};
 const setContext=id=>{
  if(!id||!/^[A-Za-z0-9_-]{1,100}$/.test(id))return;
  try{localStorage.setItem('ns-review-last-project',id);}catch(e){}
  bar.querySelector('[data-area="field"]').href='field.html?project='+encodeURIComponent(id);
  bar.querySelector('[data-area="records"]').href='workspace.html?project='+encodeURIComponent(id);
  document.querySelectorAll('[data-continue-project]').forEach(a=>{a.href=(a.dataset.continueProject==='field'?'field.html':'workspace.html')+'?project='+encodeURIComponent(id);a.hidden=false;});
 };
 setContext(new URLSearchParams(location.search).get('project')||remembered());
 window.addEventListener('ns-review-job',e=>setContext(e.detail?.project));
 document.querySelectorAll('.ns-continue-empty').forEach(el=>{el.hidden=!!remembered();});
 // This guide follows the existing PHP-session intake; it never creates or resets a record.
 if(path==='engineering.html'&&new URLSearchParams(location.search).has('walkthrough')){
  const intake=document.getElementById('intake');if(intake){const guide=document.createElement('div');guide.className='ns-guide';guide.innerHTML='<b>Follow one request through the app</b><p>1. Select “Fill an example request.” 2. Review the property and preferred time. 3. Create the sample request. The confirmation links open that exact job on the dispatch map and in job records.</p>';intake.prepend(guide);}
 }
})();
