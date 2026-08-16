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

    it('should initialize status filter state from localStorage', async () => {
      localStorage.setItem('pmfStatusFilter', 'review');
      localStorage.setItem('pmfCheckboxFilterNew', 'false');

      document.body.innerHTML = `
        <select id="pmf-status-filter">
          <option value="">All</option>
          <option value="draft">Draft</option>
          <option value="review">In review</option>
          <option value="published">Published</option>
        </select>
        <input type="checkbox" id="pmf-checkbox-filter-new" />
        <div class="accordion-collapse" data-pmf-categoryId="1" data-pmf-language="en"></div>
      `;

      await handleFaqOverview();

      const statusFilter = document.getElementById('pmf-status-filter') as HTMLSelectElement;
      const newCheckbox = document.getElementById('pmf-checkbox-filter-new') as HTMLInputElement;

      expect(statusFilter.value).toBe('review');
      expect(newCheckbox.checked).toBe(false);
    });

    it('should persist status filter state to localStorage on change', async () => {
      document.body.innerHTML = `
        <select id="pmf-status-filter">
          <option value="">All</option>
          <option value="draft">Draft</option>
        </select>
        <input type="checkbox" id="pmf-checkbox-filter-new" />
        <div class="accordion-collapse" data-pmf-categoryId="1" data-pmf-language="en"></div>
      `;

      await handleFaqOverview();

      const statusFilter = document.getElementById('pmf-status-filter') as HTMLSelectElement;
      statusFilter.value = 'draft';
      statusFilter.dispatchEvent(new Event('change'));

      expect(localStorage.getItem('pmfStatusFilter')).toBe('draft');

      const newCheckbox = document.getElementById('pmf-checkbox-filter-new') as HTMLInputElement;
      newCheckbox.checked = true;
      newCheckbox.dispatchEvent(new Event('change'));

      expect(localStorage.getItem('pmfCheckboxFilterNew')).toBe('true');
    });

    it('should clear category table on hidden.bs.collapse', async () => {
      document.body.innerHTML = `
        <div class="accordion-collapse" data-pmf-categoryId="5" data-pmf-language="en"></div>
        <table><tbody id="tbody-category-id-5"><tr><td>Existing</td></tr></tbody></table>
      `;

      await handleFaqOverview();

      const category = document.querySelector('.accordion-collapse') as HTMLElement;
      category.dispatchEvent(new Event('hidden.bs.collapse'));

      const tableBody = document.getElementById('tbody-category-id-5') as HTMLElement;
      expect(tableBody.innerHTML).toBe('');
    });

    it('should fetch FAQs and populate table on shown.bs.collapse', async () => {
      document.body.innerHTML = `
        <div class="accordion-collapse" data-pmf-categoryId="3" data-pmf-language="en"></div>
        <table><tbody id="tbody-category-id-3" data-pmf-csrf="csrf-token"></tbody></table>
      `;

      (fetchAllFaqsByCategory as Mock).mockResolvedValue({
        faqs: [
          {
            id: 1,
            language: 'en',
            solution_id: 1001,
            question: 'How to install?',
            created: '2026-03-01',
            category_id: 3,
            sticky: 'no',
            status: 'published',
            isAllowedToPublish: true,
          },
        ],
        isAllowedToTranslate: false,
      });

      await handleFaqOverview();

      const category = document.querySelector('.accordion-collapse') as HTMLElement;
      category.dispatchEvent(new Event('shown.bs.collapse'));

      await flushPromises();

      expect(fetchAllFaqsByCategory).toHaveBeenCalledWith('3', 'en', '', false);

      const tableBody = document.getElementById('tbody-category-id-3') as HTMLElement;
      expect(tableBody.querySelectorAll('tr').length).toBe(1);
    });

    it('should use localStorage filter states when fetching FAQs', async () => {
      localStorage.setItem('pmfStatusFilter', 'published');
      localStorage.setItem('pmfCheckboxFilterNew', 'true');

      document.body.innerHTML = `
        <select id="pmf-status-filter">
          <option value="">All</option>
          <option value="published">Published</option>
        </select>
        <input type="checkbox" id="pmf-checkbox-filter-new" />
        <div class="accordion-collapse" data-pmf-categoryId="3" data-pmf-language="en"></div>
        <table><tbody id="tbody-category-id-3" data-pmf-csrf="csrf-token"></tbody></table>
      `;

      (fetchAllFaqsByCategory as Mock).mockResolvedValue({
        faqs: [],
        isAllowedToTranslate: false,
      });

      await handleFaqOverview();

      const category = document.querySelector('.accordion-collapse') as HTMLElement;
      category.dispatchEvent(new Event('shown.bs.collapse'));

      await flushPromises();

      expect(fetchAllFaqsByCategory).toHaveBeenCalledWith('3', 'en', 'published', true);
    });

    it('should render the status read-only without the publish permission once published', async () => {
      document.body.innerHTML = `
        <div class="accordion-collapse" data-pmf-categoryId="3" data-pmf-language="en"></div>
        <table><tbody id="tbody-category-id-3" data-pmf-csrf="csrf-token"></tbody></table>
      `;

      (fetchAllFaqsByCategory as Mock).mockResolvedValue({
        faqs: [
          {
            id: 7,
            language: 'en',
            solution_id: 1007,
            question: 'Test FAQ',
            created: '2026-03-01',
            category_id: 3,
            sticky: 'no',
            status: 'published',
            isAllowedToPublish: false,
          },
        ],
        isAllowedToTranslate: false,
      });

      await handleFaqOverview();

      const category = document.querySelector('.accordion-collapse') as HTMLElement;
      category.dispatchEvent(new Event('shown.bs.collapse'));

      await flushPromises();

      // No select to change, but the state stays visible, and the sticky toggle is unaffected.
      expect(document.querySelector('.pmf-admin-status-faq')).toBeNull();
      expect(document.querySelector('.badge')).not.toBeNull();
      expect(document.querySelector('.pmf-admin-sticky-faq')).not.toBeNull();
    });

    it('should render a draft/review select for a non-published FAQ without the publish permission', async () => {
      document.body.innerHTML = `
        <div class="accordion-collapse" data-pmf-categoryId="3" data-pmf-language="en"></div>
        <table><tbody id="tbody-category-id-3" data-pmf-csrf="csrf-token"></tbody></table>
      `;

      (fetchAllFaqsByCategory as Mock).mockResolvedValue({
        faqs: [
          {
            id: 7,
            language: 'en',
            solution_id: 1007,
            question: 'Test FAQ',
            created: '2026-03-01',
            category_id: 3,
            sticky: 'no',
            status: 'draft',
            isAllowedToPublish: false,
          },
        ],
        isAllowedToTranslate: false,
      });

      await handleFaqOverview();

      const category = document.querySelector('.accordion-collapse') as HTMLElement;
      category.dispatchEvent(new Event('shown.bs.collapse'));

      await flushPromises();

      const statusSelect = document.querySelector('.pmf-admin-status-faq') as HTMLSelectElement;
      expect(statusSelect).not.toBeNull();
      const optionValues = Array.from(statusSelect.options).map((option) => option.value);
      expect(optionValues).toEqual(['draft', 'review']);
    });

    it('should call saveStatus when sticky toggle changes', async () => {
      document.body.innerHTML = `
        <div class="accordion-collapse" data-pmf-categoryId="3" data-pmf-language="en"></div>
        <table><tbody id="tbody-category-id-3" data-pmf-csrf="csrf-token"></tbody></table>
      `;

      (fetchAllFaqsByCategory as Mock).mockResolvedValue({
        faqs: [
          {
            id: 7,
            language: 'en',
            solution_id: 1007,
            question: 'Test FAQ',
            created: '2026-03-01',
            category_id: 3,
            sticky: 'no',
            status: 'published',
            isAllowedToPublish: true,
          },
        ],
        isAllowedToTranslate: false,
      });

      const fetchMock = vi.fn().mockResolvedValue({
        ok: true,
        json: vi.fn().mockResolvedValue({ success: 'Status updated' }),
      });
      vi.stubGlobal('fetch', fetchMock);

      await handleFaqOverview();

      const category = document.querySelector('.accordion-collapse') as HTMLElement;
      category.dispatchEvent(new Event('shown.bs.collapse'));

      await flushPromises();

      // Find the sticky checkbox and toggle it
      const stickyCheckbox = document.querySelector('.pmf-admin-sticky-faq') as HTMLInputElement;
      expect(stickyCheckbox).not.toBeNull();
      stickyCheckbox.checked = true;
      stickyCheckbox.dispatchEvent(new Event('change'));

      await flushPromises();

      expect(fetchMock).toHaveBeenCalledWith(
        './api/faq/sticky',
        expect.objectContaining({
          method: 'POST',
        })
      );
      expect(pushNotification).toHaveBeenCalledWith('Status updated');

      vi.unstubAllGlobals();
    });

    it('should call saveFaqStatus when the status select changes', async () => {
      document.body.innerHTML = `
        <div class="accordion-collapse" data-pmf-categoryId="3" data-pmf-language="en"></div>
        <table><tbody id="tbody-category-id-3" data-pmf-csrf="csrf-token"></tbody></table>
      `;

      (fetchAllFaqsByCategory as Mock).mockResolvedValue({
        faqs: [
          {
            id: 7,
            language: 'en',
            solution_id: 1007,
            question: 'Test FAQ',
            created: '2026-03-01',
            category_id: 3,
            sticky: 'no',
            status: 'draft',
            isAllowedToPublish: true,
          },
        ],
        isAllowedToTranslate: false,
      });

      const fetchMock = vi.fn().mockResolvedValue({
        ok: true,
        json: vi.fn().mockResolvedValue({ success: 'Status updated' }),
      });
      vi.stubGlobal('fetch', fetchMock);

      await handleFaqOverview();

      const category = document.querySelector('.accordion-collapse') as HTMLElement;
      category.dispatchEvent(new Event('shown.bs.collapse'));

      await flushPromises();

      const statusSelect = document.querySelector('.pmf-admin-status-faq') as HTMLSelectElement;
      expect(statusSelect).not.toBeNull();
      statusSelect.value = 'published';
      statusSelect.dispatchEvent(new Event('change'));

      await flushPromises();

      expect(fetchMock).toHaveBeenCalledWith(
        './api/faq/status',
        expect.objectContaining({
          method: 'POST',
        })
      );

      vi.unstubAllGlobals();
    });

    it('should show error notification when saveStatus API returns error', async () => {
      document.body.innerHTML = `
        <div class="accordion-collapse" data-pmf-categoryId="3" data-pmf-language="en"></div>
        <table><tbody id="tbody-category-id-3" data-pmf-csrf="csrf-token"></tbody></table>
      `;

      (fetchAllFaqsByCategory as Mock).mockResolvedValue({
        faqs: [
          {
            id: 7,
            language: 'en',
            solution_id: 1007,
            question: 'Test FAQ',
            created: '2026-03-01',
            category_id: 3,
            sticky: 'no',
            status: 'published',
            isAllowedToPublish: true,
          },
        ],
        isAllowedToTranslate: false,
      });

      const fetchMock = vi.fn().mockResolvedValue({
        ok: true,
        json: vi.fn().mockResolvedValue({ error: 'Permission denied' }),
      });
      vi.stubGlobal('fetch', fetchMock);

      await handleFaqOverview();

      const category = document.querySelector('.accordion-collapse') as HTMLElement;
      category.dispatchEvent(new Event('shown.bs.collapse'));

      await flushPromises();

      const stickyCheckbox = document.querySelector('.pmf-admin-sticky-faq') as HTMLInputElement;
      stickyCheckbox.dispatchEvent(new Event('change'));

      await flushPromises();

      expect(pushErrorNotification).toHaveBeenCalledWith('Permission denied');

      vi.unstubAllGlobals();
    });

    it('should show error notification when saveStatus network fails', async () => {
      document.body.innerHTML = `
        <div class="accordion-collapse" data-pmf-categoryId="3" data-pmf-language="en"></div>
        <table><tbody id="tbody-category-id-3" data-pmf-csrf="csrf-token"></tbody></table>
      `;

      (fetchAllFaqsByCategory as Mock).mockResolvedValue({
        faqs: [
          {
            id: 7,
            language: 'en',
            solution_id: 1007,
            question: 'Test FAQ',
            created: '2026-03-01',
            category_id: 3,
            sticky: 'no',
            status: 'published',
            isAllowedToPublish: true,
          },
        ],
        isAllowedToTranslate: false,
      });

      const fetchMock = vi.fn().mockRejectedValue(new Error('Connection refused'));
      vi.stubGlobal('fetch', fetchMock);

      await handleFaqOverview();

      const category = document.querySelector('.accordion-collapse') as HTMLElement;
      category.dispatchEvent(new Event('shown.bs.collapse'));

      await flushPromises();

      const stickyCheckbox = document.querySelector('.pmf-admin-sticky-faq') as HTMLInputElement;
      stickyCheckbox.dispatchEvent(new Event('change'));

      await flushPromises();

      expect(pushErrorNotification).toHaveBeenCalledWith('An error occurred while saving the status.');

      vi.unstubAllGlobals();
    });

    it('should show error when saveStatus response is not ok', async () => {
      document.body.innerHTML = `
        <div class="accordion-collapse" data-pmf-categoryId="3" data-pmf-language="en"></div>
        <table><tbody id="tbody-category-id-3" data-pmf-csrf="csrf-token"></tbody></table>
      `;

      (fetchAllFaqsByCategory as Mock).mockResolvedValue({
        faqs: [
          {
            id: 7,
            language: 'en',
            solution_id: 1007,
            question: 'Test FAQ',
            created: '2026-03-01',
            category_id: 3,
            sticky: 'no',
            status: 'published',
            isAllowedToPublish: true,
          },
        ],
        isAllowedToTranslate: false,
      });

      const fetchMock = vi.fn().mockResolvedValue({
        ok: false,
        text: vi.fn().mockResolvedValue('Internal Server Error'),
      });
      vi.stubGlobal('fetch', fetchMock);

      await handleFaqOverview();

      const category = document.querySelector('.accordion-collapse') as HTMLElement;
      category.dispatchEvent(new Event('shown.bs.collapse'));

      await flushPromises();

      const statusSelect = document.querySelector('.pmf-admin-status-faq') as HTMLSelectElement;
      statusSelect.dispatchEvent(new Event('change'));

      await flushPromises();

      expect(pushErrorNotification).toHaveBeenCalledWith('Network response was not ok: Internal Server Error');

      vi.unstubAllGlobals();
    });
  });

  describe('handleDeleteFaqModal - nested click target', () => {
    it('should handle click on icon inside delete button', async () => {
      document.body.innerHTML = `
        <div id="deleteFaqConfirmModal"></div>
        <button id="confirmDeleteFaqButton">Confirm</button>
        <button class="pmf-button-delete-faq"
                data-pmf-id="99"
                data-pmf-language="de"
                data-pmf-token="csrf-xyz">
          <i class="bi bi-trash"></i>
        </button>
      `;

      handleDeleteFaqModal();

      // Click the icon inside the button
      const icon = document.querySelector('.bi-trash') as HTMLElement;
      icon.click();

      await flushPromises();

      expect(mockModalShow).toHaveBeenCalled();

      // Confirm and verify correct data was stored
      (deleteFaq as Mock).mockResolvedValue({ success: 'Deleted' });

      const confirmButton = document.getElementById('confirmDeleteFaqButton') as HTMLButtonElement;
      confirmButton.click();

      await flushPromises();

      expect(deleteFaq).toHaveBeenCalledWith('99', 'de', 'csrf-xyz');
    });
  });
});
