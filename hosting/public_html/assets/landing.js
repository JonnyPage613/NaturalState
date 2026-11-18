'use strict';
const form=document.getElementById('landing-inquiry');
function roleFields(){document.getElementById('lot-count').hidden=form.elements.role.value!=='Builder / developer'}
form.elements.role.addEventListener('change',roleFields);
document.querySelectorAll('[data-service],[data-role]').forEach(a=>a.addEventListener('click',()=>{if(a.dataset.service)form.elements.service.value=a.dataset.service;if(a.dataset.role){form.elements.role.value=a.dataset.role;roleFields()}}));
form.addEventListener('submit',e=>{e.preventDefault();if(!form.reportValidity())return;const target=document.getElementById('inquiry-result');target.replaceChildren();const strong=document.createElement('strong');strong.textContent='Your example request is ready for team review.';const p=document.createElement('p');p.textContent=form.elements.service.value+' · '+form.elements.county.value+' County. In the live service, the team would review the property details, confirm the scope and follow up about availability. Nothing was sent or booked in this preview.';target.append(strong,p);target.scrollIntoView({behavior:'smooth',block:'nearest'})});
