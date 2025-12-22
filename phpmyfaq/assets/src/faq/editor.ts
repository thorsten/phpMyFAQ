/**
 * Reduced Jodit Editor for Frontend FAQ Submissions
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2025 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2025-12-22
 */

import { Jodit } from 'jodit';

import 'jodit/esm/plugins/clean-html/clean-html.js';
import 'jodit/esm/plugins/clipboard/clipboard.js';
import 'jodit/esm/plugins/delete/delete.js';
import 'jodit/esm/plugins/fullsize/fullsize.js';
import 'jodit/esm/plugins/image/image.js';
import 'jodit/esm/plugins/indent/indent.js';
import 'jodit/esm/plugins/justify/justify.js';
import 'jodit/esm/plugins/paste-from-word/paste-from-word.js';
import 'jodit/esm/plugins/preview/preview.js';
import 'jodit/esm/plugins/resizer/resizer.js';
import 'jodit/esm/plugins/select/select.js';
import 'jodit/esm/plugins/source/source.js';

let joditEditorInstance: any = null;

export const getJoditEditor = () => joditEditorInstance;

/**
 * Renders a reduced Jodit editor for the FAQ add form
 * This is a simplified version compared to the admin editor with:
 * - Basic formatting only
 * - No file uploads (security)
 * - No advanced features (tables, media, custom plugins)
 */
export const renderFaqEditor = () => {
  const answerField = document.getElementById('answer') as HTMLTextAreaElement | null;
  if (!answerField) {
    return;
  }

  // Detect browser color scheme preference (dark/light)
  const prefersDark = window.matchMedia('(prefers-color-scheme: dark)');

  const joditEditor = Jodit.make(answerField, {
    zIndex: 0,
    readonly: false,
    beautifyHTML: false,
    sourceEditor: 'area',
    activeButtonsInReadOnly: ['source', 'fullsize', 'preview'],
    toolbarButtonSize: 'middle',
    theme: prefersDark.matches ? 'dark' : 'default',
    saveModeInStorage: false,
    spellcheck: true,
    editorClassName: false,
    triggerChangeEvent: true,
    width: 'auto',
    height: 'auto',
    minHeight: 300,
    direction: '',
    language: 'auto',
    debugLanguage: false,
    tabIndex: -1,
    toolbar: true,
    enter: 'p',
    defaultMode: 1, // MODE_WYSIWYG
    useSplitMode: false,
    askBeforePasteFromWord: true,
    processPasteFromWord: true,
    defaultActionOnPasteFromWord: 'insert_clear_html',
    colors: {
      greyscale: [
        '#000000',
        '#434343',
        '#666666',
        '#999999',
        '#B7B7B7',
        '#CCCCCC',
        '#D9D9D9',
        '#EFEFEF',
        '#F3F3F3',
        '#FFFFFF',
      ],
      palette: [
        '#980000',
        '#FF0000',
        '#FF9900',
        '#FFFF00',
        '#00F0F0',
        '#00FFFF',
        '#4A86E8',
        '#0000FF',
        '#9900FF',
        '#FF00FF',
      ],
    },
    colorPickerDefaultTab: 'background',
    imageDefaultWidth: 300,
    removeButtons: [],
    disablePlugins: ['file', 'video', 'media', 'table'],
    extraPlugins: [],
    extraButtons: [],
    buttons: [
      'source',
      '|',
      'bold',
      'italic',
      'underline',
      'strikethrough',
      '|',
      'ul',
      'ol',
      '|',
      'outdent',
      'indent',
      '|',
      'left',
      'center',
      'right',
      '|',
      'link',
      'image',
      '|',
      'undo',
      'redo',
      '|',
      'eraser',
      'fullsize',
      'preview',
    ],
    events: {},
    textIcons: false,
    uploader: {
      url: '', // Disabled for frontend security
    },
    filebrowser: {
      ajax: {
        url: '',
      },
    },
  });

  const setJoditTheme = (theme: 'dark' | 'default'): void => {
    (joditEditor as any).options.theme = theme;

    const container: HTMLDivElement = joditEditor.container;
    container.classList.remove('jodit_theme_default', 'jodit_theme_dark');
    container.classList.add(theme === 'dark' ? 'jodit_theme_dark' : 'jodit_theme_default');
  };

  const applyTheme = (): void => {
    setJoditTheme(prefersDark.matches ? 'dark' : 'default');
  };

  if (typeof prefersDark.addEventListener === 'function') {
    prefersDark.addEventListener('change', applyTheme);
  } else if (typeof (prefersDark as any).addListener === 'function') {
    (prefersDark as any).addListener(applyTheme);
  }

  const applyThemeFromAttribute = (): void => {
    const themeAttr: string | null = document.documentElement.getAttribute('data-bs-theme');
    setJoditTheme(themeAttr === 'dark' ? 'dark' : 'default');
  };

  const themeObserver = new MutationObserver((mutations): void => {
    for (const m of mutations) {
      if (m.type === 'attributes' && m.attributeName === 'data-bs-theme') {
        applyThemeFromAttribute();
      }
    }
  });
  themeObserver.observe(document.documentElement, { attributes: true, attributeFilter: ['data-bs-theme'] });

  window.addEventListener('storage', (e: StorageEvent): void => {
    if (e.key === 'pmf-theme') {
      applyThemeFromAttribute();
    }
  });

  applyThemeFromAttribute();

  joditEditorInstance = joditEditor;
};
