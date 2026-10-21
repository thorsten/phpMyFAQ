import { describe, it, expect, beforeEach, vi, type Mock } from 'vitest';
import { handleCategoryDelete } from './category';
import * as api from '../api';
import * as utils from '../../../../assets/src/utils';

vi.mock('bootstrap', () => ({
  Modal: vi.fn(function () {
    return { show: vi.fn(), hide: vi.fn() };
  }),
}));
vi.mock('../api');
vi.mock('../../../../assets/src/utils');

const mockDeleteCategory = api.deleteCategory as Mock;
const mockPushNotification = utils.pushNotification as Mock;
const mockPushErrorNotification = utils.pushErrorNotification as Mock;

const flushPromises = (): Promise<void> => new Promise((resolve) => setTimeout(resolve, 0));

describe('handleCategoryDelete', () => {
  beforeEach(() => {
    vi.clearAllMocks();

    document.body.innerHTML = `
      <input type="hidden" name="pmf-csrf-token" value="token" />
      <ul>
        <li id="pmf-category-45">
          <button name="pmf-category-delete-button" data-pmf-category-id="45" data-pmf-language="en">Delete</button>
        </li>
      </ul>
      <div id="deleteConfirmModal"></div>
      <button id="confirmDeleteButton">Confirm</button>
    `;
  });

  const clickDeleteAndConfirm = async (): Promise<void> => {
    await handleCategoryDelete();
    (document.getElementsByName('pmf-category-delete-button')[0] as HTMLElement).click();
    await flushPromises();
    (document.getElementById('confirmDeleteButton') as HTMLButtonElement).click();
    await flushPromises();
  };

  it('should remove the category row and show a notification on success', async () => {
    mockDeleteCategory.mockResolvedValue({ success: 'Category deleted' });

    await clickDeleteAndConfirm();

    expect(mockDeleteCategory).toHaveBeenCalledWith('45', 'en', 'token');
    expect(mockPushNotification).toHaveBeenCalledWith('Category deleted');
    expect(document.getElementById('pmf-category-45')).toBeNull();
  });

  it('should keep the category row and show an error notification on failure', async () => {
    mockDeleteCategory.mockRejectedValue(new Error('Unexpected server response (HTTP 500)'));

    await clickDeleteAndConfirm();

    expect(mockPushErrorNotification).toHaveBeenCalledWith('Unexpected server response (HTTP 500)');
    expect(document.getElementById('pmf-category-45')).not.toBeNull();
  });
});
