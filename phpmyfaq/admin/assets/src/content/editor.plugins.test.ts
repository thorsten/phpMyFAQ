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
