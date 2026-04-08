/* toast */
function toast(msg, type = 'success') {
  const t = document.createElement('div');
  t.className = 'alert ' + (type === 'success' ? 'success' : 'error');
  t.style.position = 'fixed';
  t.style.right = '16px';
  t.style.bottom = '16px';
  t.style.zIndex = '9999';
  t.textContent = msg;
  document.body.appendChild(t);
  setTimeout(() => t.remove(), 2500);
}

// Mobile nav toggle
(function(){
  const btn = document.querySelector('[data-nav-toggle]');
  if (!btn) return;
  btn.addEventListener('click', () => {
    document.body.classList.toggle('nav-open');
  });
  document.addEventListener('click', (e) => {
    if (!document.body.classList.contains('nav-open')) return;
    const nav = document.getElementById('siteNav');
    if (!nav) return;
    if (nav.contains(e.target) || btn.contains(e.target)) return;
    document.body.classList.remove('nav-open');
  });
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') document.body.classList.remove('nav-open');
  });
})();

/* Profile dropdown */
document.addEventListener('click', (e) => {
  const menu = e.target.closest('.profile-menu');
  document.querySelectorAll('.profile-menu').forEach(m => { if (m !== menu) m.classList.remove('open'); });
  if (menu && (e.target.closest('.profile-btn') || e.target.classList.contains('profile-btn'))) menu.classList.toggle('open');
});

/* Search autocomplete */
(function(){
  const inp = document.querySelector('[data-search]');
  if(!inp) return;
  const list = document.createElement('div'); list.className='autocomplete-list'; list.style.display='none';
  inp.parentElement.style.position='relative';
  inp.parentElement.appendChild(list);

  let t=null;
  inp.addEventListener('input', ()=>{
    const q=inp.value.trim();
    clearTimeout(t);
    if(q.length<2){ list.style.display='none'; return; }
    t=setTimeout(async ()=>{
      try{
        const res = await fetch(inp.dataset.suggest+'?q='+encodeURIComponent(q));
        const items = await res.json();
        list.innerHTML='';
        items.forEach(row=>{
          const el=document.createElement('div'); el.className='autocomplete-item';
          el.innerHTML=`<span>${row.name} <small>(${row.brand}${row.sku?(' · '+row.sku):''})</small></span><span class="muted">RFQ</span>`;
          el.addEventListener('click', ()=>{ location.href=row.url; });
          list.appendChild(el);
        });
        list.style.display = items.length ? 'block':'none';
      }catch(err){
        list.style.display='none';
      }
    }, 180);
  });
  document.addEventListener('click',(e)=>{ if(!inp.parentElement.contains(e.target)){ list.style.display='none'; }});
})();

/* product: thumbs + tabs */
(function(){
  const main = document.getElementById('mainImg');
  document.querySelectorAll('.thumb').forEach(t=>{
    t.addEventListener('click',()=>{
      document.querySelectorAll('.thumb').forEach(x=>x.classList.remove('active'));
      t.classList.add('active');
      if(main) main.src = (t.dataset.src || t.dataset.img || t.getAttribute('data-img') || t.getAttribute('data-src') || '');
    });
  });
  const tabs = document.querySelectorAll('.tab');
  tabs.forEach(btn=>{
    btn.addEventListener('click',()=>{
      tabs.forEach(x=>x.classList.remove('active'));
      document.querySelectorAll('.pane').forEach(p=>p.classList.remove('show'));
      btn.classList.add('active');
      const pane = document.getElementById('tab-'+btn.dataset.tab);
      if (pane) pane.classList.add('show');
    });
  });
})();

// Reveal animations
(function(){
  const els = document.querySelectorAll('.reveal');
  if (!els.length) return;
  // Older mobile/tablet browsers can miss IntersectionObserver. In that case,
  // simply show everything (no reveal animations).
  if (!('IntersectionObserver' in window)) {
    els.forEach(el => el.classList.add('in'));
    return;
  }
  const io = new IntersectionObserver((entries)=>{
    entries.forEach(en=>{
      if(en.isIntersecting){
        en.target.classList.add('in');
        io.unobserve(en.target);
      }
    });
  }, { threshold: 0.12 });
  els.forEach(el=>io.observe(el));
})();

// Back to top
(function(){
  const btn = document.querySelector('[data-to-top]');
  if (!btn) return;
  const onScroll = () => {
    if (window.scrollY > 500) btn.classList.add('show'); else btn.classList.remove('show');
  };
  window.addEventListener('scroll', onScroll, { passive: true });
  onScroll();
  btn.addEventListener('click', () => window.scrollTo({ top: 0, behavior: 'smooth' }));
})();
