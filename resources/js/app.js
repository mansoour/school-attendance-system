import './bootstrap';
import Alpine from 'alpinejs';
const root = document.documentElement;
const media = window.matchMedia('(prefers-color-scheme: dark)');
function savedTheme() {
    try { return localStorage.getItem('theme'); } catch { return null; }
}
function applyTheme(choice) {
    choice = ['light', 'dark', 'system'].includes(choice) ? choice : 'light';
    root.dataset.themeChoice = choice;
    root.dataset.theme = root.dataset.darkEnabled === '1' && (choice === 'dark' || (choice === 'system' && media.matches)) ? 'dark' : 'light';
    root.classList.toggle('dark', root.dataset.theme === 'dark');
    root.style.colorScheme = root.dataset.theme;
    document.querySelectorAll('button[data-theme-choice]').forEach(button => button.setAttribute('aria-pressed', String(button.dataset.themeChoice === choice)));
}
applyTheme(savedTheme() || root.dataset.defaultTheme || 'light');

// Hijri (Umm al-Qura) is the school's main calendar: show it under every Gregorian date picker.
function hijriLabel(input) {
    let label = input.nextElementSibling;
    if (!label || !label.classList.contains('hijri-hint')) {
        label = document.createElement('small');
        label.className = 'hijri-hint';
        input.after(label);
    }
    if (!input.value) { label.textContent = ''; return; }
    const lang = root.lang === 'ar' ? 'ar-SA' : 'en';
    const text = new Intl.DateTimeFormat(lang + '-u-ca-islamic-umalqura-nu-latn', { day: 'numeric', month: 'long', year: 'numeric', weekday: 'long' }).format(new Date(input.value + 'T12:00:00'));
    label.textContent = text + (root.lang === 'ar' ? ' هـ' : ' AH');
}
document.querySelectorAll('input[type=date]').forEach(input => {
    hijriLabel(input);
    input.addEventListener('change', () => hijriLabel(input));
});
media.addEventListener('change', () => applyTheme(savedTheme() || root.dataset.defaultTheme));
document.querySelectorAll('button[data-theme-choice]').forEach(button => button.addEventListener('click', () => {
    try { localStorage.setItem('theme', button.dataset.themeChoice); } catch { /* Still apply the choice for this page. */ }
    applyTheme(button.dataset.themeChoice);
}));
Alpine.data('navbarNotifications', (initialCount) => ({
    open: false, count: initialCount, busy: false, failed: false, allRead: initialCount === 0,
    async markAllRead() {
        if (this.busy || this.count === 0) return;
        this.busy = true; this.failed = false;
        try {
            const response = await fetch('/api/v1/notifications/read-all', {
                method: 'POST', credentials: 'same-origin',
                headers: {'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content},
            });
            if (!response.ok) throw new Error('Notification update failed');
            this.count = 0; this.allRead = true;
            window.dispatchEvent(new CustomEvent('notifications-updated', {detail: {unreadCount: 0}}));
        } catch { this.failed = true; } finally { this.busy = false; }
    },
}));
const sidebar = document.getElementById('admin-sidebar');
if (sidebar) {
    sidebar.querySelectorAll('details').forEach((group, index) => {
        let saved = null;
        try { saved = localStorage.getItem(`admin-navigation-${index}`); } catch { /* Use the default open groups. */ }
        if (saved !== null) group.open = saved === '1';
        group.addEventListener('toggle', () => { try { localStorage.setItem(`admin-navigation-${index}`, group.open ? '1' : '0'); } catch { /* Navigation still works without persistence. */ } });
    });
    const active = sidebar.querySelector('[aria-current="page"]');
    if (active) {
        active.closest('details').open = true;
        requestAnimationFrame(() => { const position = active.getBoundingClientRect().top - sidebar.getBoundingClientRect().top; sidebar.scrollTop += position - sidebar.clientHeight / 2 + active.clientHeight / 2; });
    }
}
document.querySelectorAll('[data-recaptcha-action]').forEach(input => {
    input.form.addEventListener('submit', async event => {
        event.preventDefault();
        if (!window.grecaptcha) return;
        window.grecaptcha.ready(async () => {
            try { input.value = await window.grecaptcha.execute(input.dataset.siteKey, {action: input.dataset.recaptchaAction}); input.form.submit(); }
            catch { input.value = ''; input.form.submit(); }
        });
    });
});
window.Alpine = Alpine;
Alpine.start();
