import { createApp } from 'vue';
import { createPinia } from 'pinia';
import { createRouter, createWebHistory } from 'vue-router';
import { createI18n } from 'vue-i18n';
import App from './App.vue';
import Overview from './Overview.vue';
import Notifications from './Notifications.vue';
import Preferences from './Preferences.vue';
import en from '../../lang/en.json';
import ar from '../../lang/ar.json';

const locale = document.documentElement.lang || 'en';
export const i18n = createI18n({ legacy: false, locale, fallbackLocale: 'en', messages: { en, ar }, missingWarn: false, fallbackWarn: false });
const router = createRouter({ history: createWebHistory('/app'), routes: [
    { path: '/', component: Overview },
    { path: '/notifications', component: Notifications },
    { path: '/preferences', component: Preferences },
    { path: '/:pathMatch(.*)*', redirect: '/' },
] });
createApp(App).use(createPinia()).use(i18n).use(router).mount('#member-app');
fetch(`/api/v1/catalogs/${locale}`).then(r => r.ok ? r.json() : Promise.reject()).then(data => i18n.global.mergeLocaleMessage(locale, data.messages)).catch(() => {});
