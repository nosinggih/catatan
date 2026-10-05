<script setup>
import { reactive, ref } from 'vue';
import BottomSheet from './BottomSheet.vue';
import { fromLocalInput, toLocalInput } from '../lib/format';

const props = defineProps({
    entry: { type: Object, required: true },
    activity: { type: Object, required: true },
    deletable: { type: Boolean, default: false },
    // Declared as a prop so the parent's @save handler can be awaited.
    onSave: { type: Function, required: true },
});
const emit = defineEmits(['delete', 'close']);

const form = reactive({
    occurred_at: toLocalInput(props.entry.occurred_at),
    note: props.entry.note ?? '',
    cost: props.entry.cost ?? '',
    location_name: props.entry.location_name ?? '',
});
const saving = ref(false);
const error = ref('');

async function save() {
    saving.value = true;
    error.value = '';
    try {
        await props.onSave({
            occurred_at: fromLocalInput(form.occurred_at),
            note: form.note.trim() || null,
            cost: form.cost === '' ? null : Number(String(form.cost).replace(/\D/g, '')),
            location_name: form.location_name.trim() || null,
        });
    } catch (e) {
        error.value = e.response?.data?.message ?? 'Gagal menyimpan.';
    } finally {
        saving.value = false;
    }
}
</script>

<template>
    <BottomSheet :title="`${activity.icon} ${activity.name}`" @close="emit('close')">
        <form class="space-y-4" @submit.prevent="save">
            <label class="block">
                <span class="text-sm text-stone-600">Waktu</span>
                <input
                    v-model="form.occurred_at"
                    type="datetime-local"
                    required
                    :max="toLocalInput(new Date().toISOString())"
                    class="mt-1 h-12 w-full rounded-xl border border-stone-300 px-3"
                />
            </label>
            <label class="block">
                <span class="text-sm text-stone-600">Catatan</span>
                <textarea
                    v-model="form.note"
                    rows="2"
                    maxlength="500"
                    placeholder="Contoh: sprei biru, oli Federal"
                    class="mt-1 w-full rounded-xl border border-stone-300 p-3"
                />
                <span class="text-xs text-stone-500">Jangan simpan PIN, kata sandi, atau nomor rekening.</span>
            </label>
            <div class="grid grid-cols-2 gap-3">
                <label class="block">
                    <span class="text-sm text-stone-600">Biaya (Rp)</span>
                    <input
                        v-model="form.cost"
                        inputmode="numeric"
                        placeholder="0"
                        class="mt-1 h-12 w-full rounded-xl border border-stone-300 px-3"
                    />
                </label>
                <label class="block">
                    <span class="text-sm text-stone-600">Lokasi</span>
                    <input
                        v-model="form.location_name"
                        maxlength="120"
                        placeholder="Contoh: AHASS"
                        class="mt-1 h-12 w-full rounded-xl border border-stone-300 px-3"
                    />
                </label>
            </div>

            <p v-if="error" class="text-sm text-red-700">{{ error }}</p>

            <button
                type="submit"
                :disabled="saving"
                class="h-14 w-full rounded-2xl bg-teal-700 text-lg font-medium text-white disabled:bg-stone-300"
            >
                Simpan
            </button>
            <button v-if="deletable" type="button" class="h-12 w-full text-red-700" @click="emit('delete')">
                Hapus catatan ini
            </button>
        </form>
    </BottomSheet>
</template>
