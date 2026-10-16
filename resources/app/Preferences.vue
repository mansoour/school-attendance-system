<script setup lang="ts">
import { reactive, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import { api } from './api';
import { useMember, type Preferences } from './store';
const { t } = useI18n(); const store = useMember();
const preferences = reactive<Preferences>(Object.fromEntries(['welcome',...(store.member?.plans_enabled ? ['plan_changed'] : []),'support_received'].map(event => [event, { ...(store.member?.preferences[event] ?? { database: true, mail: true }) }])));
const saved = ref(false), failed = ref(false), saving = ref(false);
async function save() {
    saved.value = false; failed.value = false; saving.value = true;
    try { await api('notification-preferences', 'PUT', { preferences }); if (store.member) store.member.preferences = structuredClone(JSON.parse(JSON.stringify(preferences))); saved.value = true; }
    catch { failed.value = true; } finally { saving.value = false; }
}
</script>
<template><form class="stack" @submit.prevent="save"><h2>{{ t('Preferences') }}</h2><p>{{ t('Security email is always enabled.') }}</p><fieldset v-for="(channels, event) in preferences" :key="event" class="panel"><legend>{{ t(event) }}</legend><label><input type="checkbox" v-model="channels.database">{{ t('In-app notifications') }}</label><label><input type="checkbox" v-model="channels.mail">{{ t('Email notifications') }}</label></fieldset><p v-if="saved" role="status" class="status">{{ t('Saved.') }}</p><p v-if="failed" role="alert" class="errors">{{ t('Could not load your workspace. Please try again.') }}</p><button :disabled="saving">{{ t('Save changes') }}</button></form></template>
