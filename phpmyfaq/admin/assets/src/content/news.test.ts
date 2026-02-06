import { describe, it, expect, vi, beforeEach, Mock } from 'vitest';
import { handleAddNews, handleEditNews, handleNews } from './news';
import { addNews, deleteNews, activateNews, updateNews } from '../api';
import { pushErrorNotification, pushNotification } from '../../../../assets/src/utils';

vi.mock('../api');
vi.mock('../../../../assets/src/utils');
vi.mock('bootstrap', () => ({
  Modal: vi.fn().mockImplementation(() => ({
    show: vi.fn(),
    hide: vi.fn(),
  })),
}));

const setupAddNewsDom = (): void => {
  document.body.innerHTML = `
    <button id="submitAddNews">Submit</button>
    <input id="editor" value="News content" />
    <input id="newsheader" value="News Header" />
    <input id="authorName" value="Author" />
    <input id="authorEmail" value="author@test.com" />
    <input id="active" type="checkbox" checked />
    <input id="comment" type="checkbox" />
    <input id="link" value="https://example.com" />
    <input id="linkTitle" value="Example" />
    <input id="langTo" value="en" />
    <input id="target" name="target" type="radio" value="_blank" checked />
    <input id="pmf-csrf-token" value="test-csrf" />
  `;
};

const setupEditNewsDom = (): void => {
  document.body.innerHTML = `
    <button id="submitEditNews">Submit</button>
    <input id="id" value="42" />
    <input id="editor" value="Updated content" />
    <input id="newsheader" value="Updated Header" />
    <input id="authorName" value="Author" />
    <input id="authorEmail" value="author@test.com" />
    <input id="active" type="checkbox" checked />
    <input id="comment" type="checkbox" />
    <input id="link" value="https://example.com" />
    <input id="linkTitle" value="Example" />
    <input id="langTo" value="en" />
    <input id="target" name="target" type="radio" value="_blank" checked />
    <input id="pmf-csrf-token" value="test-csrf" />
  `;
};

const setupNewsListDom = (): void => {
  document.body.innerHTML = `
    <button id="deleteNews" data-pmf-newsid="42">Delete</button>
    <div id="confirmDeleteNewsModal"><div class="modal-dialog"></div></div>
    <input id="newsId" value="" />
    <input id="pmf-csrf-token-delete" value="delete-csrf" />
    <button id="pmf-delete-news-action">Confirm Delete</button>
    <input id="activate" type="checkbox" data-pmf-id="42" data-pmf-csrf-token="activate-csrf" />
  `;
};

describe('News Functions', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    document.body.innerHTML = '';
  });

  describe('handleAddNews', () => {
    it('should do nothing when submit button is missing', () => {
      document.body.innerHTML = '<div></div>';

      handleAddNews();

      expect(addNews).not.toHaveBeenCalled();
    });

    it('should call addNews with form data and show success notification', async () => {
      setupAddNewsDom();

      (addNews as Mock).mockResolvedValue({ success: 'News added successfully' });

      handleAddNews();

      const button = document.getElementById('submitAddNews') as HTMLButtonElement;
      button.click();

      await new Promise((resolve) => setTimeout(resolve, 10));

      expect(addNews).toHaveBeenCalled();
      expect(pushNotification).toHaveBeenCalledWith('News added successfully');
    });

    it('should show error notification on failure', async () => {
      setupAddNewsDom();

      (addNews as Mock).mockResolvedValue({ error: 'Failed to add news' });

      handleAddNews();

      const button = document.getElementById('submitAddNews') as HTMLButtonElement;
      button.click();

      await new Promise((resolve) => setTimeout(resolve, 10));

      expect(pushErrorNotification).toHaveBeenCalledWith('Failed to add news');
    });

    it('should show default error when no error message provided', async () => {
      setupAddNewsDom();

      (addNews as Mock).mockResolvedValue({});

      handleAddNews();

      const button = document.getElementById('submitAddNews') as HTMLButtonElement;
      button.click();

      await new Promise((resolve) => setTimeout(resolve, 10));

      expect(pushErrorNotification).toHaveBeenCalledWith('An error occurred');
    });
  });

  describe('handleEditNews', () => {
    it('should do nothing when submit button is missing', () => {
      document.body.innerHTML = '<div></div>';

      handleEditNews();

      expect(updateNews).not.toHaveBeenCalled();
    });

    it('should call updateNews with form data and show success notification', async () => {
      setupEditNewsDom();

      (updateNews as Mock).mockResolvedValue({ success: 'News updated successfully' });

      handleEditNews();

      const button = document.getElementById('submitEditNews') as HTMLButtonElement;
      button.click();

      await new Promise((resolve) => setTimeout(resolve, 10));

      expect(updateNews).toHaveBeenCalled();
      expect(pushNotification).toHaveBeenCalledWith('News updated successfully');
    });

    it('should show error notification on failure', async () => {
      setupEditNewsDom();

      (updateNews as Mock).mockResolvedValue({ error: 'Update failed' });

      handleEditNews();

      const button = document.getElementById('submitEditNews') as HTMLButtonElement;
      button.click();

      await new Promise((resolve) => setTimeout(resolve, 10));

      expect(pushErrorNotification).toHaveBeenCalledWith('Update failed');
    });
  });

  describe('handleNews', () => {
    it('should do nothing when deleteNews button is missing', () => {
      document.body.innerHTML = '<div></div>';

      handleNews();

      expect(deleteNews).not.toHaveBeenCalled();
    });

    it('should delete news on confirm and show success notification', async () => {
      setupNewsListDom();

      (deleteNews as Mock).mockResolvedValue({ success: 'News deleted' });

      handleNews();

      // Click the delete action button directly (simulating modal confirm)
      const deleteAction = document.getElementById('pmf-delete-news-action') as HTMLButtonElement;
      // First set the newsId as the modal would
      (document.getElementById('newsId') as HTMLInputElement).value = '42';
      deleteAction.click();

      await new Promise((resolve) => setTimeout(resolve, 10));

      expect(deleteNews).toHaveBeenCalledWith('delete-csrf', '42');
      expect(pushNotification).toHaveBeenCalledWith('News deleted');
    });

    it('should show error notification when delete fails', async () => {
      setupNewsListDom();

      (deleteNews as Mock).mockResolvedValue({ error: 'Delete failed' });

      handleNews();

      (document.getElementById('newsId') as HTMLInputElement).value = '42';
      const deleteAction = document.getElementById('pmf-delete-news-action') as HTMLButtonElement;
      deleteAction.click();

      await new Promise((resolve) => setTimeout(resolve, 10));

      expect(pushErrorNotification).toHaveBeenCalledWith('Delete failed');
    });

    it('should activate news and show success notification', async () => {
      setupNewsListDom();

      (activateNews as Mock).mockResolvedValue({ success: 'News activated' });

      handleNews();

      const checkbox = document.getElementById('activate') as HTMLInputElement;
      checkbox.click();

      await new Promise((resolve) => setTimeout(resolve, 10));

      expect(activateNews).toHaveBeenCalledWith('42', String(checkbox.checked), 'activate-csrf');
      expect(pushNotification).toHaveBeenCalledWith('News activated');
    });

    it('should show error notification when activation fails', async () => {
      setupNewsListDom();

      (activateNews as Mock).mockResolvedValue({ error: 'Activation failed' });

      handleNews();

      const checkbox = document.getElementById('activate') as HTMLInputElement;
      checkbox.click();

      await new Promise((resolve) => setTimeout(resolve, 10));

      expect(pushErrorNotification).toHaveBeenCalledWith('Activation failed');
    });
  });
});
