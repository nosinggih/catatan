import { defineStore } from 'pinia';
import axios from 'axios';
import { timezone, uuid } from '../lib/format';
import { isOffline, read, write } from '../lib/storage';

/**
 * Activities plus an offline queue. A tap always succeeds locally; entries
 * that could not reach the server wait in the queue (in localStorage) and
 * are sent when the connection is back. The client-made UUID makes resending
 * safe.
 */
export const useActivitiesStore = defineStore('activities', {
    state: () => ({
        items: read('activities', []),
        queue: read('queue', []),
        loaded: false,
        offline: false,
    }),

    actions: {
        async load() {
            try {
                await this.flush();
                const { data } = await axios.get('/api/activities', { params: { tz: timezone() } });
                this.items = data.data;
                this.offline = false;
                this.applyQueue();
                write('activities', data.data);
            } catch (error) {
                if (!isOffline(error)) throw error;
                this.offline = true;
            }
            this.loaded = true;
        },

        byId(id) {
            return this.items.find((a) => a.id === Number(id));
        },

        async create(name, icon = null) {
            const { data } = await axios.post('/api/activities', { name, icon }, { params: { tz: timezone() } });
            if (!this.byId(data.data.id)) this.items.push(data.data);
            write('activities', this.items);
            return data.data;
        },

        /** One-tap logging. Never fails because of a missing connection. */
        async log(activity) {
            const entry = { uuid: uuid(), activity_id: activity.id, occurred_at: new Date().toISOString() };
            const before = { ...activity.stats };
            this.bump(activity, entry.occurred_at);

            try {
                const { data } = await axios.post('/api/entries', entry);
                write('activities', this.items);
                return { ...data.data, before };
            } catch (error) {
                if (!isOffline(error)) {
                    activity.stats = before;
                    throw error;
                }
                this.offline = true;
                this.queue.push(entry);
                write('queue', this.queue);
                write('activities', this.items);
                return { ...entry, pending: true, before };
            }
        },

        /** Sends queued entries, oldest first. Stops at the first connection error. */
        async flush() {
            while (this.queue.length) {
                try {
                    await axios.post('/api/entries', this.queue[0]);
                } catch (error) {
                    if (isOffline(error)) throw error;
                    // Rejected for good (e.g. the activity was deleted): drop it.
                }
                this.queue.shift();
                write('queue', this.queue);
            }
        },

        async undo(entry) {
            if (this.dequeue(entry)) {
                const activity = this.byId(entry.activity_id);
                if (activity && entry.before) activity.stats = entry.before;
                write('activities', this.items);
                return;
            }
            await axios.delete(`/api/entries/${entry.uuid}`);
            await this.load();
        },

        async updateEntry(entry, fields) {
            const queued = this.queue.find((e) => e.uuid === entry.uuid);
            if (queued) {
                Object.assign(queued, fields);
                write('queue', this.queue);
                return { ...queued, pending: true };
            }
            const { data } = await axios.patch(`/api/entries/${entry.uuid}`, fields);
            await this.load();
            return data.data;
        },

        async rename(activity, name) {
            const { data } = await axios.patch(`/api/activities/${activity.id}`, { name }, { params: { tz: timezone() } });
            Object.assign(activity, data.data);
        },

        async setReminder(activity, days) {
            const { data } = await axios.patch(
                `/api/activities/${activity.id}`,
                { reminder_interval_days: days },
                { params: { tz: timezone() } },
            );
            Object.assign(activity, data.data);
            const cached = this.byId(activity.id);
            if (cached && cached !== activity) Object.assign(cached, data.data);
            write('activities', this.items);
            return data.data;
        },

        async remove(activity) {
            await axios.delete(`/api/activities/${activity.id}`);
            this.items = this.items.filter((a) => a.id !== activity.id);
            write('activities', this.items);
        },

        bump(activity, occurredAt) {
            activity.stats.entries_count++;
            activity.stats.month_count++;
            if (!activity.stats.last_entry_at || occurredAt > activity.stats.last_entry_at) {
                activity.stats.last_entry_at = occurredAt;
                if (activity.reminder_interval_days) {
                    const due = new Date(occurredAt);
                    due.setDate(due.getDate() + activity.reminder_interval_days);
                    activity.stats.next_due_at = due.toISOString();
                }
            }
        },

        /** Re-applies still-queued entries on top of fresh server stats. */
        applyQueue() {
            for (const entry of this.queue) {
                const activity = this.byId(entry.activity_id);
                if (activity) this.bump(activity, entry.occurred_at);
            }
        },

        dequeue(entry) {
            const index = this.queue.findIndex((e) => e.uuid === entry.uuid);
            if (index === -1) return false;
            this.queue.splice(index, 1);
            write('queue', this.queue);
            return true;
        },
    },
});
