import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';

export default defineConfig({
    plugins: [
        laravel([
            'resources/assets/sass/main.scss',
            'resources/assets/js/main.js',
        ]),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
                compilerOptions: {
                    whitespace: 'preserve',
                },
            },
        }),
    ],
    resolve: {
        alias: {
            '@': '/resources/assets/js',
            vue: '@vue/compat',
            '@images': '/resources/assets/images',
        },
    },
    server: {
        watch: {
            ignored: [
                '**/storage/**',
                '**/vendor/**',
            ],
        },
    },
});
