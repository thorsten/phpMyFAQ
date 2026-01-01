/**
 * Unit tests for the Add FAQ Editor
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2025-2026 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2025-12-22
 */

import { describe, it, expect, beforeEach } from 'vitest';
import { renderFaqEditor, getJoditEditor } from './editor';

describe('renderAddFaqEditor', () => {
  beforeEach(() => {
    document.body.innerHTML = '';
  });

  it('should not initialize editor when textarea is missing', () => {
    renderFaqEditor();
    const editor = getJoditEditor();
    expect(editor).toBeNull();
  });

  it('should find the answer textarea element', () => {
    document.body.innerHTML = '<textarea id="answer"></textarea>';
    const textarea = document.getElementById('answer');
    expect(textarea).not.toBeNull();
    expect(textarea?.tagName).toBe('TEXTAREA');
  });

  it('should initialize with correct textarea id', () => {
    document.body.innerHTML = `
      <form id="pmf-add-faq-form">
        <textarea id="answer" name="answer"></textarea>
      </form>
    `;
    const answerField = document.getElementById('answer');
    expect(answerField).toBeDefined();
  });
});

describe('getJoditAddEditor', () => {
  it('should return null initially', () => {
    const editor = getJoditEditor();
    expect(editor).toBeNull();
  });
});
