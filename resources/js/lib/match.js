// Loose matching so "sprei diganti" finds "Ganti sprei".

const PREFIXES = ['ter', 'ber', 'di'];

export function normalize(text) {
    return text
        .toLowerCase()
        .replace(/[^\p{L}\p{N}]+/gu, ' ')
        .trim()
        .replace(/\s+/g, ' ');
}

function stem(word) {
    for (const prefix of PREFIXES) {
        if (word.length >= prefix.length + 4 && word.startsWith(prefix)) return word.slice(prefix.length);
    }
    return word;
}

function stems(text) {
    return normalize(text).split(' ').filter(Boolean).map(stem);
}

/** Activities whose name matches what the user is typing, best first. */
export function search(query, activities) {
    const q = stems(query);
    if (!q.length) return activities;

    return activities
        .map((activity) => {
            const words = stems(activity.name);
            const hits = q.filter((t) => words.some((w) => w.startsWith(t) || t.startsWith(w))).length;
            return { activity, score: hits / Math.max(q.length, words.length) };
        })
        .filter(({ score }) => (q.length === 1 ? score > 0 : score >= 0.5))
        .sort((a, b) => b.score - a.score)
        .map(({ activity }) => activity);
}

/** True when a new name is the same as an existing activity's name. */
export function isSameName(a, b) {
    return normalize(a) === normalize(b);
}
