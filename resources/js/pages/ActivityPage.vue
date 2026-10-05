<script setup>
import { computed, onMounted, ref } from 'vue';
import { useRouter } from 'vue-router';
import axios from 'axios';
import { useActivitiesStore } from '../stores/activities';
import { formatDate, formatDateTime, formatRupiah, relativeDay, timezone } from '../lib/format';
import EntrySheet from '../components/EntrySheet.vue';

const props = defineProps({ id: { type: String, required: true } });
const store = useActivitiesStore();
const router = useRouter();

const activity = ref(null);
const entries = ref([]);
const editing = ref(null);
const notFound = ref(false);

const stats = computed(() => activity.value?.stats);

async function load() {
    try {
        const [a, e] = await Promise.all([
            axios.get(`/api/activities/${props.id}`, { params: { tz: timezone() } }),
            axios.get(`/api/activities/${props.id}/entries`),
        ]);
        activity.value = a.data.data;
        entries.value = e.data.data;
    } catch (error) {
        if (error.response?.status !== 404) throw error;
        notFound.value = true;
    }
}

async function logNow() {
    await store.log(store.byId(activity.value.id) ?? activity.value);
    await load();
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
                <h2 class="px-2 text-lg font-semibold">Riwayat</h2>
                <p v-if="!entries.length" class="mt-3 px-2 text-stone-500">Belum ada catatan.</p>
                <ul class="mt-3 divide-y divide-stone-200 rounded-2xl bg-white shadow-sm">
                    <li v-for="entry in entries" :key="entry.uuid">
                        <button type="button" class="w-full p-4 text-left" @click="editing = entry">
                            <span class="block font-medium">{{ formatDateTime(entry.occurred_at) }}</span>
                            <span v-if="entry.note" class="block text-stone-700">{{ entry.note }}</span>
                            <span
                                v-if="entry.cost !== null || entry.location_name"
                                class="block text-sm text-stone-500"
                            >
                                {{ [entry.cost !== null ? formatRupiah(entry.cost) : null, entry.location_name].filter(Boolean).join(' · ') }}
                            </span>
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
            @close="editing = null"
        />
    </main>
</template>
