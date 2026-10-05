<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '../stores/auth';
import { useActivitiesStore } from '../stores/activities';
import { relativeDay } from '../lib/format';
import { search } from '../lib/match';
import AddActivitySheet from '../components/AddActivitySheet.vue';
import EntrySheet from '../components/EntrySheet.vue';
import TemplatePicker from '../components/TemplatePicker.vue';
import InstallGuide from '../components/InstallGuide.vue';
import { enablePush, isIos, isStandalone, pushPermission } from '../lib/push';

const auth = useAuthStore();
const store = useActivitiesStore();
const router = useRouter();

const query = ref('');
const adding = ref(null); // initial name when the add sheet is open
const toast = ref(null); // { activity, entry }
const editing = ref(null); // { activity, entry }
const error = ref('');
const installPrompt = ref(null);
let toastTimer;

const visible = computed(() => search(query.value, store.items));
const permission = ref(pushPermission());
const now = ref(Date.now());
const isDue = (activity) => activity.stats.next_due_at && new Date(activity.stats.next_due_at).getTime() <= now.value;
// Offer notifications once the user has set a reminder somewhere.
const askNotifications = computed(
    () =>
        permission.value === 'default' &&
        !(isIos() && !isStandalone()) &&
        store.items.some((a) => a.reminder_interval_days),
);

async function turnOnNotifications() {
    await enablePush().catch(() => false);
    permission.value = pushPermission();
}

async function log(activity) {
    error.value = '';
    clearTimeout(toastTimer);
    try {
        const entry = await store.log(activity);
        now.value = Date.now();
        toast.value = { activity, entry };
        toastTimer = setTimeout(() => (toast.value = null), 6000);
        query.value = '';
    } catch {
        error.value = `Gagal mencatat "${activity.name}". Periksa koneksi lalu coba lagi.`;
    }
}

async function undo() {
    const { entry } = toast.value;
    toast.value = null;
    await store.undo(entry);
}

function openDetails() {
    editing.value = toast.value;
    toast.value = null;
}

async function saveDetails(fields) {
    await store.updateEntry(editing.value.entry, fields);
    editing.value = null;
}

function captureInstallPrompt(event) {
    event.preventDefault();
    installPrompt.value = event;
}

async function install() {
    installPrompt.value.prompt();
    await installPrompt.value.userChoice;
    installPrompt.value = null;
}

async function logout() {
    await auth.logout();
    router.replace({ name: 'login' });
}

onMounted(() => {
    now.value = Date.now();
    store.load();
    window.addEventListener('beforeinstallprompt', captureInstallPrompt);
});
onBeforeUnmount(() => {
    clearTimeout(toastTimer);
    window.removeEventListener('beforeinstallprompt', captureInstallPrompt);
});
</script>

<template>
    <main class="mx-auto flex min-h-dvh max-w-md flex-col px-4 pt-6 pb-28">
        <header class="flex items-center justify-between px-2">
            <h1 class="text-2xl font-semibold">Catatan</h1>
            <button type="button" class="text-sm text-stone-500" @click="logout">Keluar</button>
        </header>

        <button
            v-if="installPrompt"
            type="button"
            class="mx-2 mt-4 h-12 rounded-2xl border border-teal-700 font-medium text-teal-800"
            @click="install"
        >
            Pasang aplikasi di layar utama
        </button>

        <InstallGuide />

        <p
            v-if="store.offline || store.queue.length"
            role="status"
            class="mx-2 mt-4 rounded-xl bg-amber-50 p-3 text-sm text-amber-900"
        >
            {{ store.offline ? 'Offline' : 'Mengirim' }}<template v-if="store.queue.length">
                · {{ store.queue.length }} catatan menunggu dikirim</template
            ><template v-else>. Catatan tetap tersimpan dan dikirim saat online.</template>
        </p>

        <button
            v-if="askNotifications"
            type="button"
            class="mx-2 mt-4 h-12 rounded-2xl border border-teal-700 font-medium text-teal-800"
            @click="turnOnNotifications"
        >
            🔔 Aktifkan notifikasi pengingat
        </button>

        <p v-if="!store.loaded" class="mt-10 text-center text-stone-500">Memuat…</p>

        <template v-else-if="!store.items.length">
            <div class="px-2">
                <p class="mt-2 text-stone-600">Halo, {{ auth.user?.name }}.</p>
                <TemplatePicker @custom="adding = ''" />
            </div>
        </template>

        <template v-else>
            <input
                v-model="query"
                type="search"
                placeholder="Cari: kapan terakhir…"
                aria-label="Cari kegiatan"
                class="mx-2 mt-4 h-12 rounded-2xl border border-stone-300 bg-white px-4"
            />

            <p v-if="error" class="mx-2 mt-3 rounded-xl bg-red-50 p-3 text-sm text-red-700">{{ error }}</p>

            <ul class="mt-4 grid grid-cols-2 gap-3">
                <li v-for="activity in visible" :key="activity.id" class="relative">
                    <button
                        type="button"
                        class="flex h-32 w-full flex-col justify-between rounded-2xl bg-white p-4 text-left shadow-sm active:scale-95 active:bg-teal-50"
                        @click="log(activity)"
                    >
                        <span class="text-3xl" aria-hidden="true">{{ activity.icon }}</span>
                        <span
                            v-if="isDue(activity)"
                            class="absolute top-4 right-12 rounded-full bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-900"
                        >
                            Sudah waktunya
                        </span>
                        <span>
                            <span class="line-clamp-2 leading-tight font-medium">{{ activity.name }}</span>
                            <span class="mt-1 block text-sm text-stone-500">
                                {{ relativeDay(activity.stats.last_entry_at) }}
                            </span>
                        </span>
                    </button>
                    <RouterLink
                        :to="{ name: 'activity', params: { id: activity.id } }"
                        class="absolute top-2 right-2 flex size-10 items-center justify-center rounded-full text-stone-400"
                        :aria-label="`Riwayat ${activity.name}`"
                    >
                        ⓘ
                    </RouterLink>
                </li>
            </ul>

            <div v-if="query && !visible.length" class="mt-8 text-center text-stone-500">
                <p>Belum ada kegiatan "{{ query }}".</p>
                <button type="button" class="mt-3 font-medium text-teal-800" @click="adding = query">
                    Tambah "{{ query }}"
                </button>
            </div>
        </template>

        <button
            v-if="store.items.length"
            type="button"
            class="fixed right-4 bottom-[max(1.5rem,env(safe-area-inset-bottom))] h-14 rounded-full bg-teal-700 px-6 text-lg font-medium text-white shadow-lg"
            @click="adding = query"
        >
            + Kegiatan
        </button>

        <div
            v-if="toast"
            role="status"
            class="fixed inset-x-4 bottom-24 z-30 mx-auto flex max-w-md items-center gap-2 rounded-2xl bg-stone-900 p-3 pl-4 text-white shadow-lg"
        >
            <span class="flex-1 truncate">
                {{ toast.entry.pending ? 'Tersimpan di HP' : 'Tersimpan' }}: {{ toast.activity.icon }} {{ toast.activity.name }}
            </span>
            <button type="button" class="rounded-xl px-3 py-2 font-medium text-teal-300" @click="openDetails">
                Detail
            </button>
            <button type="button" class="rounded-xl px-3 py-2 text-stone-300" @click="undo">Batal</button>
        </div>

        <AddActivitySheet v-if="adding !== null" :initial-name="adding" @close="adding = null" @log="log" />
        <EntrySheet
            v-if="editing"
            :entry="editing.entry"
            :activity="editing.activity"
            @save="saveDetails"
            @close="editing = null"
        />
    </main>
</template>
