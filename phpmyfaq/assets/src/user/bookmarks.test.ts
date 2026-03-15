import { beforeEach, describe, expect, it, vi } from 'vitest';

vi.mock('../api', () => ({
  deleteBookmark: vi.fn(),
  deleteAllBookmarks: vi.fn(),
}));

vi.mock('../utils', () => ({
  pushNotification: vi.fn(),
  pushErrorNotification: vi.fn(),
}));

import { deleteAllBookmarks, deleteBookmark } from '../api';
import { pushErrorNotification, pushNotification } from '../utils';
import { handleDeleteBookmarks, handleRemoveAllBookmarks } from './bookmarks';

describe('bookmarks handlers', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    document.body.innerHTML = '';
  });

  describe('handleDeleteBookmarks', () => {
    it('does nothing when no bookmark delete buttons exist', () => {
      handleDeleteBookmarks();

      expect(deleteBookmark).not.toHaveBeenCalled();
    });

    it('deletes a bookmark and removes its DOM element on success', async () => {
      document.body.innerHTML = `
        <div id="delete-bookmark-42">bookmark</div>
        <button class="pmf-delete-bookmark" data-pmf-bookmark-id="42" data-pmf-csrf="csrf-1">Delete</button>
      `;

      vi.mocked(deleteBookmark).mockResolvedValue({ success: 'Bookmark removed' });

      handleDeleteBookmarks();

      const button = document.querySelector('.pmf-delete-bookmark') as HTMLButtonElement;
      button.click();

      await vi.waitFor(() => {
        expect(deleteBookmark).toHaveBeenCalledWith('42', 'csrf-1');
      });

      expect(pushNotification).toHaveBeenCalledWith('Bookmark removed');
      expect(document.getElementById('delete-bookmark-42')).toBeNull();
    });

    it('shows the API error when bookmark deletion fails', async () => {
      document.body.innerHTML = `
        <div id="delete-bookmark-42">bookmark</div>
        <button class="pmf-delete-bookmark" data-pmf-bookmark-id="42" data-pmf-csrf="csrf-1">Delete</button>
      `;

      vi.mocked(deleteBookmark).mockResolvedValue({ success: '', error: 'Delete failed' });

      handleDeleteBookmarks();

      const button = document.querySelector('.pmf-delete-bookmark') as HTMLButtonElement;
      button.click();

      await vi.waitFor(() => {
        expect(deleteBookmark).toHaveBeenCalled();
      });

      expect(pushErrorNotification).toHaveBeenCalledWith('Delete failed');
      expect(document.getElementById('delete-bookmark-42')).not.toBeNull();
    });

    it('falls back to a generic error when the bookmark deletion error is missing', async () => {
      document.body.innerHTML = `
        <button class="pmf-delete-bookmark" data-pmf-bookmark-id="42" data-pmf-csrf="csrf-1">Delete</button>
      `;

      vi.mocked(deleteBookmark).mockResolvedValue({ success: '' });

      handleDeleteBookmarks();

      const button = document.querySelector('.pmf-delete-bookmark') as HTMLButtonElement;
      button.click();

      await vi.waitFor(() => {
        expect(deleteBookmark).toHaveBeenCalled();
      });

      expect(pushErrorNotification).toHaveBeenCalledWith('Unknown error');
    });
  });

  describe('handleRemoveAllBookmarks', () => {
    it('does nothing when the remove-all button does not exist', () => {
      handleRemoveAllBookmarks();

      expect(deleteAllBookmarks).not.toHaveBeenCalled();
    });

    it('deletes all bookmarks and removes the accordion on success', async () => {
      document.body.innerHTML = `
        <div id="bookmarkAccordion">all bookmarks</div>
        <button id="pmf-bookmarks-delete-all" data-pmf-csrf="csrf-2">Delete all</button>
      `;

      vi.mocked(deleteAllBookmarks).mockResolvedValue({ success: 'All bookmarks removed' });

      handleRemoveAllBookmarks();

      const button = document.getElementById('pmf-bookmarks-delete-all') as HTMLButtonElement;
      button.click();

      await vi.waitFor(() => {
        expect(deleteAllBookmarks).toHaveBeenCalledWith('csrf-2');
      });

      expect(pushNotification).toHaveBeenCalledWith('All bookmarks removed');
      expect(document.getElementById('bookmarkAccordion')).toBeNull();
    });

    it('shows the API error when removing all bookmarks fails', async () => {
      document.body.innerHTML = `
        <div id="bookmarkAccordion">all bookmarks</div>
        <button id="pmf-bookmarks-delete-all" data-pmf-csrf="csrf-2">Delete all</button>
      `;

      vi.mocked(deleteAllBookmarks).mockResolvedValue({ success: '', error: 'Bulk delete failed' });

      handleRemoveAllBookmarks();

      const button = document.getElementById('pmf-bookmarks-delete-all') as HTMLButtonElement;
      button.click();

      await vi.waitFor(() => {
        expect(deleteAllBookmarks).toHaveBeenCalled();
      });

      expect(pushErrorNotification).toHaveBeenCalledWith('Bulk delete failed');
      expect(document.getElementById('bookmarkAccordion')).not.toBeNull();
    });

    it('falls back to a generic error when the bulk delete error is missing', async () => {
      document.body.innerHTML = `
        <button id="pmf-bookmarks-delete-all" data-pmf-csrf="csrf-2">Delete all</button>
      `;

      vi.mocked(deleteAllBookmarks).mockResolvedValue({ success: '' });

      handleRemoveAllBookmarks();

      const button = document.getElementById('pmf-bookmarks-delete-all') as HTMLButtonElement;
      button.click();

      await vi.waitFor(() => {
        expect(deleteAllBookmarks).toHaveBeenCalled();
      });

      expect(pushErrorNotification).toHaveBeenCalledWith('Unknown error');
    });
  });
});
