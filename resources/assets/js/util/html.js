export function escapeHtml(value) {
    if (value === null || value === undefined) {
        return '';
    }

    return String(value)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

export function decodeEntities(value) {
    if (value === null || value === undefined) {
        return '';
    }

    const str = String(value);

    if (!str.includes('&') || typeof document === 'undefined') {
        return str;
    }

    const el = document.createElement('textarea');
    el.innerHTML = str;

    return el.value;
}

export function htmlToText(value) {
    if (value === null || value === undefined) {
        return '';
    }

    const doc = new DOMParser().parseFromString(String(value), 'text/html');

    return doc.body ? doc.body.textContent || '' : '';
}

export function renderCustomEmoji(emoji) {
    const shortcode = escapeHtml(emoji.shortcode);
    const url = escapeHtml(emoji.url);
    const staticUrl = escapeHtml(emoji.static_url);

    return `<img draggable="false" class="emojione custom-emoji" alt="${shortcode}" title="${shortcode}" src="${url}" data-original="${url}" data-static="${staticUrl}" width="16" height="16" onerror="this.onerror=null;this.src='/storage/emoji/missing.png';" />`;
}

export function renderDisplayName(name, emojis) {
    const html = escapeHtml(decodeEntities(name));

    if (!html.includes(':') || !Array.isArray(emojis) || !emojis.length) {
        return html;
    }

    return html.replace(/:(\w+):/g, (match, shortcode) => {
        const emoji = emojis.find(e => e && e.shortcode === shortcode);

        return emoji ? renderCustomEmoji(emoji) : match;
    });
}
