<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { useI18n } from 'vue-i18n';
import { api } from './api';
const { t } = useI18n();
interface Notice { id: string; message: string; read_at: string | null; created_at: string }
const notices = ref<Notice[]>([]), loading = ref(false), failed = ref(false), page = ref(1), last = ref(1);
async function load() {
    loading.value = true; failed.value = false;
    try { const result = await api<{ data: Notice[]; last_page: number; unread_count: number }>(`notifications?page=${page.value}`); notices.value = result.data; last.value = result.last_page; window.dispatchEvent(new CustomEvent('notifications-updated', {detail: {unreadCount: result.unread_count}})); }
    catch { failed.value = true; } finally { loading.value = false; }
}
async function read(id?: string) {
    try { await api(id ? `notifications/${id}/read` : 'notifications/read-all', 'POST'); await load(); }
    catch { failed.value = true; }
}
function next(direction: number) { page.value += direction; load(); }
onMounted(load);
</script>
<template><section><div class="title-row"><h2>{{ t('Notifications') }}</h2><button class="secondary" @click="read()">{{ t('Mark all read') }}</button></div><p v-if="loading" role="status">{{ t('Loading…') }}</p><div v-else-if="failed" class="errors" role="alert">{{ t('Could not load your workspace. Please try again.') }} <button @click="load">{{ t('Retry') }}</button></div><template v-else><p v-if="!notices.length">{{ t('No notifications yet.') }}</p><article v-for="notice in notices" :key="notice.id" class="panel"><p>{{ notice.message }}</p><time :datetime="notice.created_at">{{ new Date(notice.created_at).toLocaleString() }}</time><button v-if="!notice.read_at" class="text-button" @click="read(notice.id)">{{ t('Mark read') }}</button></article><div class="hero-actions"><button :disabled="page <= 1" @click="next(-1)">{{ t('Previous') }}</button><span>{{ page }} / {{ last }}</span><button :disabled="page >= last" @click="next(1)">{{ t('Next') }}</button></div></template></section></template>
