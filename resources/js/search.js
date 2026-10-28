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
const DELAY = 250;
const MIN_LENGTH = 2;

/*
 * Results as the visitor types: after a short pause, the panel asks /search/results for the first
 * matches and shows them under the field; clearing the field hides them. A newer keystroke cancels the request before it, so results never arrive out of order.
 */
if (panel) {
    const input = panel.querySelector('[data-search-input]');
    const target = panel.querySelector('[data-live-target]');
    let timer = null;
    let request = null;

    const clear = () => {
        target.hidden = true;
        target.innerHTML = '';
    };

    input?.addEventListener('input', () => {
        window.clearTimeout(timer);
        request?.abort();
        const query = input.value.trim();

        if (query.length < MIN_LENGTH) {
            clear();

            return;
        }

        timer = window.setTimeout(async () => {
            request = new AbortController();

            try {
                const url = `${panel.dataset.resultsUrl}?lang=${panel.dataset.lang}&q=${encodeURIComponent(query)}`;
                const response = await fetch(url, { headers: { Accept: 'text/html' }, signal: request.signal });

                if (response.ok) {
                    target.innerHTML = await response.text();
                    target.hidden = false;
                }
            } catch {
                // Cancelled by a newer keystroke, or offline: the form still opens the Search page.
            }
        }, DELAY);
    });
}

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
