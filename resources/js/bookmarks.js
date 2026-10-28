/*
 * Bookmarks, kept in this browser's localStorage: newest first, at most MAX. The header's button
 * saves or removes the page, the badge counts them, and My bookmarks asks the site for their
 * cards. A list travels as ?items=, the ids in base64url; opening such a link shows that list
 * with a button to save it. Storage can be refused (private windows, blocked site data): then
 * bookmarking simply does nothing.
 */
const KEY = 'arcturus.bookmarks';
const MAX = 50;

function read() {
    try {
        const list = JSON.parse(localStorage.getItem(KEY) ?? '[]');

        return Array.isArray(list) ? list.filter((id) => Number.isInteger(id) && id > 0) : [];
    } catch {
        return [];
    }
}

function write(ids) {
    try {
        localStorage.setItem(KEY, JSON.stringify(ids.slice(0, MAX)));
    } catch {
        // Nowhere to keep it: the page stays as it was.
    }

    refreshHeader();
}

const encode = (ids) => btoa(ids.join(',')).replace(/\+/g, '-').replace(/\//g, '_').replace(/=+$/, '');

function decode(items) {
    try {
        return atob(items.replace(/-/g, '+').replace(/_/g, '/')).split(',').map(Number).filter((id) => Number.isInteger(id) && id > 0).slice(0, MAX);
    } catch {
        return [];
    }
}

function refreshHeader() {
    const ids = read();

    document.querySelectorAll('[data-bookmark-toggle]').forEach((button) => {
        const isSaved = ids.includes(Number(button.dataset.bookmarkToggle));
        button.hidden = false;
        button.setAttribute('aria-pressed', String(isSaved));
        button.dataset.labelAdd ??= button.getAttribute('aria-label');
        button.setAttribute('aria-label', isSaved ? button.dataset.labelSaved : button.dataset.labelAdd);
    });

    document.querySelectorAll('[data-bookmarks-count]').forEach((badge) => {
        badge.textContent = String(ids.length);
        badge.hidden = ids.length === 0;
    });
}

document.querySelectorAll('[data-bookmark-toggle]').forEach((button) => {
    button.addEventListener('click', () => {
        const id = Number(button.dataset.bookmarkToggle);
        const ids = read();
        write(ids.includes(id) ? ids.filter((saved) => saved !== id) : [id, ...ids]);
    });
});

refreshHeader();

const page = document.querySelector('[data-bookmarks-page]');

if (page) {
    const list = page.querySelector('[data-bookmarks-list]');
    const shared = page.querySelector('[data-bookmarks-shared]');
    const shareRow = page.querySelector('[data-bookmarks-share-row]');
    const shareUrl = page.querySelector('[data-bookmarks-share-url]');
    const sharedIds = decode(new URLSearchParams(location.search).get('items') ?? '');

    const show = async (ids, isShared) => {
        list.innerHTML = '';
        page.querySelector('[data-bookmarks-empty]').hidden = ids.length > 0;
        shared.hidden = ! isShared;
        shareRow.hidden = isShared || ids.length === 0;

        if (ids.length === 0) {
            return;
        }

        const response = await fetch(`${page.dataset.cardsUrl}?lang=${page.dataset.lang}&ids=${ids.join(',')}`, { headers: { Accept: 'text/html' } });
        list.innerHTML = response.ok ? await response.text() : '';
        list.querySelectorAll('[data-bookmark-remove]').forEach((button) => {
            button.hidden = isShared;
            button.addEventListener('click', () => {
                write(read().filter((id) => id !== Number(button.dataset.bookmarkRemove)));
                show(read(), false);
            });
        });
    };

    page.querySelector('[data-bookmarks-save-all]').addEventListener('click', () => {
        write([...new Set([...sharedIds, ...read()])]);
        history.replaceState(null, '', location.pathname);
        show(read(), false);
    });

    page.querySelector('[data-bookmarks-share]').addEventListener('click', async () => {
        shareUrl.value = `${location.origin}${location.pathname}?items=${encode(read())}`;
        shareUrl.hidden = false;
        shareUrl.select();

        try {
            await navigator.clipboard.writeText(shareUrl.value);
            page.querySelector('[data-bookmarks-copied]').hidden = false;
        } catch {
            // The link stays selected in the box for copying by hand.
        }
    });

    show(sharedIds.length > 0 ? sharedIds : read(), sharedIds.length > 0);
}
