// localStorage wrapper that never throws (private mode, blocked storage).

export function read(key, fallback = null) {
    try {
        const raw = localStorage.getItem(`catatan.${key}`);
        return raw === null ? fallback : JSON.parse(raw);
    } catch {
        return fallback;
    }
}

export function write(key, value) {
    try {
        localStorage.setItem(`catatan.${key}`, JSON.stringify(value));
    } catch {
        // Storage full or unavailable: the app still works online.
    }
}

export function remove(key) {
    try {
        localStorage.removeItem(`catatan.${key}`);
    } catch {
        // ignore
    }
}

/** True when a request failed because there was no connection (no response at all). */
export function isOffline(error) {
    return !error.response;
}
