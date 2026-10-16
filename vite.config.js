import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import tailwindcss from '@tailwindcss/vite';
export default defineConfig({plugins:[tailwindcss(),laravel({input:['resources/css/app.css','resources/js/app.js','resources/app/main.ts'],refresh:true}),vue()], server:{host:'127.0.0.1',port:5177,strictPort:true}});
