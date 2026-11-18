/* Guided partner review. It never submits, resets or migrates application records. */
(() => {
 'use strict';
 const steps=[
  ['index.html','Project overview','The three parts of the project and how to review them.','customer-preview.png'],
  ['engineering.html','Customer website','See how a landowner chooses a service, asks a question and requests a site visit. This is the customer-facing experience.','customer-preview.png'],
  ['receptionist.html','Receptionist & booking plan','See the intended voice conversation, appointment request and callback process, including what still needs a live connection.','receptionist-preview.jpg'],
  ['operations-intro.html','Before you open the internal tools','Meet the installer, operator and engineering roles. See what each screen records before you enter the internal app.','operations-preview.png'],
  ['field.html','GIS map & dispatch app','Leave the customer experience and enter the crew workspace. Explore the job queue, dispatch/on-site maps, handoffs and engineering record tabs.','operations-preview.png'],
  ['workspace.html','Job records & property data','Open the office workspace behind the map: customer requests, property details, history, documents and published GIS lookups.','records-preview.jpg'],
  ['gis-plan.html','Mapping, tracking & customer updates','Understand the public map layers, vehicle choices and notification rules behind the field app. The interactive map illustrates the plan.','operations-preview.png'],
  ['system.html','How the complete system runs','See what I would manage, where records live, how the services connect and which production connections come next.','records-preview.jpg'],
  ['siteworks.html','Siteworks customer concept','Explore the proposed separate service path for grading, clearing and site preparation, including a fictional inquiry preview.','siteworks-preview.jpg'],
  ['proposal.html?chapter=1','Proposal · the overall arrangement','Begin the business discussion: your Engineering business, my contribution and the proposed separate Siteworks company.','proposal-preview.png'],
  ['proposal.html?chapter=2','Proposal · Engineering assignments','Review the initial assignment scope, the four-hour base, additional time and separate backfill return. Try the compensation examples.','proposal-preview.png'],
  ['proposal.html?chapter=3','Proposal · Siteworks ownership','Review the proposed ownership, operating roles, startup contribution and relationship with your installer partner.','siteworks-preview.jpg'],
  ['proposal.html?chapter=4','Proposal · job economics','Try the calculator: labor and equipment first, then expenses and retained cash, followed by the proposed profit distribution.','economics-preview.jpg'],
  ['proposal.html?chapter=5','Proposal · brand & growth','See the marketing work I would manage, the advertising costs paid by each business and the veteran-contracting opportunities to prepare for.','customer-preview.png'],
  ['proposal.html?chapter=6','Proposal · digital operating role','Review how I would build and manage the website, receptionist, field tools, customer updates and business records around your workflow.','records-preview.jpg'],
  ['proposal.html?chapter=7','Proposal · customer process & decisions','Review the separate-company disclosure and example additional-work authorization, then the decisions needed before starting.','work-order-preview.jpg'],
  ['platform.html','Digital scope & service offers','Compare the company-building and standalone arrangements, included service work and separate provider or advertising expenses.','platform-preview.jpg'],
  ['review-complete.html','Review complete · next decisions','Bring the operating plan and proposal together. Note the questions and decisions we should work through before launch.','proposal-preview.png']
 ];
 const path=location.pathname.split('/').pop()||'index.html';
 const host=document.querySelector('main');if(!host)return;
 const chapter=()=>{
  const anchors={'engineering-pay':2,'siteworks-ownership':3,siteworks:3,'siteworks-pay':4,growth:5,'digital-system':6,'separate-company-disclosure':7,'work-order-demo':7};
  return anchors[location.hash.slice(1)]||Math.max(1,Math.min(7,parseInt(new URLSearchParams(location.search).get('chapter'),10)||1));
 };
 const current=()=>path==='proposal.html'?8+chapter():steps.findIndex(s=>s[0].split('?')[0]===path);
 const getProject=()=>{try{return new URLSearchParams(location.search).get('project')||localStorage.getItem('ns-review-last-project')||'';}catch(e){return '';}};
 function href(index){
  const target=steps[index][0],id=getProject();
  return /^(field|workspace)\.html$/.test(target)&&/^[A-Za-z0-9_-]{1,100}$/.test(id)?target+'?project='+encodeURIComponent(id):target;
 }
 const tour=document.createElement('section');tour.className='ns-tour';tour.setAttribute('aria-label','Guided project review');host.append(tour);document.body.classList.add('ns-guided-review');
 let baseContext=document.querySelector('.ns-review-context')?.textContent||'';
 function render(){
  const index=current();
  if(index<0||document.body.dataset.mode==='live'){
   tour.innerHTML='<div class="ns-tour-kicker">OPTIONAL REFERENCE</div><h2>Continue the guided project review.</h2><p>This screen is a separate reference or protected staff entry. The public walkthrough stays available without staff sign-in.</p><div class="ns-tour-buttons"><a class="ns-tour-back" href="index.html">← Review home</a><a class="ns-tour-next" href="workspace.html">Open the job-records demo →</a></div>';return;
  }
  const next=index===steps.length-1?0:index+1,previous=Math.max(0,index-1),isLast=index===steps.length-1;
  tour.innerHTML=`<div class="ns-tour-progress"><span>YOU ARE HERE · STEP ${index+1} OF ${steps.length}</span><b>${steps[index][1]}</b></div><div class="ns-tour-track"><span style="width:${(index+1)/steps.length*100}%"></span></div><div class="ns-tour-up-next"><img src="assets/review/${steps[next][3]}" alt="Preview of ${steps[next][1]}" width="240" height="150" loading="lazy"><div><p class="ns-tour-kicker">${isLast?'REVIEW FINISHED':'COMING NEXT'}</p><h2>${isLast?'Return to the complete project hub.':steps[next][1]}</h2><p>${isLast?'You have reached the end of the guided review. You can revisit any screen, keep exploring the tools or use the decisions above for our discussion.':steps[next][2]}</p></div></div><div class="ns-tour-buttons"><a class="ns-tour-back" href="${index?href(previous):'index.html'}">← ${index?'Back: '+steps[previous][1]:'Review home'}</a><a class="ns-tour-next" href="${href(next)}">${isLast?'Return to review home':'Next: '+steps[next][1]} →</a></div><details class="ns-tour-contents"><summary>Jump to any step</summary><ol>${steps.map((s,i)=>`<li><a href="${href(i)}" ${i===index?'aria-current="step"':''}>${s[1]}</a></li>`).join('')}</ol></details>`;
  const context=document.querySelector('.ns-review-context');if(context)context.textContent=baseContext+' · '+(index+1)+'/'+steps.length;
  try{localStorage.setItem('ns-review-tour-step',String(index));if(index>0)localStorage.setItem('ns-review-tour-resume',String(index));}catch(e){}
 }
 window.addEventListener('ns-proposal-chapter',render);window.addEventListener('ns-review-job',render);window.addEventListener('popstate',render);window.addEventListener('hashchange',render);render();
 document.querySelectorAll('[data-tour-resume]').forEach(a=>{
  let index=0;try{index=Math.max(0,Math.min(steps.length-1,Number(localStorage.getItem('ns-review-tour-resume'))||0));}catch(e){}
  if(index){a.href=href(index);a.hidden=false;}
 });
 // Remember the last visited step without changing which page opens as the review home.
 if(path!=='index.html'&&current()>=0&&document.body.dataset.mode!=='live')try{localStorage.setItem('ns-review-tour-resume',String(current()));}catch(e){}
})();
