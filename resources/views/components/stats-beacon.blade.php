@props(['page'])
{{--
    Visitor counts (app/Stats): tells the site once the page has been visible for a second, and when
    a visitor clicks an element with data-stats-event or opens a <details> that has one
    (data-stats-subject names what, optionally). The same for every visitor, so cached pages keep it.
    It sets nothing in the browser; the site keeps daily totals only.
--}}
@if ($page?->id)
<script data-stats-beacon="{{ $page->id }}">
(() => {
    const url = @js(route('stats.hit', absolute: false));
    const page = @js((string) $page->id);
    const send = (event, subject = '') => navigator.sendBeacon?.(url, new URLSearchParams({ page, event, subject }));
    const view = () => {
        let timer = null;
        const arm = () => {
            if (document.visibilityState === 'visible' && timer === null) {
                timer = setTimeout(() => { send('view'); document.removeEventListener('visibilitychange', arm); }, 1000);
            } else if (document.visibilityState !== 'visible' && timer !== null) {
                clearTimeout(timer);
                timer = null;
            }
        };
        document.addEventListener('visibilitychange', arm);
        arm();
    };
    document.prerendering ? document.addEventListener('prerenderingchange', view, { once: true }) : view();
    document.addEventListener('click', (event) => {
        const element = event.target.closest?.('[data-stats-event]');
        if (element && element.tagName !== 'DETAILS') send(element.dataset.statsEvent, element.dataset.statsSubject ?? '');
    }, true);
    // toggle doesn't bubble, so it is caught on its way down.
    document.addEventListener('toggle', (event) => {
        const element = event.target;
        if (element.tagName === 'DETAILS' && element.open && element.dataset.statsEvent) send(element.dataset.statsEvent, element.dataset.statsSubject ?? '');
    }, true);
})();
</script>
@endif
