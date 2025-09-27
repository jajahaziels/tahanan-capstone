// sidebar.js (or script.js) — sidebar toggle + live search with smooth hide/show

// ---- sidebar toggle (keeps state across pages) ----
const sidebar = document.querySelector('.sidebar');
const toggle = document.querySelector('.toggle');

if (sidebar && toggle) {
  // restore state
  if (localStorage.getItem('sidebarState') === 'collapsed') {
    sidebar.classList.add('collapsed');
  }

  toggle.addEventListener('click', () => {
    sidebar.classList.toggle('collapsed');
    localStorage.setItem(
      'sidebarState',
      sidebar.classList.contains('collapsed') ? 'collapsed' : 'expanded'
    );
  });
}

// ---- live search for listing cards ----
(function() {
  const input = document.getElementById('searchInput'); // matches updated id
  if (!input) return;

  const cardsContainer = document.querySelector('.card-grid');
  const cards = Array.from(document.querySelectorAll('.listing-card'));
  const FADE_MS = 220; // match CSS transition duration

  // helper: hide element with fade then set display:none
  function hideCard(card) {
    if (card.classList.contains('is-hidden')) return;
    // start fade
    card.classList.add('is-hidden');
    // after transition, remove from layout
    setTimeout(() => {
      card.style.display = 'none';
    }, FADE_MS);
  }

  // helper: show element (set display then remove hidden class to fade in)
  function showCard(card) {
    if (!card.classList.contains('is-hidden') && card.style.display !== 'none') return;
    // make it occupy layout space first
    card.style.display = '';
    // force reflow so transition runs
    void card.offsetWidth;
    // remove hidden class to fade back in
    card.classList.remove('is-hidden');
  }

  // search function
  function filterCards(query) {
    const q = query.trim().toLowerCase();
    cards.forEach(card => {
      // pick what to search: title, details, price — adjust selector if needed
      const title = card.querySelector('.card-body h3')?.innerText || '';
      const details = card.querySelector('.card-body .details')?.innerText || '';
      const price = card.querySelector('.card-body .price')?.innerText || '';
      const text = (title + ' ' + details + ' ' + price).toLowerCase();

      if (q === '' || text.includes(q)) {
        showCard(card);
      } else {
        hideCard(card);
      }
    });
  }

  // debounce helper
  function debounce(fn, wait) {
    let t;
    return function(...args) {
      clearTimeout(t);
      t = setTimeout(() => fn.apply(this, args), wait);
    };
  }

  const onInput = debounce((e) => {
    filterCards(e.target.value);
  }, 120);

  input.addEventListener('input', onInput);

  // initial state: ensure all cards are visible (in case some were display:none)
  cards.forEach(c => { c.style.display = ''; c.classList.remove('is-hidden'); });
})();
