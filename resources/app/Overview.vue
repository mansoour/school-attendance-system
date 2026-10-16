<script setup lang="ts">
import { ArrowUpRight, Layers, UserRound, ShieldCheck } from '@lucide/vue';
import { useI18n } from 'vue-i18n';
import { useMember } from './store';
const { t } = useI18n(); const store = useMember();
</script>
<template><div class="feature-grid"><article v-if="store.member?.plans_enabled"><p class="eyebrow"><Layers class="icon"/>{{ t('Your plan') }}</p><h2>{{ store.member?.plan ?? '—' }}</h2><a href="/pricing">{{ t('Explore plans') }} <ArrowUpRight class="icon rtl-directional"/></a></article><article><p class="eyebrow"><UserRound class="icon"/>{{ t('Account') }}</p><h3>{{ store.member?.user.email }}</h3><a href="/account">{{ t('Manage account') }} <ArrowUpRight class="icon rtl-directional"/></a></article><article><p class="eyebrow"><ShieldCheck class="icon"/>{{ t('Peace of mind') }}</p><h3>{{ t('Two-factor authentication') }}</h3><a href="/account/security">{{ t('Explore security') }} <ArrowUpRight class="icon rtl-directional"/></a></article></div></template>
