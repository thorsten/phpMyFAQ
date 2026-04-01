/**
 * Jodit Editor plugin to toggle visual outlines on block-level elements
 *
 * When activated, block elements (p, div, h1–h6, blockquote, ul, ol, table,
 * section, article, header, footer, nav, aside, main, pre, address, figure,
 * figcaption, details, summary) receive a dashed border and a tag-name label
 * via CSS, making the document structure visible at a glance.
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
 * @since     2026-04-01
 */

import { Jodit } from 'jodit';
import showBlocksIcon from './show-blocks.svg.js';

Jodit.modules.Icon.set('showBlocks', showBlocksIcon);

const BLOCK_TAGS = [
  'p',
  'div',
  'h1',
  'h2',
  'h3',
  'h4',
  'h5',
  'h6',
  'blockquote',
  'ul',
  'ol',
  'li',
  'table',
  'thead',
  'tbody',
  'tr',
  'td',
  'th',
  'section',
  'article',
  'header',
  'footer',
  'nav',
  'aside',
  'main',
  'pre',
  'address',
  'figure',
  'figcaption',
  'details',
  'summary',
  'dl',
  'dt',
  'dd',
];

const SHOW_BLOCKS_CLASS = 'jodit-show-blocks';

const buildStyleSheet = (): string => {
  const selectors = BLOCK_TAGS.map((tag) => `.${SHOW_BLOCKS_CLASS} ${tag}`).join(',\n');
  const labelRules = BLOCK_TAGS.map((tag) => `.${SHOW_BLOCKS_CLASS} ${tag}::before { content: "${tag}"; }`).join('\n');

  return `
${selectors} {
  position: relative;
  border: 1px dashed #adb5bd;
  padding-top: 18px !important;
  margin-bottom: 2px;
  min-height: 24px;
}
.${SHOW_BLOCKS_CLASS} [class^="jodit"]::before {
  content: none !important;
}
${selectors.replace(/,\n/g, '::before,\n')}::before {
  position: absolute;
  top: 0;
  left: 0;
  font-size: 9px;
  font-family: monospace;
  line-height: 1;
  padding: 1px 4px;
  background: #6c757d;
  color: #fff;
  border-bottom-right-radius: 3px;
  pointer-events: none;
  z-index: 1;
  text-transform: uppercase;
}
${labelRules}
`;
};

Jodit.plugins.add('showBlocks', (editor: Jodit): void => {
  let active = false;
  let styleElement: HTMLStyleElement | null = null;

  editor.registerButton({
    name: 'showBlocks',
    group: 'other',
    options: {
      tooltip: 'Show Blocks',
      isActive: () => active,
    },
  });

  const injectStyles = (): void => {
    if (styleElement) {
      return;
    }

    const editorDoc = editor.editor.ownerDocument;
    styleElement = editorDoc.createElement('style');
    styleElement.setAttribute('data-jodit-show-blocks', '');
    styleElement.textContent = buildStyleSheet();
    editorDoc.head.appendChild(styleElement);
  };

  const removeStyles = (): void => {
    if (styleElement) {
      styleElement.remove();
      styleElement = null;
    }
  };

  editor.registerCommand('showBlocks', (): void => {
    active = !active;

    if (active) {
      injectStyles();
      editor.editor.classList.add(SHOW_BLOCKS_CLASS);
    } else {
      editor.editor.classList.remove(SHOW_BLOCKS_CLASS);
      removeStyles();
    }

    editor.events.fire('updateToolbar');
  });
});
