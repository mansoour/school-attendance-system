<script setup lang="ts">
import { LayoutDashboard, Bell, Settings } from '@lucide/vue';
import { onMounted } from 'vue';
import { useI18n } from 'vue-i18n';
import { useMember } from './store';
const { t } = useI18n();
const store = useMember();
onMounted(() => store.load());
</script>
<template>
    <div class="page-heading"><p class="eyebrow">{{ t('My workspace') }}</p><h1>{{ t('Welcome') }}{{ store.member ? `, ${store.member.user.name}` : '' }}.</h1></div>
    <nav class="member-tabs" :aria-label="t('My workspace')"><RouterLink to="/"><LayoutDashboard class="icon"/>{{ t('Overview') }}</RouterLink><RouterLink to="/notifications"><Bell class="icon"/>{{ t('Notifications') }}</RouterLink><RouterLink to="/preferences"><Settings class="icon"/>{{ t('Preferences') }}</RouterLink></nav>
    <p v-if="store.loading" role="status">{{ t('Loading…') }}</p>
    <div v-else-if="store.failed" class="errors" role="alert">{{ t('Could not load your workspace. Please try again.') }} <button @click="store.load">{{ t('Retry') }}</button></div>
    <RouterView v-else-if="store.member" />
</template>
