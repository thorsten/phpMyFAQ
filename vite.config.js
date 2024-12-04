/// <reference types="vitest/config" />
import { defineConfig } from 'vite';
import path from 'path';
import { createHtmlPlugin } from 'vite-plugin-html';
import viteCompression from 'vite-plugin-compression';
import { viteStaticCopy } from 'vite-plugin-static-copy';
import { ViteMinifyPlugin } from 'vite-plugin-minify';
import sbom from 'rollup-plugin-sbom';

export default defineConfig({
  build: {
    rollupOptions: {
      input: {
        backend: path.resolve(__dirname, 'phpmyfaq/admin/assets/src/index.js'),
        frontend: path.resolve(__dirname, 'phpmyfaq/assets/src/frontend.js'),
        cookieConsent: path.resolve(__dirname, 'phpmyfaq/assets/src/cookie-consent.js'),
        setup: path.resolve(__dirname, 'phpmyfaq/assets/src/setup.js'),
        update: path.resolve(__dirname, 'phpmyfaq/assets/src/update.js'),
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
          tinymce: ['tinymce'],
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
          src: path.resolve(__dirname, 'phpmyfaq/admin/assets/src/tinymce/phpmyfaq.tinymce.plugin.js'),
          dest: '../phpmyfaq/assets/public/plugins/phpmyfaq',
          rename: 'plugin.js',
        },
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
    sbom({
      includeWellKnown: false,
      outFilename: 'sbom-node',
      outFormats: ['json'],
    }),
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
    globals: true,
    include: ['**/phpmyfaq/assets/**/*.test.js', '**/phpmyfaq/admin/assets/**/*.test.js'],
  },
});
