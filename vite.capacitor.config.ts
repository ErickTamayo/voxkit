import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react';
import tailwindcss from '@tailwindcss/vite';
import { fileURLToPath, URL } from 'node:url';

export default defineConfig({
    root: 'resources/capacitor',
    plugins: [react(), tailwindcss()],
    resolve: {
        alias: {
            '@': fileURLToPath(new URL('./resources/js', import.meta.url)),
        },
    },
    build: {
        outDir: '../../dist-capacitor',
        emptyOutDir: true,
    },
    server: {
        watch: {
            ignored: [
                '**/storage/framework/views/**',
                '**/.agents/**',
                '**/.claude/**',
                '**/.codex/**',
                '**/.cursor/**',
                '**/.gemini/**',
            ],
        },
    },
});
