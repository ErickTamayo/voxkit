import { fileURLToPath, URL } from "node:url";
import tailwindcss from "@tailwindcss/vite";
import { mergeConfig } from "vite";
import type { StorybookConfig } from "@storybook/react-vite";

const config: StorybookConfig = {
    stories: [
        "../resources/js/**/*.mdx",
        "../resources/js/**/*.stories.@(js|jsx|ts|tsx)",
    ],
    addons: [],
    framework: {
        name: "@storybook/react-vite",
        options: {},
    },
    async viteFinal(config) {
        return mergeConfig(config, {
            plugins: [
                tailwindcss(),
            ],
            resolve: {
                alias: {
                    "@": fileURLToPath(new URL("../resources/js", import.meta.url)),
                },
            },
        });
    },
};

export default config;
