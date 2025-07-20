/// <reference types="vitest" />
import { defineConfig } from 'vite';
import path from 'path';
import { createHtmlPlugin } from 'vite-plugin-html';
import viteCompression from 'vite-plugin-compression';
import { viteStaticCopy } from 'vite-plugin-static-copy';
import { ViteMinifyPlugin } from 'vite-plugin-minify';

export default defineConfig({
  build: {
    rollupOptions: {
      input: {
        backend: path.resolve(__dirname, 'phpmyfaq/admin/assets/src/index.ts'),
        frontend: path.resolve(__dirname, 'phpmyfaq/assets/src/frontend.ts'),
        cookieConsent: path.resolve(__dirname, 'phpmyfaq/assets/src/cookie-consent.ts'),
        setup: path.resolve(__dirname, 'phpmyfaq/assets/src/setup.ts'),
        update: path.resolve(__dirname, 'phpmyfaq/assets/src/update.ts'),
        styles: path.resolve(__dirname, 'phpmyfaq/assets/scss/style.scss'),
        admin: path.resolve(__dirname, 'phpmyfaq/admin/assets/scss/style.scss'),
        debugMode: path.resolve(__dirname, 'phpmyfaq/assets/scss/debug-mode.scss'),
      },
      output: {
        dir: path.resolve(__dirname, 'phpmyfaq/assets/public'),
        format: 'es',
        entryFileNames: '[name].js',
        assetFileNames: '[name].[ext]',
        manualChunks: {
          bootstrap: ['bootstrap'],
          chart: ['chart.js'],
          jodit: ['jodit'],
        },
      },
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
          src: path.resolve(__dirname, 'phpmyfaq/assets/fonts/*'),
          dest: '../phpmyfaq/assets/public/fonts',
        },
        {
          src: path.resolve(__dirname, 'node_modules/bootstrap-icons/font/bootstrap-icons.css'),
          dest: '../phpmyfaq/assets/public',
        },
        {
          src: path.resolve(__dirname, 'node_modules/bootstrap-icons/font/fonts/*'),
          dest: '../phpmyfaq/assets/public/fonts',
        },
      ],
    }),
    ViteMinifyPlugin(),
  ],
  css: {
    preprocessorOptions: {
      scss: {
        silenceDeprecations: ['mixed-decls', 'color-functions', 'global-builtin', 'import'],
      },
    },
  },
  test: {
    environment: 'jsdom',
    coverage: {
      provider: 'istanbul',
      reporter: ['text', 'html'],
      exclude: [
        '**/node_modules/**',
        '**/html-coverage/**',
        '**/libs/**',
        '**/bootstrap*.min.js',
        '**/popper*.min.js',
        '**/babel.config.cjs',
        '**/commitlint.config.cjs',
      ],
      include: ['**/phpmyfaq/assets/**/*.ts', '**/phpmyfaq/admin/assets/**/*.ts'],
    },
    globals: true,
    include: ['**/phpmyfaq/assets/**/*.test.ts', '**/phpmyfaq/admin/assets/**/*.test.ts'],
  },
});
