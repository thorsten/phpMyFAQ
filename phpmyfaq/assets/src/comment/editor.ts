/**
 * Minimal Jodit Editor for Comment Forms
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2026 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2026-01-19
 */

import { Jodit } from 'jodit';

import 'jodit/esm/plugins/clean-html/clean-html.js';
import 'jodit/esm/plugins/clipboard/clipboard.js';
import 'jodit/esm/plugins/delete/delete.js';
import 'jodit/esm/plugins/indent/indent.js';
import 'jodit/esm/plugins/paste-from-word/paste-from-word.js';
import 'jodit/esm/plugins/select/select.js';

/**
 * Renders a minimal Jodit editor for comment forms
 * Features:
 * - Basic text formatting (bold, italic, underline, strikethrough)
 * - Lists (ul, ol)
 * - Links
 * - No file/image uploads (security)
 * - No advanced features (tables, media, alignment)
 */
export const renderCommentEditor = (selector: string): Jodit | null => {
  const commentField = document.querySelector<HTMLTextAreaElement>(selector);

  if (!commentField) {
    return null;
  }

  // Detect browser color scheme preference (dark/light)
  const prefersDark = window.matchMedia('(prefers-color-scheme: dark)');

  const joditEditor = Jodit.make(commentField, {
    zIndex: 0,
    readonly: false,
    beautifyHTML: true,
    sourceEditor: 'area',
    toolbarButtonSize: 'middle',
    theme: prefersDark.matches ? 'dark' : 'default',
    saveModeInStorage: false,
    spellcheck: true,
    editorClassName: false,
    triggerChangeEvent: true,
    width: 'auto',
    height: 'auto',
    minHeight: 200,
    maxHeight: 400,
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
    showCharsCounter: false,
    showWordsCounter: false,
    showXPathInStatusbar: false,
    toolbarAdaptive: false,

    // Minimal button set for basic formatting
    buttons: [
      'bold',
      'italic',
      'underline',
      'strikethrough',
      '|',
      'ul',
      'ol',
      '|',
      'link',
      'unlink',
      '|',
      'undo',
      'redo',
    ],

    // Disable all upload-related and advanced plugins
    disablePlugins: [
      'add-new-line',
      'image',
      'video',
      'file',
      'media',
      'table',
      'iframe',
      'drag-and-drop',
      'drag-and-drop-element',
      'fullsize',
      'preview',
      'source',
    ],

    // Disable uploaders completely
    uploader: {
      url: '',
      insertImageAsBase64URI: false,
    },
    filebrowser: {
      ajax: {
        url: '',
      },
    },

    events: {},
    textIcons: false,
  });

  // Theme switching support
  const setJoditTheme = (theme: 'dark' | 'default'): void => {
    joditEditor.options.theme = theme;

    const container: HTMLDivElement = joditEditor.container;
    container.classList.remove('jodit_theme_default', 'jodit_theme_dark');
    container.classList.add(theme === 'dark' ? 'jodit_theme_dark' : 'jodit_theme_default');
  };

  const applyTheme = (): void => {
    setJoditTheme(prefersDark.matches ? 'dark' : 'default');
  };

  // Listen for system color scheme changes
  if (typeof prefersDark.addEventListener === 'function') {
    prefersDark.addEventListener('change', applyTheme);
  } else if (
    typeof (prefersDark as MediaQueryList & { addListener?: (listener: () => void) => void }).addListener === 'function'
  ) {
    (prefersDark as MediaQueryList & { addListener: (listener: () => void) => void }).addListener(applyTheme);
  }

  // Listen for Bootstrap theme attribute changes
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

  // Apply initial theme from Bootstrap attribute if set
  applyThemeFromAttribute();

  return joditEditor;
};
