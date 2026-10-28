/*
 * Search: opening a result tells the site, so popular pages rise in the search panel's
 * suggestions. sendBeacon still delivers when the browser leaves for the result, and the
 * result itself may come from the page cache without running PHP. Opening the panel puts the
 * cursor in its box, and Escape closes it.
 */
document.addEventListener('click', (event) => {
    const hit = event.target.closest('[data-search-hit]');

    if (hit && event.target.closest('a')) {
        const body = new FormData();
        body.append('page', hit.dataset.searchHit);
        navigator.sendBeacon('/search/hit', body);
    }
});

const panel = document.querySelector('[data-menu="search"]');

panel?.addEventListener('toggle', () => {
    if (panel.open) {
        panel.querySelector('[data-search-input]')?.focus();
    }
});

document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && panel?.open) {
        panel.open = false;
        panel.querySelector('summary')?.focus();
    }
});
