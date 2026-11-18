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

        init() {
            const contact = read();

            if (contact === null) {
                return;
            }

            fields.forEach((field) => this.$wire.set(`data.${field}`, contact[field] ?? '', false));
            this.name = `${contact.first_name ?? ''} ${contact.last_name ?? ''}`.trim();
            this.known = true;
        },

        remember() {
            write(Object.fromEntries(fields.map((field) => [field, this.$wire.get(`data.${field}`) ?? ''])));
        },

        forget() {
            write(null);
            fields.forEach((field) => this.$wire.set(`data.${field}`, '', false));
            this.known = false;
        },

        start(url) {
            window.location.assign(url);
        },
    }));
});
