(()=>{
  const root=document.documentElement;
  const themeButton=document.getElementById('themeButton');
  const saved=localStorage.getItem('accesshub-theme');
  const preferredDark=window.matchMedia?.('(prefers-color-scheme: dark)').matches;
  const initial=saved || (preferredDark ? 'dark' : 'light');
  root.dataset.theme=initial;

  function syncThemeButton(){
    if(!themeButton)return;
    const dark=root.dataset.theme==='dark';
    themeButton.textContent=dark?'☀':'◐';
    themeButton.title=dark?'Switch to light mode':'Switch to dark mode';
    themeButton.setAttribute('aria-label',themeButton.title);
    themeButton.setAttribute('aria-pressed',String(dark));
  }
  syncThemeButton();
  themeButton?.addEventListener('click',()=>{
    const next=root.dataset.theme==='dark'?'light':'dark';
    root.dataset.theme=next;
    localStorage.setItem('accesshub-theme',next);
    syncThemeButton();
  });

  const toggle=document.getElementById('filterToggle'), panel=document.getElementById('filterPanel');
  toggle?.addEventListener('click',e=>{e.stopPropagation();panel?.classList.toggle('open')});
  document.addEventListener('click',e=>{if(panel&&!panel.contains(e.target)&&e.target!==toggle)panel.classList.remove('open')});

  const search=document.getElementById('tableSearch');
  const checks=['mineOnly','missingApprover','editableOnly'].map(id=>document.getElementById(id));
  function filterRows(){
    const term=(search?.value||'').toLowerCase();
    document.querySelectorAll('#recordsTable tbody tr').forEach(row=>{
      const text=row.textContent.toLowerCase();
      const mine=!checks[0]?.checked||row.dataset.mine==='1';
      const missing=!checks[1]?.checked||row.dataset.missing==='1';
      const editable=!checks[2]?.checked||row.dataset.editable==='1';
      row.hidden=!(text.includes(term)&&mine&&missing&&editable);
    });
  }
  search?.addEventListener('input',filterRows);
  checks.forEach(c=>c?.addEventListener('change',filterRows));

  document.querySelectorAll('.note-btn.edit').forEach(btn=>btn.addEventListener('click',()=>{
    const row=btn.closest('tr');
    row.querySelector('.note-view').hidden=true;
    row.querySelector('.note-form').hidden=false;
    btn.hidden=true;
  }));
  document.querySelectorAll('.note-btn.cancel').forEach(btn=>btn.addEventListener('click',()=>{
    const row=btn.closest('tr');
    row.querySelector('.note-view').hidden=false;
    row.querySelector('.note-form').hidden=true;
    row.querySelector('.note-btn.edit').hidden=false;
  }));

  document.querySelectorAll('.sort-btn').forEach(btn=>btn.addEventListener('click',()=>{
    const tbody=document.querySelector('#recordsTable tbody');
    if(!tbody)return;
    const asc=btn.dataset.dir!=='asc';
    btn.dataset.dir=asc?'asc':'desc';
    btn.textContent=asc?'↑':'↓';
    document.querySelectorAll('.sort-btn').forEach(other=>{if(other!==btn)other.classList.remove('active')});
    btn.classList.add('active');
    const rows=[...tbody.querySelectorAll('tr')].sort((a,b)=>{
      const aa=a.dataset.name||'',bb=b.dataset.name||'';
      return asc?aa.localeCompare(bb):bb.localeCompare(aa);
    });
    rows.forEach(r=>tbody.appendChild(r));
  }));

  const toast=document.getElementById('statusToast');
  if(toast)setTimeout(()=>toast.classList.add('hide'),3300);
})();
