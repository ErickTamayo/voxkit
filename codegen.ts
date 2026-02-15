import type { CodegenConfig } from "@graphql-codegen/cli";

const config: CodegenConfig = {
    schema: "./graphql/schema.generated.graphql",
    documents: ["resources/js/**/*.graphql"],
    generates: {
        "resources/js/graphql/types.ts": {
            plugins: ["typescript"],
            config: {
                maybeValue: "T | null",
                scalars: {
                    Timestamp: "number",
                    ULID: "string",
                },
            },
        },
        "resources/js/": {
            preset: "near-operation-file",
            presetConfig: {
                extension: ".graphql.ts",
                baseTypesPath: "graphql/types.ts",
                folder: ".",
            },
            plugins: ["typescript-operations", "typed-document-node"],
            config: {
                maybeValue: "T | null",
                scalars: {
                    Timestamp: "number",
                    ULID: "string",
                },
            },
        },
    },
    ignoreNoDocuments: true,
};

export default config;
