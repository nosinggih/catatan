<script setup>
import { computed, onMounted, ref } from 'vue';
import { useRouter } from 'vue-router';
import axios from 'axios';
import { useActivitiesStore } from '../stores/activities';
import { formatDate, formatDateTime, formatRupiah, relativeDay, timezone } from '../lib/format';
import { isOffline } from '../lib/storage';
import { enablePush, isIos, isStandalone, pushPermission } from '../lib/push';
import EntrySheet from '../components/EntrySheet.vue';

const props = defineProps({ id: { type: String, required: true } });
const store = useActivitiesStore();
const router = useRouter();

const activity = ref(null);
const entries = ref([]);
const editing = ref(null);
const notFound = ref(false);
const offline = ref(false);
const permission = ref(pushPermission());
const reminderError = ref('');

const stats = computed(() => activity.value?.stats);
const pending = computed(() =>
    store.queue
        .filter((e) => e.activity_id === Number(props.id))
        .map((e) => ({ ...e, pending: true }))
        .reverse(),
);
const history = computed(() => [...pending.value, ...entries.value]);

const DAY = 86400000;
const averageDays = computed(() =>
    stats.value?.average_interval_days ? Math.max(1, Math.round(stats.value.average_interval_days)) : null,
);
const reminderOptions = computed(() => {
    const options = [7, 14, 30];
    if (averageDays.value && !options.includes(averageDays.value)) options.push(averageDays.value);
    const current = activity.value?.reminder_interval_days;
    if (current && !options.includes(current)) options.push(current);
    return options.sort((a, b) => a - b);
});
const dueText = computed(() => {
    const due = stats.value?.next_due_at;
    if (!activity.value?.reminder_interval_days) return null;
    if (!due) return 'Pengingat mulai setelah catatan pertama.';
    const days = Math.floor((Date.now() - new Date(due).getTime()) / DAY);
    if (days > 0) return `Sudah lewat ${days} hari dari jadwal.`;
    if (days === 0 && Date.now() >= new Date(due).getTime()) return 'Sudah waktunya hari ini.';
    return `Berikutnya: ${formatDate(due)}`;
});
// iPhone only allows notifications for apps added to the home screen.
const needsInstallForPush = computed(() => isIos() && !isStandalone());

async function load() {
    try {
        const [a, e] = await Promise.all([
            axios.get(`/api/activities/${props.id}`, { params: { tz: timezone() } }),
            axios.get(`/api/activities/${props.id}/entries`),
        ]);
        activity.value = a.data.data;
        entries.value = e.data.data;
        offline.value = false;
        for (const entry of pending.value) store.bump(activity.value, entry.occurred_at);
    } catch (error) {
        if (error.response?.status === 404) {
            notFound.value = true;
        } else if (isOffline(error)) {
            // Fall back to the cached activity; full history needs a connection.
            offline.value = true;
            activity.value = store.byId(props.id) ?? null;
            entries.value = [];
            notFound.value = !activity.value;
        } else {
            throw error;
        }
    }
}

async function logNow() {
    await store.log(store.byId(activity.value.id) ?? activity.value);
    await load();
}

async function setReminder(days) {
    reminderError.value = '';
    try {
        await store.setReminder(activity.value, days);
    } catch (error) {
        reminderError.value = isOffline(error) ? 'Butuh koneksi internet untuk mengatur pengingat.' : 'Gagal menyimpan pengingat.';
        return;
    }
    if (days && permission.value === 'default' && !needsInstallForPush.value) {
        await enablePush().catch(() => false);
        permission.value = pushPermission();
    }
}

async function turnOnNotifications() {
    await enablePush().catch(() => false);
    permission.value = pushPermission();
}

function onPhoto(updated) {
    const index = entries.value.findIndex((e) => e.uuid === updated.uuid);
    if (index !== -1) entries.value[index] = updated;
}

