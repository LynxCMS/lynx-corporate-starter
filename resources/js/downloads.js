/*
 * The download form remembers who sent it, in this browser's localStorage only: the next form
 * fills in their details and shows "Downloading as …", so a second download takes one click.
 * "Not you?" forgets them. The stored page never holds anyone's details, so it can be cached.
 * Storage can be refused (private windows, blocked site data): then the full form shows.
 */
const KEY = 'arcturus.download-contact';

function read() {
    try {
        const contact = JSON.parse(localStorage.getItem(KEY) ?? 'null');

        return contact && typeof contact === 'object' && contact.email ? contact : null;
    } catch {
        return null;
    }
}

function write(contact) {
    try {
        contact === null ? localStorage.removeItem(KEY) : localStorage.setItem(KEY, JSON.stringify(contact));
    } catch {
        // Nowhere to keep it: the visitor fills the form again next time.
    }
}

document.addEventListener('alpine:init', () => {
    window.Alpine.data('downloadContact', (fields) => ({
        known: false,
        name: '',
        contact: null,

        init() {
            this.contact = read();

            if (this.contact === null) {
                return;
            }

            this.fill();
            this.name = `${this.contact.first_name ?? ''} ${this.contact.last_name ?? ''}`.trim();
            this.known = true;
        },

        // The remembered details go out with the submit itself: values set while an island is
        // still starting can be dropped, and these must reach the server.
        send() {
            if (this.known && this.contact !== null) {
                this.fill();
            }

            this.$wire.submit();
        },

        fill() {
            fields.forEach((field) => this.$wire.set(`data.${field}`, this.contact[field] ?? '', false));
        },

        remember() {
            write(Object.fromEntries(fields.map((field) => [field, this.$wire.get(`data.${field}`) ?? ''])));
        },

        forget() {
            write(null);
            this.contact = null;
            fields.forEach((field) => this.$wire.set(`data.${field}`, '', false));
            this.known = false;
        },

        start(url) {
            window.location.assign(url);
        },
    }));
});
