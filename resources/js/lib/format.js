const DAY = 86400000;

function startOfDay(date) {
    const d = new Date(date);
    d.setHours(0, 0, 0, 0);
    return d;
}

export function timezone() {
    return Intl.DateTimeFormat().resolvedOptions().timeZone;
}

/** "Hari ini", "Kemarin", "12 hari lalu", "3 bulan lalu" */
export function relativeDay(iso) {
    if (!iso) return 'Belum pernah';
    const days = Math.round((startOfDay(new Date()) - startOfDay(iso)) / DAY);
    if (days <= 0) return 'Hari ini';
    if (days === 1) return 'Kemarin';
    if (days < 30) return `${days} hari lalu`;
    if (days < 365) return `${Math.floor(days / 30)} bulan lalu`;
    return `${Math.floor(days / 365)} tahun lalu`;
}

/** "Sabtu, 4 Okt 2026" */
export function formatDate(iso) {
    return new Intl.DateTimeFormat('id-ID', { weekday: 'long', day: 'numeric', month: 'short', year: 'numeric' }).format(
        new Date(iso),
    );
}

/** "4 Okt 2026, 08.30" */
export function formatDateTime(iso) {
    return new Intl.DateTimeFormat('id-ID', { dateStyle: 'medium', timeStyle: 'short' }).format(new Date(iso));
}

export function formatRupiah(amount) {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(amount);
}

/** ISO string to the value a datetime-local input expects, in local time. */
export function toLocalInput(iso) {
    const d = new Date(iso);
    const pad = (n) => String(n).padStart(2, '0');
    return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}T${pad(d.getHours())}:${pad(d.getMinutes())}`;
}

/** datetime-local value (local time) to an ISO string with offset. */
export function fromLocalInput(value) {
    return new Date(value).toISOString();
}

export function uuid() {
    if (globalThis.crypto?.randomUUID) return crypto.randomUUID();
    // Fallback for non-secure contexts (e.g. testing over a LAN IP).
    return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, (c) => {
        const r = (Math.random() * 16) | 0;
        return (c === 'x' ? r : (r & 0x3) | 0x8).toString(16);
    });
}
