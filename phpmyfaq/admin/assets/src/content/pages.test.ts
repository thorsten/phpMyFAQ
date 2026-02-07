import { describe, it, expect, vi, beforeEach, Mock } from 'vitest';
import { handleAddPage, handleEditPage, handlePages, handleTranslatePage } from './pages';
import { addPage, deletePage, updatePage, activatePage } from '../api';
import { pushNotification } from '../../../../assets/src/utils';

vi.mock('../api');
vi.mock('./editor', () => ({
  renderPageEditor: vi.fn(),
}));
vi.mock('../translation/translator', () => ({
  Translator: vi.fn(),
}));
vi.mock('../../../../assets/src/utils', async (importOriginal) => {
  const actual = (await importOriginal()) as Record<string, unknown>;
  return {
    ...actual,
    pushNotification: vi.fn(),
    pushErrorNotification: vi.fn(),
  };
});
vi.mock('bootstrap', () => {
  const ModalMock = vi.fn();
  ModalMock.prototype.show = vi.fn();
  ModalMock.prototype.hide = vi.fn();
  return { Modal: ModalMock };
});

const flushPromises = async (): Promise<void> => {
  await new Promise((resolve) => setTimeout(resolve, 10));
};

const setupAddPageDom = (): void => {
  document.body.innerHTML = `
    <form id="pmf-add-page-form">
      <input id="pageTitle" value="Test Page" />
      <input id="slug" value="" />
      <textarea id="content">Page content</textarea>
      <input id="authorName" value="Author" />
      <input id="authorEmail" value="author@test.com" />
      <input id="active" type="checkbox" checked />
      <select id="lang"><option value="en" selected>English</option></select>
      <input id="seoTitle" value="SEO Title" />
      <textarea id="seoDescription">SEO Description</textarea>
      <select id="seoRobots"><option value="index,follow" selected>Index, Follow</option></select>
      <input id="pmf-csrf-token" value="test-csrf" />
      <button id="pmf-submit-page">Submit</button>
      <span id="seo-title-counter">0</span>
      <span id="seo-description-counter">0</span>
    </form>
  `;
};

const setupEditPageDom = (): void => {
  document.body.innerHTML = `
    <form id="pmf-edit-page-form">
      <input id="pageId" value="42" />
      <input id="pageTitle" value="Edit Page" />
      <input id="slug" value="edit-page" />
      <textarea id="content">Edit content</textarea>
      <input id="authorName" value="Author" />
      <input id="authorEmail" value="author@test.com" />
      <input id="active" type="checkbox" checked />
      <input id="lang" value="en" />
      <input id="seoTitle" value="SEO Title" />
      <textarea id="seoDescription">SEO Description</textarea>
      <select id="seoRobots"><option value="index,follow" selected>Index, Follow</option></select>
      <input id="pmf-csrf-token" value="test-csrf" />
      <button id="pmf-submit-page">Submit</button>
      <span id="seo-title-counter">0</span>
      <span id="seo-description-counter">0</span>
    </form>
  `;
};

const setupPagesListDom = (): void => {
  document.body.innerHTML = `
    <button id="deletePage" data-pmf-pageid="42" data-pmf-lang="en">Delete</button>
    <div id="confirmDeletePageModal"><div class="modal-dialog"></div></div>
    <input id="pageId" value="" />
    <input id="pageLang" value="" />
    <input id="pmf-csrf-token-delete" value="delete-csrf" />
    <button id="pmf-delete-page-action">Confirm Delete</button>
    <input id="activate" type="checkbox" data-pmf-id="42" data-pmf-csrf-token="activate-csrf" />
  `;
};

const setupTranslatePageDom = (): void => {
  document.body.innerHTML = `
    <form id="pmf-translate-page-form">
      <input id="pageId" value="42" />
      <input id="pageTitle" value="Translated Page" />
      <input id="slug" value="" />
      <textarea id="content">Translated content</textarea>
      <input id="authorName" value="Author" />
      <input id="authorEmail" value="author@test.com" />
      <input id="active" type="checkbox" checked />
      <select id="lang"><option value="de" selected>German</option></select>
      <input id="originalLang" value="en" />
      <input id="seoTitle" value="SEO Title" />
      <textarea id="seoDescription">SEO Description</textarea>
      <select id="seoRobots"><option value="index,follow" selected>Index, Follow</option></select>
      <input id="pmf-csrf-token" value="test-csrf" />
      <button id="pmf-submit-page">Submit</button>
      <button id="btn-translate-page-ai">Translate with AI</button>
      <span id="seo-title-counter">0</span>
      <span id="seo-description-counter">0</span>
    </form>
  `;
};

