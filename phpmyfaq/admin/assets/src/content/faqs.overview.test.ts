import { describe, it, expect, vi, beforeEach, Mock } from 'vitest';
import { handleDeleteFaqModal, handleFaqOverview } from './faqs.overview';
import { deleteFaq, fetchAllFaqsByCategory } from '../api';
import { pushErrorNotification, pushNotification } from '../../../../assets/src/utils';

const mockModalShow = vi.fn();
const mockModalHide = vi.fn();

vi.mock('bootstrap', () => {
  const ModalClass = vi.fn().mockImplementation(function (this: Record<string, unknown>) {
    this.show = mockModalShow;
    this.hide = mockModalHide;
  });
  return { Modal: ModalClass };
});
vi.mock('../api');
vi.mock('../utils');
vi.mock('../../../../assets/src/utils', async (importOriginal) => {
  const actual = (await importOriginal()) as Record<string, unknown>;
  return {
    ...actual,
    pushNotification: vi.fn(),
    pushErrorNotification: vi.fn(),
  };
});

const flushPromises = async (): Promise<void> => {
  await new Promise((resolve) => setTimeout(resolve, 50));
};

describe('FAQ Overview Functions', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    document.body.innerHTML = '';
    localStorage.clear();
    vi.spyOn(console, 'error').mockImplementation(() => {});
  });

  describe('handleDeleteFaqModal', () => {
    it('should do nothing when modal element is missing', () => {
      document.body.innerHTML = '<div></div>';

      handleDeleteFaqModal();

      expect(deleteFaq).not.toHaveBeenCalled();
    });

    it('should store data and show modal when .pmf-button-delete-faq is clicked', async () => {
      document.body.innerHTML = `
        <div id="deleteFaqConfirmModal"></div>
        <button id="confirmDeleteFaqButton">Confirm</button>
        <button class="pmf-button-delete-faq"
                data-pmf-id="42"
                data-pmf-language="en"
                data-pmf-token="csrf-abc">Delete</button>
      `;

      handleDeleteFaqModal();

      const deleteButton = document.querySelector('.pmf-button-delete-faq') as HTMLElement;
      deleteButton.click();

      await flushPromises();

      expect(mockModalShow).toHaveBeenCalled();
    });

    it('should call deleteFaq, remove row, show notification, and hide modal on confirm', async () => {
      document.body.innerHTML = `
        <div id="deleteFaqConfirmModal"></div>
        <button id="confirmDeleteFaqButton">Confirm</button>
        <button class="pmf-button-delete-faq"
                data-pmf-id="42"
                data-pmf-language="en"
                data-pmf-token="csrf-abc">Delete</button>
        <tr id="faq_42_en"><td>FAQ row</td></tr>
      `;

      (deleteFaq as Mock).mockResolvedValue({ success: 'FAQ deleted successfully' });

      handleDeleteFaqModal();

      // Click the delete button to store data
      const deleteButton = document.querySelector('.pmf-button-delete-faq') as HTMLElement;
      deleteButton.click();

      await flushPromises();

      // Click confirm to trigger deletion
      const confirmButton = document.getElementById('confirmDeleteFaqButton') as HTMLButtonElement;
      confirmButton.click();

      await flushPromises();

      expect(deleteFaq).toHaveBeenCalledWith('42', 'en', 'csrf-abc');
      expect(pushNotification).toHaveBeenCalledWith('FAQ deleted successfully');
      expect(document.getElementById('faq_42_en')).toBeNull();
      expect(mockModalHide).toHaveBeenCalled();
    });

    it('should show error notification on API failure', async () => {
      document.body.innerHTML = `
        <div id="deleteFaqConfirmModal"></div>
        <button id="confirmDeleteFaqButton">Confirm</button>
        <button class="pmf-button-delete-faq"
                data-pmf-id="42"
                data-pmf-language="en"
                data-pmf-token="csrf-abc">Delete</button>
      `;

      (deleteFaq as Mock).mockRejectedValue(new Error('Network error'));

      handleDeleteFaqModal();

      // Click the delete button to store data
      const deleteButton = document.querySelector('.pmf-button-delete-faq') as HTMLElement;
      deleteButton.click();

      await flushPromises();

      // Click confirm to trigger deletion
      const confirmButton = document.getElementById('confirmDeleteFaqButton') as HTMLButtonElement;
      confirmButton.click();

      await flushPromises();

      expect(pushErrorNotification).toHaveBeenCalledWith('Fehler beim Löschen der FAQ');
    });

    it('should do nothing when currentFaqId, language, and token are empty', async () => {
      document.body.innerHTML = `
        <div id="deleteFaqConfirmModal"></div>
        <button id="confirmDeleteFaqButton">Confirm</button>
      `;

      handleDeleteFaqModal();

      // Click confirm without first clicking a delete button (no data stored)
      const confirmButton = document.getElementById('confirmDeleteFaqButton') as HTMLButtonElement;
      confirmButton.click();

      await flushPromises();

      expect(deleteFaq).not.toHaveBeenCalled();
    });
  });

  describe('handleFaqOverview', () => {
    it('should do nothing when no .accordion-collapse elements exist', async () => {
      document.body.innerHTML = '<div></div>';

      await handleFaqOverview();

      expect(fetchAllFaqsByCategory).not.toHaveBeenCalled();
    });

    it('should initialize checkbox state from localStorage', async () => {
      localStorage.setItem('pmfCheckboxFilterInactive', 'true');
      localStorage.setItem('pmfCheckboxFilterNew', 'false');

      document.body.innerHTML = `
        <input type="checkbox" id="pmf-checkbox-filter-inactive" />
        <input type="checkbox" id="pmf-checkbox-filter-new" />
        <div class="accordion-collapse" data-pmf-categoryId="1" data-pmf-language="en"></div>
      `;

      await handleFaqOverview();

      const inactiveCheckbox = document.getElementById('pmf-checkbox-filter-inactive') as HTMLInputElement;
      const newCheckbox = document.getElementById('pmf-checkbox-filter-new') as HTMLInputElement;

      expect(inactiveCheckbox.checked).toBe(true);
      expect(newCheckbox.checked).toBe(false);
    });
  });
});
