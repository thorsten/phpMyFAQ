import { describe, it, expect, vi, beforeEach, Mock } from 'vitest';
import { handleCategories, handleCategoryDelete, handleResetCategoryImage, handleCategoryTranslate } from './category';
import { deleteCategory } from '../api';
import { pushNotification } from '../../../../assets/src/utils';

vi.mock('sortablejs', () => ({
  default: vi.fn().mockImplementation(() => ({})),
}));
vi.mock('bootstrap', () => ({
  Modal: class {
    show = vi.fn();
    hide = vi.fn();
  },
}));
vi.mock('../api');
vi.mock('../../../../assets/src/utils', async (importOriginal) => {
  const actual = (await importOriginal()) as Record<string, unknown>;
  return {
    ...actual,
    pushNotification: vi.fn(),
    pushErrorNotification: vi.fn(),
  };
});
vi.mock('../translation/translator', () => {
  return {
    Translator: vi.fn(),
  };
});

describe('Category Functions', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    document.body.innerHTML = '';
  });

  describe('handleCategories', () => {
    it('should not throw when no .nested-sortable elements exist', () => {
      document.body.innerHTML = '<div></div>';

      expect(() => handleCategories()).not.toThrow();
    });
  });

  describe('handleCategoryDelete', () => {
    it('should do nothing when modal element is missing', async () => {
      document.body.innerHTML = `
        <button name="pmf-category-delete-button" data-pmf-category-id="1" data-pmf-language="en">Delete</button>
      `;

      await handleCategoryDelete();

      expect(deleteCategory).not.toHaveBeenCalled();
    });

    it('should do nothing when delete buttons are missing', async () => {
      document.body.innerHTML = `
        <div id="deleteConfirmModal"></div>
        <button id="confirmDeleteButton">Confirm</button>
      `;

      await handleCategoryDelete();

      expect(deleteCategory).not.toHaveBeenCalled();
    });

    it('should call deleteCategory, remove element, and show notification on confirm', async () => {
      document.body.innerHTML = `
        <div id="deleteConfirmModal"></div>
        <button id="confirmDeleteButton">Confirm</button>
        <button name="pmf-category-delete-button" data-pmf-category-id="5" data-pmf-language="en">Delete</button>
        <div id="pmf-category-5">Category row</div>
        <input name="pmf-csrf-token" value="csrf-token-abc" />
      `;

      (deleteCategory as Mock).mockResolvedValue({ success: 'Category deleted successfully' });

      await handleCategoryDelete();

      // Click the delete button to open the modal and set the category info
      const deleteButton = document.querySelector('[name="pmf-category-delete-button"]') as HTMLButtonElement;
      deleteButton.click();

      await new Promise((resolve) => setTimeout(resolve, 10));

      // Click the confirm button to trigger deletion
      const confirmButton = document.getElementById('confirmDeleteButton') as HTMLButtonElement;
      confirmButton.click();

      await new Promise((resolve) => setTimeout(resolve, 10));

      expect(deleteCategory).toHaveBeenCalledWith('5', 'en', 'csrf-token-abc');
      expect(pushNotification).toHaveBeenCalledWith('Category deleted successfully');
      expect(document.getElementById('pmf-category-5')).toBeNull();
    });

    it('should not call deleteCategory when categoryId and language are empty', async () => {
      document.body.innerHTML = `
        <div id="deleteConfirmModal"></div>
        <button id="confirmDeleteButton">Confirm</button>
        <button name="pmf-category-delete-button" data-pmf-category-id="" data-pmf-language="">Delete</button>
        <input name="pmf-csrf-token" value="csrf-token-abc" />
      `;

      await handleCategoryDelete();

      // Click the delete button
      const deleteButton = document.querySelector('[name="pmf-category-delete-button"]') as HTMLButtonElement;
      deleteButton.click();

      await new Promise((resolve) => setTimeout(resolve, 10));

      // Click confirm
      const confirmButton = document.getElementById('confirmDeleteButton') as HTMLButtonElement;
      confirmButton.click();

      await new Promise((resolve) => setTimeout(resolve, 10));

      expect(deleteCategory).not.toHaveBeenCalled();
    });
  });

  describe('handleResetCategoryImage', () => {
    it('should not throw when reset button is missing', () => {
      document.body.innerHTML = '<div></div>';

      expect(() => handleResetCategoryImage()).not.toThrow();
    });

    it('should clear image input values and label when reset button is clicked', () => {
      document.body.innerHTML = `
        <button id="button-reset-category-image">Reset</button>
        <input id="pmf-category-existing-image" value="existing-image.png" />
        <input id="pmf-category-image-upload" value="uploaded-image.png" />
        <label id="pmf-category-image-label">Current image: test.png</label>
      `;

      handleResetCategoryImage();

      const resetButton = document.getElementById('button-reset-category-image') as HTMLButtonElement;
      resetButton.click();

      const existingImage = document.getElementById('pmf-category-existing-image') as HTMLInputElement;
      const imageUpload = document.getElementById('pmf-category-image-upload') as HTMLInputElement;
      const imageLabel = document.getElementById('pmf-category-image-label') as HTMLLabelElement;

      expect(existingImage.value).toBe('');
      expect(imageUpload.value).toBe('');
      expect(imageLabel.innerHTML).toBe('');
    });
  });

  describe('handleCategoryTranslate', () => {
    const consoleErrorSpy = vi.spyOn(console, 'error').mockImplementation(() => {});

    beforeEach(() => {
      consoleErrorSpy.mockClear();
    });

    it('should not throw when elements are missing', () => {
      document.body.innerHTML = '<div></div>';

      expect(() => handleCategoryTranslate()).not.toThrow();
    });

    it('should enable translate button when different source and target languages are selected', () => {
      document.body.innerHTML = `
        <button id="btn-translate-category-ai">Translate</button>
        <select id="catlang">
          <option value="en">English</option>
          <option value="de">German</option>
        </select>
        <input id="originalCategoryLang" value="en" />
      `;

      handleCategoryTranslate();

      const translateButton = document.getElementById('btn-translate-category-ai') as HTMLButtonElement;
      expect(translateButton.disabled).toBe(true);

      const langSelect = document.getElementById('catlang') as HTMLSelectElement;
      langSelect.value = 'de';
      langSelect.dispatchEvent(new Event('change'));

      expect(translateButton.disabled).toBe(false);
    });

    it('should disable translate button when same language is selected', () => {
      document.body.innerHTML = `
        <button id="btn-translate-category-ai">Translate</button>
        <select id="catlang">
          <option value="en">English</option>
          <option value="de">German</option>
        </select>
        <input id="originalCategoryLang" value="en" />
      `;

      handleCategoryTranslate();

      const translateButton = document.getElementById('btn-translate-category-ai') as HTMLButtonElement;
      const langSelect = document.getElementById('catlang') as HTMLSelectElement;

      // First select a different language to enable
      langSelect.value = 'de';
      langSelect.dispatchEvent(new Event('change'));
      expect(translateButton.disabled).toBe(false);

      // Then select the same language as source to disable
      langSelect.value = 'en';
      langSelect.dispatchEvent(new Event('change'));
      expect(translateButton.disabled).toBe(true);
    });
  });
});
