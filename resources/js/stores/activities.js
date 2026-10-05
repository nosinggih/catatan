import { defineStore } from 'pinia';
import axios from 'axios';
import { timezone, uuid } from '../lib/format';

export const useActivitiesStore = defineStore('activities', {
    state: () => ({
        items: [],
        loaded: false,
    }),

    actions: {
        async load() {
            const { data } = await axios.get('/api/activities', { params: { tz: timezone() } });
            this.items = data.data;
            this.loaded = true;
        },

        byId(id) {
            return this.items.find((a) => a.id === Number(id));
        },

        async create(name, icon = null) {
            const { data } = await axios.post('/api/activities', { name, icon }, { params: { tz: timezone() } });
            if (!this.byId(data.data.id)) this.items.push(data.data);
            return data.data;
        },

        /**
         * One-tap logging. Updates the button immediately and saves in the
         * background; the UUID makes a retried request safe.
         */
        async log(activity) {
            const entry = { uuid: uuid(), activity_id: activity.id, occurred_at: new Date().toISOString() };
            const before = { ...activity.stats };
            activity.stats.entries_count++;
            activity.stats.month_count++;
            activity.stats.last_entry_at = entry.occurred_at;

            try {
                const { data } = await axios.post('/api/entries', { uuid: entry.uuid, activity_id: activity.id });
                return data.data;
            } catch (error) {
                activity.stats = before;
                throw error;
            }
        },

        async undo(entry) {
            await axios.delete(`/api/entries/${entry.uuid}`);
            await this.load();
        },

        async updateEntry(entry, fields) {
            const { data } = await axios.patch(`/api/entries/${entry.uuid}`, fields);
            await this.load();
            return data.data;
        },

        async rename(activity, name) {
            const { data } = await axios.patch(`/api/activities/${activity.id}`, { name }, { params: { tz: timezone() } });
            Object.assign(activity, data.data);
        },

        async remove(activity) {
            await axios.delete(`/api/activities/${activity.id}`);
            this.items = this.items.filter((a) => a.id !== activity.id);
        },
    },
});
