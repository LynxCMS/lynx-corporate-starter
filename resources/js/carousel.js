/*
 * Carousels: a horizontal track of slides that snap into place. Without JavaScript the track still
 * scrolls and swipes; this adds the previous and next buttons, the dots and, when the section asks
 * for it, turning by itself. Turning stops while the pointer or the keyboard is in the carousel,
 * and never starts for visitors who prefer reduced motion.
 *
 * <section data-carousel data-autoplay="7000">
 *     <div data-carousel-track> <article data-carousel-slide>…</article> … </div>
 *     <button data-carousel-prev> <button data-carousel-next> <button data-carousel-dot="0"> …
 * </section>
 */
const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');

function setUp(carousel) {
    const track = carousel.querySelector('[data-carousel-track]');
    const slides = [...carousel.querySelectorAll('[data-carousel-slide]')];
    const dots = [...carousel.querySelectorAll('[data-carousel-dot]')];

    if (!track || slides.length < 2) {
        return;
    }

    let current = 0;
    let timer = null;

    const show = (index) => {
        const target = slides[(index + slides.length) % slides.length];
        track.scrollTo({ left: target.offsetLeft - track.offsetLeft, behavior: reducedMotion.matches ? 'auto' : 'smooth' });
    };

    const mark = (index) => {
        current = index;
        dots.forEach((dot, i) => dot.setAttribute('aria-current', i === index ? 'true' : 'false'));
        carousel.dataset.current = String(index);
    };

    const visible = new IntersectionObserver((entries) => {
        entries.filter((entry) => entry.isIntersecting).forEach((entry) => mark(slides.indexOf(entry.target)));
    }, { root: track, threshold: 0.6 });
    slides.forEach((slide) => visible.observe(slide));

    carousel.querySelector('[data-carousel-prev]')?.addEventListener('click', () => show(current - 1));
    carousel.querySelector('[data-carousel-next]')?.addEventListener('click', () => show(current + 1));
    dots.forEach((dot) => dot.addEventListener('click', () => show(Number(dot.dataset.carouselDot))));
    carousel.querySelectorAll('[data-carousel-controls]').forEach((controls) => controls.removeAttribute('hidden'));

    const delay = Number(carousel.dataset.autoplay);
    const stop = () => window.clearInterval(timer);
    const start = () => {
        stop();

        if (delay > 0 && !reducedMotion.matches) {
            timer = window.setInterval(() => show(current + 1), delay);
        }
    };

    carousel.addEventListener('pointerenter', stop);
    carousel.addEventListener('pointerleave', start);
    carousel.addEventListener('focusin', stop);
    carousel.addEventListener('focusout', (event) => carousel.contains(event.relatedTarget) || start());
    start();
}

document.querySelectorAll('[data-carousel]').forEach(setUp);
