(() => {
  'use strict';
  const form = document.getElementById('work-order-form');
  const receipt = document.getElementById('work-order-receipt');
  const output = document.getElementById('work-order-output');
  if (!form || !receipt || !output) return;
  const labels = ['Separate provider', 'Contractor choice', 'Separate scope and billing', 'Scope and price'];
  form.addEventListener('submit', event => {
    event.preventDefault();
    if (!form.reportValidity()) return;
    const data = new FormData(form);
    for (const name of ['customer', 'address', 'scope', 'signature']) {
      const input = form.elements.namedItem(name);
      input.setCustomValidity(String(data.get(name)).trim() ? '' : 'Please enter a value.');
      if (!input.reportValidity()) return;
    }
    output.replaceChildren();
    const add = (label, value) => {
      const p = document.createElement('p');
      const b = document.createElement('b');
      b.textContent = label + ': ';
      p.append(b, document.createTextNode(value)); output.append(p);
    };
    add('Provider', 'Natural State Siteworks — proposed company');
    add('Customer', data.get('customer').trim());
    add('Property', data.get('address').trim());
    add('Scope', data.get('scope').trim());
    add('Agreed flat price', new Intl.NumberFormat('en-US', {style:'currency',currency:'USD'}).format(Number(data.get('price'))));
    add('Requested work date', data.get('date') + ' — subject to scheduling and scope checks');
    form.querySelectorAll('.order-ack label').forEach((label, i) => add(labels[i] + ' — acknowledged', label.querySelector('span').textContent));
    add('Typed name — demonstration only', data.get('signature').trim());
    add('Preview created', new Date().toLocaleString('en-US', {timeZone:'America/Chicago',timeZoneName:'short'}) + ' (America/Chicago)');
    form.hidden = true; receipt.hidden = false; receipt.scrollIntoView({behavior:'smooth',block:'start'});
  });
  form.addEventListener('input', event => {if (event.target.setCustomValidity) event.target.setCustomValidity('');});
  document.getElementById('edit-work-order').addEventListener('click', () => {receipt.hidden = true; form.hidden = false; form.elements.namedItem('customer').focus();});
  document.getElementById('print-work-order').addEventListener('click', () => window.print());
})();