async function saveEntry(fields) {
    await store.updateEntry(editing.value, fields);
    editing.value = null;
    await load();
}

async function deleteEntry() {
    if (!confirm('Hapus catatan ini?')) return;
    await store.undo(editing.value);
    editing.value = null;
    await load();
}

async function rename() {
    const name = prompt('Nama kegiatan', activity.value.name)?.trim();
    if (!name || name === activity.value.name) return;
    try {
        await store.rename(activity.value, name);
    } catch (error) {
        alert(error.response?.data?.message ?? 'Gagal mengubah nama.');
    }
}

async function remove() {
    if (!confirm(`Hapus "${activity.value.name}" beserta riwayatnya?`)) return;
    await store.remove(activity.value);
    router.replace({ name: 'home' });
}

onMounted(load);
</script>

<template>
    <main class="mx-auto min-h-dvh max-w-md px-4 pt-4 pb-10">
        <RouterLink :to="{ name: 'home' }" class="inline-flex h-10 items-center px-2 text-teal-800">‹ Kembali</RouterLink>

        <p v-if="notFound" class="mt-10 text-center text-stone-500">Kegiatan tidak ditemukan.</p>

        <template v-else-if="activity">
            <header class="mt-2 px-2">
                <p class="text-4xl" aria-hidden="true">{{ activity.icon }}</p>
                <h1 class="mt-2 text-2xl font-semibold">{{ activity.name }}</h1>
            </header>

            <section class="mt-6 rounded-2xl bg-white p-5 shadow-sm">
                <p class="text-sm text-stone-500">Terakhir</p>
                <p class="mt-1 text-3xl font-semibold">{{ relativeDay(stats.last_entry_at) }}</p>
                <p v-if="stats.last_entry_at" class="mt-1 text-stone-600">{{ formatDate(stats.last_entry_at) }}</p>
                <p v-if="dueText" class="mt-3 text-sm font-medium text-amber-800">⏰ {{ dueText }}</p>
            </section>

            <dl class="mt-3 grid grid-cols-2 gap-3">
                <div class="rounded-2xl bg-white p-4 shadow-sm">
                    <dt class="text-sm text-stone-500">Bulan ini</dt>
                    <dd class="mt-1 text-xl font-semibold">{{ stats.month_count }} kali</dd>
                </div>
                <div class="rounded-2xl bg-white p-4 shadow-sm">
                    <dt class="text-sm text-stone-500">Rata-rata</dt>
                    <dd class="mt-1 text-xl font-semibold">
                        {{ stats.average_interval_days !== null ? `tiap ${Math.round(stats.average_interval_days)} hari` : '–' }}
                    </dd>
                </div>
                <div class="rounded-2xl bg-white p-4 shadow-sm">
                    <dt class="text-sm text-stone-500">Total</dt>
                    <dd class="mt-1 text-xl font-semibold">{{ stats.entries_count }} kali</dd>
                </div>
                <div class="rounded-2xl bg-white p-4 shadow-sm">
                    <dt class="text-sm text-stone-500">Total biaya</dt>
                    <dd class="mt-1 text-xl font-semibold">{{ formatRupiah(stats.total_cost) }}</dd>
                </div>
            </dl>

            <button
                type="button"
                class="mt-4 h-14 w-full rounded-2xl bg-teal-700 text-lg font-medium text-white"
                @click="logNow"
            >
                Catat sekarang
            </button>

            <section class="mt-8">
                <h2 class="px-2 text-lg font-semibold">Pengingat</h2>
                <p class="mt-1 px-2 text-sm text-stone-600">Ingatkan aku kalau sudah lewat…</p>
                <div class="mt-3 flex flex-wrap gap-2 px-2">
                    <button
                        type="button"
                        class="h-10 rounded-full border px-4"
                        :class="!activity.reminder_interval_days ? 'border-teal-700 bg-teal-700 text-white' : 'border-stone-300 bg-white'"
                        @click="setReminder(null)"
                    >
                        Mati
                    </button>
                    <button
                        v-for="days in reminderOptions"
                        :key="days"
                        type="button"
                        class="h-10 rounded-full border px-4"
                        :class="activity.reminder_interval_days === days ? 'border-teal-700 bg-teal-700 text-white' : 'border-stone-300 bg-white'"
                        @click="setReminder(days)"
                    >
                        {{ days }} hari{{ days === averageDays ? ' (rata-rata)' : '' }}
                    </button>
                </div>
                <p v-if="reminderError" class="mt-2 px-2 text-sm text-red-700">{{ reminderError }}</p>
                <template v-if="activity.reminder_interval_days">
                    <p v-if="needsInstallForPush" class="mx-2 mt-3 rounded-xl bg-amber-50 p-3 text-sm text-amber-900">
                        Di iPhone, notifikasi hanya muncul kalau Catatan dipasang di layar utama: ketuk tombol Bagikan,
                        lalu pilih "Tambah ke Layar Utama".
                    </p>
                    <p v-else-if="permission === 'denied'" class="mx-2 mt-3 rounded-xl bg-amber-50 p-3 text-sm text-amber-900">
                        Notifikasi diblokir. Izinkan notifikasi untuk situs ini di pengaturan browser.
                    </p>
                    <button
                        v-else-if="permission === 'default'"
                        type="button"
                        class="mx-2 mt-3 h-11 rounded-xl border border-teal-700 px-4 font-medium text-teal-800"
                        @click="turnOnNotifications"
                    >
                        Aktifkan notifikasi di HP ini
                    </button>
                    <p v-else-if="permission === 'unsupported'" class="mt-3 px-2 text-sm text-stone-500">
                        Browser ini belum mendukung notifikasi. Tanda "Sudah waktunya" tetap muncul di beranda.
                    </p>
                </template>
            </section>

            <section class="mt-8">
                <h2 class="px-2 text-lg font-semibold">Riwayat</h2>
                <p v-if="offline" class="mt-1 px-2 text-sm text-stone-500">Offline. Riwayat lengkap tampil saat online.</p>
                <p v-if="!history.length && !offline" class="mt-3 px-2 text-stone-500">Belum ada catatan.</p>
                <ul v-if="history.length" class="mt-3 divide-y divide-stone-200 rounded-2xl bg-white shadow-sm">
                    <li v-for="entry in history" :key="entry.uuid">
                        <button type="button" class="flex w-full gap-3 p-4 text-left" @click="editing = entry">
                            <span class="min-w-0 flex-1">
                            <span class="block font-medium">
                                {{ formatDateTime(entry.occurred_at) }}
                                <span v-if="entry.pending" class="ml-1 text-xs font-normal text-amber-700">menunggu dikirim</span>
                            </span>
                            <span v-if="entry.note" class="block text-stone-700">{{ entry.note }}</span>
                            <span
                                v-if="entry.cost != null || entry.location_name"
                                class="block text-sm text-stone-500"
                            >
                                {{ [entry.cost != null ? formatRupiah(entry.cost) : null, entry.location_name].filter(Boolean).join(' · ') }}
                            </span>
                            </span>
                            <img
                                v-if="entry.photo_url"
                                :src="entry.photo_url"
                                alt=""
                                loading="lazy"
                                class="size-14 shrink-0 rounded-lg object-cover"
                            />
                        </button>
                    </li>
                </ul>
            </section>

            <div class="mt-10 flex justify-center gap-6 text-sm">
                <button type="button" class="text-stone-600" @click="rename">Ubah nama</button>
                <button type="button" class="text-red-700" @click="remove">Hapus kegiatan</button>
            </div>
        </template>

        <EntrySheet
            v-if="editing"
            :entry="editing"
            :activity="activity"
            deletable
            @save="saveEntry"
            @delete="deleteEntry"
            @photo="onPhoto"
            @close="editing = null"
        />
    </main>
</template>
