import axios from 'axios';

export const isIos = () =>
    /iphone|ipad|ipod/i.test(navigator.userAgent) ||
    (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);

export const isStandalone = () =>
    window.matchMedia?.('(display-mode: standalone)').matches || navigator.standalone === true;

export const isPushSupported = () =>
    'serviceWorker' in navigator && 'PushManager' in window && 'Notification' in window;

/** 'granted' | 'denied' | 'default' | 'unsupported' */
export const pushPermission = () => (isPushSupported() ? Notification.permission : 'unsupported');

function urlBase64ToUint8Array(base64) {
    const padded = (base64 + '='.repeat((4 - (base64.length % 4)) % 4)).replace(/-/g, '+').replace(/_/g, '/');
    return Uint8Array.from(atob(padded), (c) => c.charCodeAt(0));
}

/**
 * Asks for permission and registers this device for reminders.
 * Returns true when the device will receive notifications.
 */
export async function enablePush() {
    const key = window.CATATAN?.vapidPublicKey;
    if (!isPushSupported() || !key) return false;

    const permission = await Notification.requestPermission();
    if (permission !== 'granted') return false;

    const registration = await navigator.serviceWorker.ready;
    const subscription =
        (await registration.pushManager.getSubscription()) ||
        (await registration.pushManager.subscribe({
            userVisibleOnly: true,
            applicationServerKey: urlBase64ToUint8Array(key),
        }));

    await axios.post('/api/push-subscriptions', subscription.toJSON());
    return true;
}
