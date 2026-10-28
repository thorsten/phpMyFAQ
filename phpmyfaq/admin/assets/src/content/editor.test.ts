/**
 * Unit tests for the admin Jodit editor
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
 * @since     2026-10-28
 */

import { describe, it, expect, beforeEach } from 'vitest';
import { Jodit } from 'jodit';
import { renderEditor, getJoditEditor } from './editor';

describe('renderEditor', () => {
  beforeEach(() => {
    document.body.innerHTML = '';
  });

  it('should not initialize editor when element is missing', () => {
    renderEditor();
    expect(getJoditEditor()).toBeNull();
  });

  it('should register the table cell plugins so the cell popup can appear', () => {
    // 'table' and 'inlinePopup' are part of the Jodit default bundle, but the
    // plugins that select cells and fire the 'cells' popup are not.
    expect(Jodit.plugins.get('table')).toBeDefined();
    expect(Jodit.plugins.get('inlinePopup')).toBeDefined();
    expect(Jodit.plugins.get('selectCells')).toBeDefined();
    expect(Jodit.plugins.get('resizeCells')).toBeDefined();
    expect(Jodit.plugins.get('tableKeyboardNavigation')).toBeDefined();
  });
});
