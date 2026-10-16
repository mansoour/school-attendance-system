import { defineStore } from 'pinia';
import { api } from './api';
export type Preferences = Record<string, { database: boolean; mail: boolean }>;
interface Member { plans_enabled: boolean; user: { id: number; name: string; email: string; locale: string }; plan: string | null; preferences: Preferences }
export const useMember = defineStore('member', {
    state: () => ({ member: null as Member | null, loading: false, failed: false }),
    actions: {
        async load() {
            this.loading = true; this.failed = false;
            try { this.member = await api<Member>('me'); } catch { this.failed = true; } finally { this.loading = false; }
        },
    },
});
