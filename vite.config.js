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
        dir: path.resolve(__dirname, 'phpmyfaq/assets/dist'),
        format: 'es',
        entryFileNames: '[name].js',
        assetFileNames: '[name].[ext]',
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
          dest: '../phpmyfaq/assets/dist/plugins/phpmyfaq',
          rename: 'plugin.js',
        },
        {
          src: path.resolve(__dirname, 'phpmyfaq/assets/fonts/*'),
          dest: '../phpmyfaq/assets/dist/fonts',
        },
        {
          src: path.resolve(__dirname, 'node_modules/bootstrap-icons/font/bootstrap-icons.css'),
          dest: '../phpmyfaq/assets/dist',
        },
        {
          src: path.resolve(__dirname, 'node_modules/bootstrap-icons/font/fonts/*'),
          dest: '../phpmyfaq/assets/dist/fonts',
        },
      ],
    }),
    ViteMinifyPlugin(),
  ],
  css: {
    preprocessorOptions: {
      scss: {},
    },
  },
});
