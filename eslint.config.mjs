// @ts-check
import eslint from '@eslint/js';
import { defineConfig, globalIgnores } from 'eslint/config';
import tseslint from 'typescript-eslint';

const ignoresConfig = globalIgnores([
  'babel.config.cjs',
  'commitlint.config.cjs',
  'coverage/*',
  'html-coverage/*',
  'node_modules/*',
  'phpmyfaq/assets/public/*',
  'phpmyfaq/src/libs/*',
  'volumes/*',
]);

export default defineConfig([
  {
    extends: [ignoresConfig, eslint.configs.recommended, tseslint.configs.strict],
  },
]);
