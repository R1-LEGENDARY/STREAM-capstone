import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/learn_more.css',
                'resources/css/application-shell.css',
                'resources/css/dashboard.css',
                'resources/js/navigation-behavior.js',
                'resources/js/dashboard.js',
                'resources/js/summary.js'
            ],
            refresh: true,
        }),
    ],
});
