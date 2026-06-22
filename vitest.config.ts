import { defineConfig } from 'vitest/config';

/**
 * Vitest configuration for the AirToShareA frontend test suite.
 *
 * Layered test map (see design.md "Testing Strategy"):
 * - JS unit tests live in `resources/js/__tests__/**` and use Vitest + jsdom + fast-check.
 * - Each property-based test runs at least 100 iterations and is tagged with a
 *   `// Feature: fileshare-enhancements-bundle, Property {n}: {short title}` comment.
 *
 * Playwright end-to-end tests are configured separately in `playwright.config.ts`
 * and are not picked up by Vitest.
 */
export default defineConfig({
    test: {
        environment: 'jsdom',
        globals: true,
        include: [
            'resources/js/__tests__/**/*.{test,spec}.{js,ts}',
        ],
        exclude: [
            'node_modules/**',
            'vendor/**',
            'public/**',
            'tests/e2e/**',
        ],
        // Property tests can take a little longer than unit tests; allow some headroom.
        testTimeout: 10_000,
        hookTimeout: 10_000,
        coverage: {
            provider: 'v8',
            reporter: ['text', 'html'],
            include: ['resources/js/**/*.{js,ts}', 'public/assets/js/**/*.js'],
            exclude: ['resources/js/__tests__/**'],
        },
    },
});
