import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
            // Self-hosted system fonts only. The previous bunny('Instrument
            // Sans') call fails on networks that intercept HTTPS (corporate
            // antivirus, ZScaler, etc.) because the plugin fetches from
            // fonts.bunny.net at build time. System fonts render fine.
        }),
        tailwindcss(),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
