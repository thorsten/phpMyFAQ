import { describe, it, expect, vi, beforeEach } from 'vitest';
import { handleDirtyState, isFaqEditorDirty, markClean, markDirty } from './faqs.editor.state';

vi.mock('./editor', () => ({
  getJoditEditor: vi.fn(() => null),
}));

describe('faqs.editor.state', () => {
  beforeEach(() => {
    document.body.innerHTML = '';
    markClean();
  });

  describe('markDirty / markClean / isFaqEditorDirty', () => {
    it('should toggle the dirty flag', () => {
      expect(isFaqEditorDirty()).toBe(false);

      markDirty();
      expect(isFaqEditorDirty()).toBe(true);

      markClean();
      expect(isFaqEditorDirty()).toBe(false);
    });
  });

  describe('handleDirtyState', () => {
    it('should do nothing when the FAQ editor form is missing', () => {
      document.body.innerHTML = '<input id="other" />';

      handleDirtyState();

      const input = document.getElementById('other') as HTMLInputElement;
      input.value = 'changed';
      input.dispatchEvent(new Event('input', { bubbles: true }));

      expect(isFaqEditorDirty()).toBe(false);
    });

    it('should mark dirty on input to a form field', () => {
      document.body.innerHTML = `
        <form id="faqEditor">
          <input id="question" name="question" value="" />
        </form>
      `;

      handleDirtyState();

      const input = document.getElementById('question') as HTMLInputElement;
      input.value = 'What is phpMyFAQ?';
      input.dispatchEvent(new Event('input', { bubbles: true }));

      expect(isFaqEditorDirty()).toBe(true);
    });

    it('should mark dirty on change of a form-associated control outside the form element', () => {
      document.body.innerHTML = `
        <form id="faqEditor"></form>
        <input type="checkbox" id="sticky" name="sticky" form="faqEditor" />
      `;

      handleDirtyState();

      const checkbox = document.getElementById('sticky') as HTMLInputElement;
      checkbox.checked = true;
      checkbox.dispatchEvent(new Event('change', { bubbles: true }));

      expect(isFaqEditorDirty()).toBe(true);
    });

    it('should not mark dirty for unnamed helper controls inside the form', () => {
      document.body.innerHTML = `
        <form id="faqEditor">
          <input type="search" id="pmf-faq-category-filter" />
          <input type="file" id="pmf-attachment-dropzone-input" />
        </form>
      `;

      handleDirtyState();

      const filter = document.getElementById('pmf-faq-category-filter') as HTMLInputElement;
      filter.value = 'general';
      filter.dispatchEvent(new Event('input', { bubbles: true }));

      const fileInput = document.getElementById('pmf-attachment-dropzone-input') as HTMLInputElement;
      fileInput.dispatchEvent(new Event('change', { bubbles: true }));

      expect(isFaqEditorDirty()).toBe(false);
    });

    it('should not mark dirty for fields of other forms', () => {
      document.body.innerHTML = `
        <form id="faqEditor"></form>
        <form id="selectRevision">
          <select id="selectedRevisionId" name="selectedRevisionId"><option value="1">1</option></select>
        </form>
      `;

      handleDirtyState();

      const select = document.getElementById('selectedRevisionId') as HTMLSelectElement;
      select.dispatchEvent(new Event('change', { bubbles: true }));

      expect(isFaqEditorDirty()).toBe(false);
    });

    it('should warn on beforeunload only when dirty', () => {
      document.body.innerHTML = '<form id="faqEditor"><input id="question" name="question" /></form>';

      handleDirtyState();

      const cleanEvent = new Event('beforeunload', { cancelable: true }) as BeforeUnloadEvent;
      window.dispatchEvent(cleanEvent);
      expect(cleanEvent.defaultPrevented).toBe(false);

      markDirty();

      const dirtyEvent = new Event('beforeunload', { cancelable: true }) as BeforeUnloadEvent;
      window.dispatchEvent(dirtyEvent);
      expect(dirtyEvent.defaultPrevented).toBe(true);
    });

    it('should reset the dirty flag when initialized', () => {
      document.body.innerHTML = '<form id="faqEditor"></form>';

      markDirty();
      handleDirtyState();

      expect(isFaqEditorDirty()).toBe(false);
    });
  });
});
