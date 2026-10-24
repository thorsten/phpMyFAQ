/**
 * Jodit Editor plugin to insert a table of contents
 *
 * Collects all headings (h1–h6) of the content, assigns each one an id, and
 * inserts a nested list of anchor links as <nav class="pmf-toc"> at the cursor.
 * Running the command again replaces the existing table of contents.
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
 * @since     2026-10-24
 */

import { Jodit } from 'jodit';
import tableOfContentsIcon from './table-of-contents.svg.js';

Jodit.modules.Icon.set('tableOfContents', tableOfContentsIcon);

const TOC_SELECTOR = 'nav.pmf-toc';

interface TocEntry {
  level: number;
  id: string;
  text: string;
}

interface TocList {
  level: number;
  list: HTMLUListElement;
}

const slugify = (text: string): string =>
  text
    .normalize('NFKC')
    .toLowerCase()
    .replace(/[^\p{L}\p{N}\s_-]/gu, '')
    .trim()
    .replace(/[\s_-]+/g, '-');

const uniqueId = (base: string, usedIds: Set<string>): string => {
  let id = base;
  for (let suffix = 2; usedIds.has(id); suffix++) {
    id = `${base}-${suffix}`;
  }

  return id;
};

const collectEntries = (root: HTMLElement): TocEntry[] => {
  const usedIds = new Set(Array.from(root.querySelectorAll('[id]'), (element) => element.id));

  return Array.from(root.querySelectorAll<HTMLHeadingElement>('h1, h2, h3, h4, h5, h6'))
    .filter((heading) => heading.closest(TOC_SELECTOR) === null)
    .map((heading) => ({ heading, text: (heading.textContent ?? '').trim() }))
    .filter(({ text }) => text !== '')
    .map(({ heading, text }) => {
      if (heading.id === '') {
        heading.id = uniqueId(`toc-${slugify(text) || 'heading'}`, usedIds);
        usedIds.add(heading.id);
      }

      return { level: Number(heading.tagName.substring(1)), id: heading.id, text };
    });
};

const createItem = (doc: Document, entry: TocEntry): HTMLLIElement => {
  const link = doc.createElement('a');
  link.href = `#${entry.id}`;
  link.textContent = entry.text;

  const item = doc.createElement('li');
  item.appendChild(link);

  return item;
};

const renderList = (doc: Document, entries: TocEntry[]): HTMLUListElement => {
  const rootList = doc.createElement('ul');
  const stack: TocList[] = [{ level: entries[0].level, list: rootList }];

  for (const entry of entries) {
    let current = stack[stack.length - 1];

    if (entry.level > current.level && current.list.lastElementChild) {
      const subList = doc.createElement('ul');
      current.list.lastElementChild.appendChild(subList);
      current = { level: entry.level, list: subList };
      stack.push(current);
    }

    while (stack.length > 1 && entry.level < current.level) {
      const closed = stack.pop() as TocList;
      current = stack[stack.length - 1];

      // A heading between two levels (h3 after h2 > h4) joins the list it closed
      if (entry.level > current.level) {
        closed.level = entry.level;
        stack.push(closed);
        current = closed;
        break;
      }
    }

    current.list.appendChild(createItem(doc, entry));
  }

  return rootList;
};

/**
 * Builds the table of contents for the headings below root and assigns missing heading ids.
 * Returns null if root has no headings with text.
 */
export const buildTableOfContents = (root: HTMLElement): HTMLElement | null => {
  const entries = collectEntries(root);
  if (entries.length === 0) {
    return null;
  }

  const doc = root.ownerDocument;
  const nav = doc.createElement('nav');
  nav.className = 'pmf-toc';
  nav.appendChild(renderList(doc, entries));

  return nav;
};

Jodit.plugins.add('tableOfContents', (editor: Jodit): void => {
  editor.registerButton({
    name: 'tableOfContents',
    group: 'insert',
    options: {
      tooltip: 'Insert Table of Contents',
    },
  });

  editor.registerCommand('tableOfContents', (): void => {
    const tableOfContents = buildTableOfContents(editor.editor);
    if (!tableOfContents) {
      editor.message.info('No headings found. Add headings to the content first, then insert the table of contents.');
      return;
    }

    const existing = editor.editor.querySelector(TOC_SELECTOR);
    if (existing) {
      existing.replaceWith(tableOfContents);
      editor.synchronizeValues();
      return;
    }

    editor.selection.insertHTML(tableOfContents.outerHTML);
  });
});
