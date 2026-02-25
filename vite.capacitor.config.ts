import { existsSync } from 'node:fs';
import { extname } from 'node:path';
import { fileURLToPath, URL } from 'node:url';
import { defineConfig, type Plugin } from 'vite';
import react from '@vitejs/plugin-react';
import tailwindcss from '@tailwindcss/vite';

const CAPACITOR_PLATFORM_EXTENSIONS = new Set([
    '.tsx',
    '.ts',
    '.jsx',
    '.js',
    '.mjs',
    '.cjs',
]);

function splitResolvedId(id: string): { pathname: string; suffix: string } {
    const queryOrHashIndex = id.search(/[?#]/);

    if (queryOrHashIndex === -1) {
        return {
            pathname: id,
            suffix: '',
        };
    }

    return {
        pathname: id.slice(0, queryOrHashIndex),
        suffix: id.slice(queryOrHashIndex),
    };
}

function capacitorPlatformResolver(): Plugin {
    return {
        name: 'capacitor-platform-resolver',
        enforce: 'pre',
        async resolveId(source, importer, options) {
            if (source.includes('.capacitor.')) {
                return null;
            }

            const resolved = await this.resolve(source, importer, {
                ...(options ?? {}),
                skipSelf: true,
            });

            if (resolved === null) {
                return null;
            }

            const { pathname, suffix } = splitResolvedId(resolved.id);

            if (
                pathname.includes('\0')
                || pathname.includes('/node_modules/')
                || pathname.includes('.capacitor.')
            ) {
                return resolved;
            }

            const extension = extname(pathname);

            if (!CAPACITOR_PLATFORM_EXTENSIONS.has(extension)) {
                return resolved;
            }

            const capacitorVariantPath = `${pathname.slice(0, -extension.length)}.capacitor${extension}`;
            const hasCapacitorVariant = existsSync(capacitorVariantPath);

            if (!hasCapacitorVariant) {
                return resolved;
            }

            return {
                ...resolved,
                id: `${capacitorVariantPath}${suffix}`,
            };
        },
    };
}

export default defineConfig({
    root: 'resources/capacitor',
    plugins: [
        capacitorPlatformResolver(),
        react({
            babel: {
                plugins: ['babel-plugin-react-compiler'],
            },
        }),
        tailwindcss(),
    ],
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