describe('Pages Functions', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    document.body.innerHTML = '';
  });

  describe('handleAddPage', () => {
    it('should do nothing when form is missing', () => {
      document.body.innerHTML = '<div></div>';

      handleAddPage();

      expect(addPage).not.toHaveBeenCalled();
    });

    it('should auto-generate slug from title input', () => {
      setupAddPageDom();

      handleAddPage();

      const titleInput = document.getElementById('pageTitle') as HTMLInputElement;
      const slugInput = document.getElementById('slug') as HTMLInputElement;

      titleInput.value = 'My New Page Title';
      titleInput.dispatchEvent(new Event('input'));

      expect(slugInput.value).toBe('my-new-page-title');
      expect(slugInput.dataset.autoGenerated).toBe('true');
    });

    it('should stop auto-generating when user manually edits slug', () => {
      setupAddPageDom();

      handleAddPage();

      const titleInput = document.getElementById('pageTitle') as HTMLInputElement;
      const slugInput = document.getElementById('slug') as HTMLInputElement;

      // First auto-generate
      titleInput.value = 'Initial Title';
      titleInput.dispatchEvent(new Event('input'));
      expect(slugInput.value).toBe('initial-title');

      // User manually edits slug
      slugInput.value = 'custom-slug';
      slugInput.dispatchEvent(new Event('input'));
      expect(slugInput.dataset.autoGenerated).toBe('false');

      // Typing more in title should not overwrite the manual slug
      titleInput.value = 'Updated Title';
      titleInput.dispatchEvent(new Event('input'));
      expect(slugInput.value).toBe('custom-slug');
    });

    it('should call addPage and show notification on success', async () => {
      setupAddPageDom();

      (addPage as Mock).mockResolvedValue({ success: 'Page added successfully' });

      handleAddPage();

      const button = document.getElementById('pmf-submit-page') as HTMLButtonElement;
      button.click();

      await flushPromises();

      expect(addPage).toHaveBeenCalled();
      expect(pushNotification).toHaveBeenCalledWith('Page added successfully');
    });
  });

  describe('handleEditPage', () => {
    it('should do nothing when form is missing', () => {
      document.body.innerHTML = '<div></div>';

      handleEditPage();

      expect(updatePage).not.toHaveBeenCalled();
    });

    it('should call updatePage with form data on submission', async () => {
      setupEditPageDom();

      (updatePage as Mock).mockResolvedValue({ success: 'Page updated successfully' });

      handleEditPage();

      const button = document.getElementById('pmf-submit-page') as HTMLButtonElement;
      button.click();

      await flushPromises();

      expect(updatePage).toHaveBeenCalled();
      expect(pushNotification).toHaveBeenCalledWith('Page updated successfully');
    });
  });

  describe('handlePages', () => {
    it('should do nothing when no delete buttons exist', () => {
      document.body.innerHTML = '<div></div>';

      handlePages();

      expect(deletePage).not.toHaveBeenCalled();
      expect(activatePage).not.toHaveBeenCalled();
    });

    it('should call activatePage when checkbox is clicked', async () => {
      setupPagesListDom();

      (activatePage as Mock).mockResolvedValue({ success: 'Page activated' });

      handlePages();

      const checkbox = document.getElementById('activate') as HTMLInputElement;
      checkbox.click();

      await flushPromises();

      expect(activatePage).toHaveBeenCalledWith('42', checkbox.checked, 'activate-csrf');
      expect(pushNotification).toHaveBeenCalledWith('Page activated');
    });

    it('should open modal when delete button is clicked', () => {
      setupPagesListDom();

      handlePages();

      const deleteButton = document.getElementById('deletePage') as HTMLButtonElement;
      deleteButton.click();

      const pageIdInput = document.getElementById('pageId') as HTMLInputElement;
      const pageLangInput = document.getElementById('pageLang') as HTMLInputElement;

      expect(pageIdInput.value).toBe('42');
      expect(pageLangInput.value).toBe('en');
    });
  });

  describe('handleTranslatePage', () => {
    it('should do nothing when form is missing', () => {
      document.body.innerHTML = '<div></div>';

      handleTranslatePage();

      expect(addPage).not.toHaveBeenCalled();
    });

    it('should call addPage on form submission', async () => {
      setupTranslatePageDom();

      (addPage as Mock).mockResolvedValue({ success: 'Translation added successfully' });

      handleTranslatePage();

      const button = document.getElementById('pmf-submit-page') as HTMLButtonElement;
      button.click();

      await flushPromises();

      expect(addPage).toHaveBeenCalled();
      expect(pushNotification).toHaveBeenCalledWith('Translation added successfully');
    });
  });
});
