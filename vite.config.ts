/// <reference types="vitest" />
import { defineConfig } from 'vitest/config';
import path from 'path';
import { createHtmlPlugin } from 'vite-plugin-html';
import viteCompression from 'vite-plugin-compression';
import { viteStaticCopy } from 'vite-plugin-static-copy';
import { ViteMinifyPlugin } from 'vite-plugin-minify';

export default defineConfig({
  // Relative base so asset URLs in emitted CSS/JS resolve next to the built
  // files — phpMyFAQ may be installed in a subdirectory.
  base: './',
  build: {
    rolldownOptions: {
      input: {
        backend: path.resolve(import.meta.dirname, 'phpmyfaq/admin/assets/src/index.ts'),
        frontend: path.resolve(import.meta.dirname, 'phpmyfaq/assets/src/frontend.ts'),
        notifications: path.resolve(import.meta.dirname, 'phpmyfaq/assets/src/notifications-export.ts'),
        cookieConsent: path.resolve(import.meta.dirname, 'phpmyfaq/assets/src/cookie-consent.ts'),
        setup: path.resolve(import.meta.dirname, 'phpmyfaq/assets/src/setup.ts'),
        update: path.resolve(import.meta.dirname, 'phpmyfaq/assets/src/update.ts'),
        styles: path.resolve(import.meta.dirname, 'phpmyfaq/assets/scss/style.scss'),
        admin: path.resolve(import.meta.dirname, 'phpmyfaq/admin/assets/scss/style.scss'),
        debugMode: path.resolve(import.meta.dirname, 'phpmyfaq/assets/scss/debug-mode.scss'),
      },
      output: {
        dir: path.resolve(import.meta.dirname, 'phpmyfaq/assets/public'),
        format: 'es',
        entryFileNames: '[name].js',
        assetFileNames: (assetInfo: { names?: string[] }) =>
          /\.(?:woff2?|ttf|eot)$/.test(assetInfo.names?.[0] ?? '') ? 'fonts/[name].[ext]' : '[name].[ext]',
        preserveModules: false,
        exports: 'named',
        manualChunks: (id: string) => {
          if (id.includes('node_modules/bootstrap/')) return 'bootstrap';
          if (id.includes('node_modules/chart.js/')) return 'chart';
          if (id.includes('node_modules/jodit/')) return 'jodit';
        },
      },
      preserveEntrySignatures: 'exports-only',
    },
    sourcemap: true,
    minify: 'terser',
  },
  plugins: [
    createHtmlPlugin(),
    viteCompression(),
    viteStaticCopy({
      targets: [
        {
          src: path.resolve(import.meta.dirname, 'phpmyfaq/assets/fonts/*'),
          dest: '../phpmyfaq/assets/public/fonts',
          rename: { stripBase: true },
        },
        {
          src: path.resolve(import.meta.dirname, 'node_modules/bootstrap-icons/font/bootstrap-icons.css'),
          dest: '../phpmyfaq/assets/public',
          rename: { stripBase: true },
        },
        {
          src: path.resolve(import.meta.dirname, 'node_modules/bootstrap-icons/font/fonts/*'),
          dest: '../phpmyfaq/assets/public/fonts',
          rename: { stripBase: true },
        },
      ],
    }),
    ViteMinifyPlugin(),
  ],
  css: {
    preprocessorOptions: {
      scss: {
        silenceDeprecations: ['color-functions', 'global-builtin', 'import', 'if-function'],
      },
    },
  },
  test: {
    environment: 'jsdom',
    environmentOptions: {
      jsdom: {
        url: 'http://localhost/',
      },
    },
    setupFiles: ['./tests/vitest.setup.ts'],
    coverage: {
      provider: 'istanbul',
      reporter: ['text', 'html'],
      exclude: [
        '**/node_modules/**',
        '**/html-coverage/**',
        '**/libs/**',
        '**/site/**',
        '**/bootstrap*.min.js',
        '**/popper*.min.js',
        '**/commitlint.config.cjs',
      ],
      include: ['**/phpmyfaq/assets/**/*.ts', '**/phpmyfaq/admin/assets/**/*.ts'],
    },
    globals: true,
    include: ['**/phpmyfaq/assets/**/*.test.ts', '**/phpmyfaq/admin/assets/**/*.test.ts'],
    exclude: ['**/node_modules/**', '.claude/**', 'site/**'],
  },
});
