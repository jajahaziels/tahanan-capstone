// ---- sidebar toggle (keeps state across pages) ----
const sidebar = document.querySelector('.sidebar');
const toggle  = document.querySelector('.toggle');

if (sidebar && toggle) {
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

// ---- active menu item — match current page URL ----
// Only adds active, never removes it, so PHP-set active classes are preserved
const currentPage = window.location.pathname.split('/').pop();
document.querySelectorAll('.sidebar a').forEach(link => {
    const href = link.getAttribute('href');
    if (href === currentPage) {
        link.classList.add('active');
    }
    // ← NO else/remove here — that was breaking the highlighting
});

// ---- live search for listing cards ----
(function() {
    const input = document.getElementById('searchInput');
    if (!input) return;

    const cards  = Array.from(document.querySelectorAll('.listing-card'));
    const FADE_MS = 220;

    function hideCard(card) {
        if (card.classList.contains('is-hidden')) return;
        card.classList.add('is-hidden');
        setTimeout(() => { card.style.display = 'none'; }, FADE_MS);
    }

    function showCard(card) {
        if (!card.classList.contains('is-hidden') && card.style.display !== 'none') return;
        card.style.display = '';
        void card.offsetWidth;
        card.classList.remove('is-hidden');
    }

    function filterCards(query) {
        const q = query.trim().toLowerCase();
        cards.forEach(card => {
            const title   = card.querySelector('.card-body h3')?.innerText   || '';
            const details = card.querySelector('.card-body .details')?.innerText || '';
            const price   = card.querySelector('.card-body .price')?.innerText   || '';
            const text    = (title + ' ' + details + ' ' + price).toLowerCase();
            (q === '' || text.includes(q)) ? showCard(card) : hideCard(card);
        });
    }

    function debounce(fn, wait) {
        let t;
        return function(...args) {
            clearTimeout(t);
            t = setTimeout(() => fn.apply(this, args), wait);
        };
    }

    input.addEventListener('input', debounce(e => filterCards(e.target.value), 120));
    cards.forEach(c => { c.style.display = ''; c.classList.remove('is-hidden'); });
})();