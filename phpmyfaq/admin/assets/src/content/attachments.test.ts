import { describe, it, expect, vi, beforeEach, Mock } from 'vitest';
import { handleDeleteAttachments, handleRefreshAttachments } from './attachments';
import { deleteAttachments, refreshAttachments } from '../api';
import { pushErrorNotification, pushNotification } from '../../../../assets/src/utils';

vi.mock('../api');
vi.mock('../../../../assets/src/utils');

describe('Attachment Functions', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    document.body.innerHTML = '';
  });

  describe('handleDeleteAttachments', () => {
    it('should do nothing when no delete buttons exist', () => {
      document.body.innerHTML = '<div></div>';

      handleDeleteAttachments();

      expect(deleteAttachments).not.toHaveBeenCalled();
    });

    it('should call deleteAttachments with correct parameters on button click', async () => {
      document.body.innerHTML = `
        <button class="btn-delete-attachment" data-attachment-id="42" data-csrf="csrf-token-123">Delete</button>
        <div id="attachment_42"></div>
      `;

      (deleteAttachments as Mock).mockResolvedValue({ success: 'Attachment deleted' });

      handleDeleteAttachments();

      const button = document.querySelector('.btn-delete-attachment') as HTMLButtonElement;
      button.click();

      await new Promise((resolve) => setTimeout(resolve, 10));

      expect(deleteAttachments).toHaveBeenCalledWith('42', 'csrf-token-123');
    });

    it('should show success notification and fade out row on successful delete', async () => {
      document.body.innerHTML = `
        <button class="btn-delete-attachment" data-attachment-id="42" data-csrf="csrf-token-123">Delete</button>
        <div id="attachment_42">Attachment row</div>
      `;

      (deleteAttachments as Mock).mockResolvedValue({ success: 'Attachment deleted' });

      handleDeleteAttachments();

      const button = document.querySelector('.btn-delete-attachment') as HTMLButtonElement;
      button.click();

      await new Promise((resolve) => setTimeout(resolve, 10));

      expect(pushNotification).toHaveBeenCalledWith('Attachment deleted');

      const row = document.getElementById('attachment_42') as HTMLElement;
      expect(row.style.opacity).toBe('0');
    });

    it('should remove row element on transitionend after delete', async () => {
      document.body.innerHTML = `
        <button class="btn-delete-attachment" data-attachment-id="42" data-csrf="csrf-token-123">Delete</button>
        <div id="attachment_42">Attachment row</div>
      `;

      (deleteAttachments as Mock).mockResolvedValue({ success: 'Attachment deleted' });

      handleDeleteAttachments();

      const button = document.querySelector('.btn-delete-attachment') as HTMLButtonElement;
      button.click();

      await new Promise((resolve) => setTimeout(resolve, 10));

      const row = document.getElementById('attachment_42') as HTMLElement;
      row.dispatchEvent(new Event('transitionend'));

      expect(document.getElementById('attachment_42')).toBeNull();
    });

    it('should show error notification on error response', async () => {
      document.body.innerHTML = `
        <button class="btn-delete-attachment" data-attachment-id="42" data-csrf="csrf-token-123">Delete</button>
        <div id="attachment_42">Attachment row</div>
      `;

      (deleteAttachments as Mock).mockResolvedValue({ error: 'Delete failed' });

      handleDeleteAttachments();

      const button = document.querySelector('.btn-delete-attachment') as HTMLButtonElement;
      button.click();

      await new Promise((resolve) => setTimeout(resolve, 10));

      expect(pushErrorNotification).toHaveBeenCalledWith('Delete failed');
    });

    it('should not call deleteAttachments when attachment-id is missing', async () => {
      document.body.innerHTML = `
        <button class="btn-delete-attachment" data-csrf="csrf-token-123">Delete</button>
      `;

      handleDeleteAttachments();

      const button = document.querySelector('.btn-delete-attachment') as HTMLButtonElement;
      button.click();

      await new Promise((resolve) => setTimeout(resolve, 10));

      expect(deleteAttachments).not.toHaveBeenCalled();
    });

    it('should not call deleteAttachments when csrf is missing', async () => {
      document.body.innerHTML = `
        <button class="btn-delete-attachment" data-attachment-id="42">Delete</button>
      `;

      handleDeleteAttachments();

      const button = document.querySelector('.btn-delete-attachment') as HTMLButtonElement;
      button.click();

      await new Promise((resolve) => setTimeout(resolve, 10));

      expect(deleteAttachments).not.toHaveBeenCalled();
    });

    it('should handle multiple delete buttons independently', async () => {
      document.body.innerHTML = `
        <button class="btn-delete-attachment" data-attachment-id="1" data-csrf="csrf-1">Delete 1</button>
        <button class="btn-delete-attachment" data-attachment-id="2" data-csrf="csrf-2">Delete 2</button>
        <div id="attachment_1">Row 1</div>
        <div id="attachment_2">Row 2</div>
      `;

      (deleteAttachments as Mock).mockResolvedValue({ success: 'Deleted' });

      handleDeleteAttachments();

      const buttons = document.querySelectorAll('.btn-delete-attachment');
      (buttons[1] as HTMLButtonElement).click();

      await new Promise((resolve) => setTimeout(resolve, 10));

      expect(deleteAttachments).toHaveBeenCalledWith('2', 'csrf-2');
    });

    it('should replace buttons with clones to remove old event listeners', () => {
      document.body.innerHTML = `
        <button class="btn-delete-attachment" data-attachment-id="1" data-csrf="csrf-1">Delete</button>
      `;

      const originalButton = document.querySelector('.btn-delete-attachment') as HTMLButtonElement;
      const addEventListenerSpy = vi.spyOn(originalButton, 'addEventListener');

      handleDeleteAttachments();

      // The original button should NOT have a new listener (it was replaced by a clone)
      expect(addEventListenerSpy).not.toHaveBeenCalled();

      // A new button should exist in the DOM
      const newButton = document.querySelector('.btn-delete-attachment') as HTMLButtonElement;
      expect(newButton).not.toBeNull();
    });
  });

  describe('handleRefreshAttachments', () => {
    it('should do nothing when no refresh buttons exist', () => {
      document.body.innerHTML = '<div></div>';

      handleRefreshAttachments();

      expect(refreshAttachments).not.toHaveBeenCalled();
    });

    it('should call refreshAttachments with correct parameters on button click', async () => {
      document.body.innerHTML = `
        <button class="btn-refresh-attachment" data-attachment-id="42" data-csrf="csrf-token-123">Refresh</button>
        <div id="attachment_42">Attachment row</div>
      `;

      (refreshAttachments as Mock).mockResolvedValue({ success: 'Attachment refreshed' });

      handleRefreshAttachments();

      const button = document.querySelector('.btn-refresh-attachment') as HTMLButtonElement;
      button.click();

      await new Promise((resolve) => setTimeout(resolve, 10));

      expect(refreshAttachments).toHaveBeenCalledWith('42', 'csrf-token-123');
    });

    it('should show success notification on successful refresh', async () => {
      document.body.innerHTML = `
        <button class="btn-refresh-attachment" data-attachment-id="42" data-csrf="csrf-token-123">Refresh</button>
      `;

      (refreshAttachments as Mock).mockResolvedValue({ success: 'Attachment refreshed' });

      handleRefreshAttachments();

      const button = document.querySelector('.btn-refresh-attachment') as HTMLButtonElement;
      button.click();

      await new Promise((resolve) => setTimeout(resolve, 10));

      expect(pushNotification).toHaveBeenCalledWith('Attachment refreshed');
    });

    it('should fade out and remove row when response includes delete flag', async () => {
      document.body.innerHTML = `
        <button class="btn-refresh-attachment" data-attachment-id="42" data-csrf="csrf-token-123">Refresh</button>
        <div id="attachment_42">Attachment row</div>
      `;

      (refreshAttachments as Mock).mockResolvedValue({ success: 'Refreshed', delete: true });

      handleRefreshAttachments();

      const button = document.querySelector('.btn-refresh-attachment') as HTMLButtonElement;
      button.click();

      await new Promise((resolve) => setTimeout(resolve, 10));

      const row = document.getElementById('attachment_42') as HTMLElement;
      expect(row.style.opacity).toBe('0');

      row.dispatchEvent(new Event('transitionend'));
      expect(document.getElementById('attachment_42')).toBeNull();
    });

    it('should not remove row when response does not include delete flag', async () => {
      document.body.innerHTML = `
        <button class="btn-refresh-attachment" data-attachment-id="42" data-csrf="csrf-token-123">Refresh</button>
        <div id="attachment_42">Attachment row</div>
      `;

      (refreshAttachments as Mock).mockResolvedValue({ success: 'Refreshed' });

      handleRefreshAttachments();

      const button = document.querySelector('.btn-refresh-attachment') as HTMLButtonElement;
      button.click();

      await new Promise((resolve) => setTimeout(resolve, 10));

      const row = document.getElementById('attachment_42') as HTMLElement;
      expect(row.style.opacity).not.toBe('0');
    });

    it('should show error notification on error response', async () => {
      document.body.innerHTML = `
        <button class="btn-refresh-attachment" data-attachment-id="42" data-csrf="csrf-token-123">Refresh</button>
      `;

      (refreshAttachments as Mock).mockResolvedValue({ error: 'Refresh failed' });

      handleRefreshAttachments();

      const button = document.querySelector('.btn-refresh-attachment') as HTMLButtonElement;
      button.click();

      await new Promise((resolve) => setTimeout(resolve, 10));

      expect(pushErrorNotification).toHaveBeenCalledWith('Refresh failed');
    });

    it('should not call refreshAttachments when attachment-id is missing', async () => {
      document.body.innerHTML = `
        <button class="btn-refresh-attachment" data-csrf="csrf-token-123">Refresh</button>
      `;

      handleRefreshAttachments();

      const button = document.querySelector('.btn-refresh-attachment') as HTMLButtonElement;
      button.click();

      await new Promise((resolve) => setTimeout(resolve, 10));

      expect(refreshAttachments).not.toHaveBeenCalled();
    });

    it('should not call refreshAttachments when csrf is missing', async () => {
      document.body.innerHTML = `
        <button class="btn-refresh-attachment" data-attachment-id="42">Refresh</button>
      `;

      handleRefreshAttachments();

      const button = document.querySelector('.btn-refresh-attachment') as HTMLButtonElement;
      button.click();

      await new Promise((resolve) => setTimeout(resolve, 10));

      expect(refreshAttachments).not.toHaveBeenCalled();
    });

    it('should handle multiple refresh buttons independently', async () => {
      document.body.innerHTML = `
        <button class="btn-refresh-attachment" data-attachment-id="1" data-csrf="csrf-1">Refresh 1</button>
        <button class="btn-refresh-attachment" data-attachment-id="2" data-csrf="csrf-2">Refresh 2</button>
      `;

      (refreshAttachments as Mock).mockResolvedValue({ success: 'Refreshed' });

      handleRefreshAttachments();

      const buttons = document.querySelectorAll('.btn-refresh-attachment');
      (buttons[0] as HTMLButtonElement).click();

      await new Promise((resolve) => setTimeout(resolve, 10));

      expect(refreshAttachments).toHaveBeenCalledWith('1', 'csrf-1');
    });

    it('should replace buttons with clones to remove old event listeners', () => {
      document.body.innerHTML = `
        <button class="btn-refresh-attachment" data-attachment-id="1" data-csrf="csrf-1">Refresh</button>
      `;

      const originalButton = document.querySelector('.btn-refresh-attachment') as HTMLButtonElement;
      const addEventListenerSpy = vi.spyOn(originalButton, 'addEventListener');

      handleRefreshAttachments();

      expect(addEventListenerSpy).not.toHaveBeenCalled();

      const newButton = document.querySelector('.btn-refresh-attachment') as HTMLButtonElement;
      expect(newButton).not.toBeNull();
    });
  });
});
