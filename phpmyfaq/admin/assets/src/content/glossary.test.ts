import { describe, it, expect, vi, beforeEach, Mock } from 'vitest';
import { handleDeleteGlossary, handleAddGlossary, onOpenUpdateGlossaryModal, handleUpdateGlossary } from './glossary';
import { createGlossary, deleteGlossary, getGlossary, updateGlossary } from '../api';
import { pushNotification } from '../../../../assets/src/utils';

vi.mock('../api');
vi.mock('../../../../assets/src/utils', async (importOriginal) => {
  const actual = (await importOriginal()) as Record<string, unknown>;
  return {
    ...actual,
    pushNotification: vi.fn(),
  };
});
vi.mock('bootstrap', () => {
  const hideFn = vi.fn();
  return {
    Modal: Object.assign(
      vi.fn().mockImplementation(() => ({
        show: vi.fn(),
        hide: hideFn,
      })),
      {
        getInstance: vi.fn().mockReturnValue({ hide: hideFn }),
      }
    ),
  };
});

const flushPromises = async (): Promise<void> => {
  await new Promise((resolve) => setTimeout(resolve, 10));
};

describe('Glossary Functions', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    document.body.innerHTML = '';
  });

  describe('handleDeleteGlossary', () => {
    it('should not throw when no delete buttons exist', () => {
      document.body.innerHTML = '<div></div>';

      expect(() => handleDeleteGlossary()).not.toThrow();
    });

    it('should call deleteGlossary with correct params and remove the row', async () => {
      document.body.innerHTML = `
        <table>
          <tbody>
            <tr id="glossary-row-1">
              <td>Term</td>
              <td>Definition</td>
              <td>
                <button
                  class="pmf-admin-delete-glossary"
                  data-pmf-glossary-id="7"
                  data-pmf-csrf-token="csrf-abc"
                  data-pmf-glossary-language="en"
                >Delete</button>
              </td>
            </tr>
          </tbody>
        </table>
      `;

      (deleteGlossary as Mock).mockResolvedValue({ success: 'Glossary item deleted' });

      handleDeleteGlossary();

      const button = document.querySelector('.pmf-admin-delete-glossary') as HTMLButtonElement;
      button.click();

      await flushPromises();

      expect(deleteGlossary).toHaveBeenCalledWith('7', 'en', 'csrf-abc');
      expect(pushNotification).toHaveBeenCalledWith('Glossary item deleted');

      const row = document.getElementById('glossary-row-1');
      expect(row).toBeNull();
    });
  });

  describe('handleAddGlossary', () => {
    it('should not throw when button is missing', () => {
      document.body.innerHTML = '<div></div>';

      expect(() => handleAddGlossary()).not.toThrow();
    });

    it('should create glossary, close modal, append row, and notify', async () => {
      document.body.innerHTML = `
        <div id="addGlossaryModal"></div>
        <button id="pmf-admin-glossary-add">Add</button>
        <input id="language" value="en" />
        <input id="item" value="API" />
        <input id="definition" value="Application Programming Interface" />
        <input id="pmf-csrf-token" value="csrf-xyz" />
        <table id="pmf-admin-glossary-table">
          <tbody></tbody>
        </table>
      `;

      (createGlossary as Mock).mockResolvedValue({ success: 'Glossary item created' });

      handleAddGlossary();

      const button = document.getElementById('pmf-admin-glossary-add') as HTMLButtonElement;
      button.click();

      await flushPromises();

      expect(createGlossary).toHaveBeenCalledWith('en', 'API', 'Application Programming Interface', 'csrf-xyz');

      // Modal should have been closed
      const { Modal } = await import('bootstrap');
      expect(Modal.getInstance).toHaveBeenCalled();

      // Form fields should be reset
      const itemInput = document.getElementById('item') as HTMLInputElement;
      const definitionInput = document.getElementById('definition') as HTMLInputElement;
      expect(itemInput.value).toBe('');
      expect(definitionInput.value).toBe('');

      // New row should be appended to the table
      const tableBody = document.querySelector('#pmf-admin-glossary-table tbody') as HTMLElement;
      const rows = tableBody.querySelectorAll('tr');
      expect(rows.length).toBe(1);

      expect(pushNotification).toHaveBeenCalledWith('Glossary item created');
    });
  });

  describe('onOpenUpdateGlossaryModal', () => {
    it('should not throw when modal element is missing', () => {
      document.body.innerHTML = '<div></div>';

      expect(() => onOpenUpdateGlossaryModal()).not.toThrow();
    });

    it('should fill form fields from API response when modal opens', async () => {
      document.body.innerHTML = `
        <div id="updateGlossaryModal"></div>
        <input id="update-id" value="" />
        <input id="update-language" value="" />
        <input id="update-item" value="" />
        <input id="update-definition" value="" />
        <button
          id="trigger-btn"
          data-pmf-glossary-id="5"
          data-pmf-glossary-language="de"
        >Edit</button>
      `;

      (getGlossary as Mock).mockResolvedValue({ item: 'Glossar', definition: 'Eine Sammlung von Begriffen' });

      onOpenUpdateGlossaryModal();

      const modal = document.getElementById('updateGlossaryModal') as HTMLElement;
      const triggerButton = document.getElementById('trigger-btn') as HTMLElement;

      // Dispatch the show.bs.modal event with relatedTarget
      const event = new Event('show.bs.modal');
      Object.defineProperty(event, 'relatedTarget', { value: triggerButton });
      modal.dispatchEvent(event);

      await flushPromises();

      expect(getGlossary).toHaveBeenCalledWith('5', 'de');

      const updateId = document.getElementById('update-id') as HTMLInputElement;
      const updateLang = document.getElementById('update-language') as HTMLInputElement;
      const updateItem = document.getElementById('update-item') as HTMLInputElement;
      const updateDef = document.getElementById('update-definition') as HTMLInputElement;

      expect(updateId.value).toBe('5');
      expect(updateLang.value).toBe('de');
      expect(updateItem.value).toBe('Glossar');
      expect(updateDef.value).toBe('Eine Sammlung von Begriffen');
    });
  });

  describe('handleUpdateGlossary', () => {
    it('should not throw when button is missing', () => {
      document.body.innerHTML = '<div></div>';

      expect(() => handleUpdateGlossary()).not.toThrow();
    });

    it('should update glossary, close modal, update DOM cells, and notify', async () => {
      document.body.innerHTML = `
        <div id="updateGlossaryModal"></div>
        <button id="pmf-admin-glossary-update">Update</button>
        <input id="update-id" value="3" />
        <input id="update-language" value="en" />
        <input id="update-item" value="Updated Term" />
        <input id="update-definition" value="Updated Definition" />
        <input id="update-csrf-token" value="csrf-update" />
        <table>
          <tbody>
            <tr id="pmf-glossary-id-3">
              <td><a href="#">Old Term</a></td>
              <td>Old Definition</td>
              <td>Actions</td>
            </tr>
          </tbody>
        </table>
      `;

      (updateGlossary as Mock).mockResolvedValue({ success: 'Glossary item updated' });

      handleUpdateGlossary();

      const button = document.getElementById('pmf-admin-glossary-update') as HTMLButtonElement;
      button.click();

      await flushPromises();

      expect(updateGlossary).toHaveBeenCalledWith('3', 'en', 'Updated Term', 'Updated Definition', 'csrf-update');

      // Modal should have been closed
      const { Modal } = await import('bootstrap');
      expect(Modal.getInstance).toHaveBeenCalled();

      // DOM cells should be updated
      const itemLink = document.querySelector('#pmf-glossary-id-3 td:nth-child(1) a') as HTMLElement;
      const definitionCell = document.querySelector('#pmf-glossary-id-3 td:nth-child(2)') as HTMLElement;
      expect(itemLink.innerText).toBe('Updated Term');
      expect(definitionCell.innerText).toBe('Updated Definition');

      expect(pushNotification).toHaveBeenCalledWith('Glossary item updated');
    });
  });
});
