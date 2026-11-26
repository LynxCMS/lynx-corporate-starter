/*
 * The resources list on a static export. The PHP site renders each sort and page itself from
 * ?sort= and ?page=. A static host ignores the query and always serves Trending lately's first
 * page, so when the address asks for something else, this fetches the cards file (every sort's
 * order and every card, in the page's language) and shows the sort and page asked for. On the
 * PHP site the address and the page always agree, and nothing is fetched.
 */
const listing = document.querySelector('[data-listing]');

if (listing) {
    show(listing).finally(() => delete listing.dataset.pending);
}

async function show(listing) {
    const query = new URLSearchParams(location.search);
    const sort = query.get('sort') ?? listing.dataset.defaultSort;
    const page = Number(query.get('page') ?? '1');

    if (sort === listing.dataset.shownSort && String(page) === listing.dataset.shownPage) {
        return;
    }

    let data;

    try {
        const response = await fetch(listing.dataset.cardsUrl, { headers: { Accept: 'application/json' } });
        data = response.ok ? await response.json() : null;
    } catch {
        data = null;
    }

    const order = data?.orders?.[sort];

    // No file, or a sort it doesn't have: the first page stays, as the server rendered it.
    if (!Array.isArray(order)) {
        return;
    }

    const last = Math.max(1, Math.ceil(order.length / data.per_page));
    const shown = Number.isInteger(page) && page >= 1 && page <= last ? page : 1;
    const start = (shown - 1) * data.per_page;

    listing.querySelector('[data-list]').innerHTML = order.slice(start, start + data.per_page).map((id) => data.cards[id] ?? '').join('');
    listing.querySelector('[data-featured]')?.toggleAttribute('hidden', shown > 1);
    markSort(listing.querySelector('[data-sorts]'), sort);
    updatePager(listing.querySelector('[data-pager]'), sort, shown, last, listing.dataset.defaultSort);
}

function markSort(nav, sort) {
    const active = nav.dataset.activeClass.split(' ');
    const idle = nav.dataset.idleClass.split(' ');

    nav.querySelectorAll('[data-sort]').forEach((link) => {
        const isActive = link.dataset.sort === sort;
        link.classList.remove(...(isActive ? idle : active));
        link.classList.add(...(isActive ? active : idle));
        isActive ? link.setAttribute('aria-current', 'true') : link.removeAttribute('aria-current');
    });
}

function updatePager(pager, sort, page, last, defaultSort) {
    if (!pager) {
        return;
    }

    const status = pager.querySelector('[data-page]');
    status.dataset.page = String(page);
    status.dataset.last = String(last);
    status.textContent = status.dataset.pageOf.replace(':page', String(page)).replace(':last', String(last));

    [[pager.querySelector('[data-pager-previous]'), page - 1], [pager.querySelector('[data-pager-next]'), page + 1]].forEach(([link, target]) => {
        const isOutside = target < 1 || target > last;
        link.classList.toggle('invisible', isOutside);
        link.href = address(sort, Math.min(Math.max(target, 1), last), defaultSort);
        link.dataset.pageLink = String(target);
    });
}

// As the template builds its links: the default sort and page 1 stay out of the address.
function address(sort, page, defaultSort) {
    const query = new URLSearchParams();

    if (sort !== defaultSort) {
        query.set('sort', sort);
    }

    if (page !== 1) {
        query.set('page', String(page));
    }

    const search = query.toString();

    return search === '' ? location.pathname : `${location.pathname}?${search}`;
}
