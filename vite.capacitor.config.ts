import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    root: 'resources/capacitor',
    plugins: [react(), tailwindcss()],
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
