import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

// CSS e JS simples, sem framework. As letras (Alegreya, Atkinson Hyperlegible,
// IBM Plex Mono) vêm dos pacotes @fontsource e ficam alojadas no próprio site (RGPD).
export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
