(() => {
 'use strict';
 const chapters=[['Overview','The business proposal'],['Engineering','Engineering field compensation'],['Siteworks','A separate Siteworks business'],['Job economics','Labor, equipment & job economics'],['Growth','Brand, marketing & growth'],['Digital system','The platform I would build and manage'],['Customer terms','Additional-work process & next decisions']];
 const groups=[...document.querySelectorAll('[data-proposal-chapter]')];if(!groups.length)return;
 const anchorChapter={'engineering-pay':2,'siteworks-ownership':3,'siteworks':3,'siteworks-pay':4,growth:5,'digital-system':6,'separate-company-disclosure':7,'work-order-demo':7};
 const tools=document.createElement('div');tools.className='ns-chapter-tools';
 tools.innerHTML='<div class="ns-chapter-progress"><span id="ns-chapter-count"></span><span>Partner review · Proposed terms</span></div><div class="ns-chapter-track"><span id="ns-chapter-fill"></span></div><div class="ns-chapter-links">'+chapters.map(([label],i)=>`<a href="proposal.html?chapter=${i+1}" data-chapter="${i+1}">${i+1}. ${label}</a>`).join('')+'</div><label class="ns-chapter-select-label"><span class="sr-only">Proposal chapter</span><select class="ns-chapter-select" aria-label="Proposal chapter">'+chapters.map(([label],i)=>`<option value="${i+1}">${i+1} of 7 · ${label}</option>`).join('')+'</select></label>';
 document.querySelector('main').prepend(tools);
 const footer=document.createElement('div');footer.innerHTML='<div class="ns-chapter-next" id="ns-next-description"></div><div class="ns-chapter-footer"><a id="ns-chapter-back"></a><a id="ns-chapter-forward"></a></div>';document.querySelector('main').append(footer);
 function requested(){return anchorChapter[location.hash.slice(1)]||Math.max(1,Math.min(7,parseInt(new URLSearchParams(location.search).get('chapter'),10)||1));}
 function show(n,scroll=false){
  n=Math.max(1,Math.min(7,n));groups.forEach(g=>g.hidden=Number(g.dataset.proposalChapter)!==n);
  document.getElementById('ns-chapter-count').textContent='CHAPTER '+n+' OF 7';
  document.getElementById('ns-chapter-fill').style.width=(n/7*100)+'%';
  tools.querySelectorAll('[data-chapter]').forEach(a=>{if(Number(a.dataset.chapter)===n)a.setAttribute('aria-current','step');else a.removeAttribute('aria-current');});
  tools.querySelector('select').value=String(n);
  document.querySelector('h1').textContent=n===1?'Engineering support & Siteworks partnership':chapters[n-1][1];
  document.querySelector('.page-head .page-explanation').hidden=n!==1;
  document.querySelector('.page-head .actions').hidden=n!==1;
  const back=document.getElementById('ns-chapter-back'),next=document.getElementById('ns-chapter-forward');
  back.href=n===1?'index.html':'proposal.html?chapter='+(n-1);back.textContent=n===1?'← Review home':'← '+chapters[n-2][0];
  next.href=n===7?'platform.html#pricing':'proposal.html?chapter='+(n+1);next.textContent=n===7?'Compare digital service offers ↗':chapters[n][0]+' →';
  document.getElementById('ns-next-description').textContent=n===7?'Next: compare the two digital-service arrangements, then agree the scope and initial assignments.':'Coming next: '+chapters[n][1]+'.';
  document.title='Natural State proposal · '+chapters[n-1][0];window.dispatchEvent(new CustomEvent('ns-proposal-chapter',{detail:{chapter:n}}));
  if(scroll)document.querySelector('.ns-chapter-tools').scrollIntoView({behavior:'instant',block:'start'});
 }
 document.addEventListener('click',event=>{
  const a=event.target.closest('a');if(!a||event.ctrlKey||event.metaKey||event.shiftKey||event.altKey||a.target==='_blank')return;
  const url=new URL(a.href,location.href);if(url.origin!==location.origin||!url.pathname.endsWith('/proposal.html')||!url.searchParams.has('chapter'))return;
  event.preventDefault();history.pushState(null,'',url.href);show(requested(),true);
 });
 tools.querySelector('select').onchange=e=>{history.pushState(null,'','proposal.html?chapter='+e.target.value);show(requested(),true);};
 window.addEventListener('popstate',()=>show(requested(),true));window.addEventListener('hashchange',()=>show(requested()));show(requested());
})();
