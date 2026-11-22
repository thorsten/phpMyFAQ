import { describe, it, expect, vi, beforeEach, Mock } from 'vitest';
import { handleFormEdit, handleFormTranslations } from './forms';
import { pushErrorNotification } from '../../../../assets/src/utils';
import {
  fetchActivateInput,
  fetchAddTranslation,
  fetchDeleteTranslation,
  fetchEditTranslation,
  fetchSetInputAsRequired,
} from '../api';

vi.mock('../api');
vi.mock('../../../../assets/src/utils');

describe('Form Functions', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    document.body.innerHTML = '';
  });

  describe('handleFormEdit', () => {
    it('should handle activate checkboxes', async () => {
      document.body.innerHTML = `
        <div id="forms">
          <input type="checkbox" id="active" data-pmf-csrf-token="csrf" data-pmf-inputid="input1" data-pmf-formid="form1" />
        </div>
      `;

      (fetchActivateInput as Mock).mockResolvedValue({ success: 'Activated' });

      handleFormEdit();

      const checkbox = document.querySelector('#active') as HTMLInputElement;
      checkbox.checked = true;
      checkbox.dispatchEvent(new Event('change'));

      expect(fetchActivateInput).toHaveBeenCalledWith('csrf', 'form1', 'input1', true);
    });

    it('should revert the checkbox and show the error when activating is refused', async () => {
      document.body.innerHTML = `
        <div id="forms">
          <input type="checkbox" id="active" data-pmf-csrf-token="csrf" data-pmf-inputid="input1" data-pmf-formid="form1" />
        </div>
      `;

      (fetchActivateInput as Mock).mockResolvedValue({ error: 'No permission' });

      handleFormEdit();

      const checkbox = document.querySelector('#active') as HTMLInputElement;
      checkbox.checked = true;
      checkbox.dispatchEvent(new Event('change'));

      await vi.waitFor(() => {
        expect(pushErrorNotification).toHaveBeenCalledWith('No permission');
      });
      expect(checkbox.checked).toBe(false);
    });

    it('should revert the checkbox and show the error when marking as required is refused', async () => {
      document.body.innerHTML = `
        <div id="forms">
          <input type="checkbox" id="required" checked data-pmf-csrf-token="csrf" data-pmf-inputid="input1" data-pmf-formid="form1" />
        </div>
      `;

      (fetchSetInputAsRequired as Mock).mockResolvedValue({ error: 'No permission' });

      handleFormEdit();

      const checkbox = document.querySelector('#required') as HTMLInputElement;
      checkbox.checked = false;
      checkbox.dispatchEvent(new Event('change'));

      await vi.waitFor(() => {
        expect(pushErrorNotification).toHaveBeenCalledWith('No permission');
      });
      expect(checkbox.checked).toBe(true);
    });

    it('should handle required checkboxes', async () => {
      document.body.innerHTML = `
        <div id="forms">
          <input type="checkbox" id="required" data-pmf-csrf-token="csrf" data-pmf-inputid="input1" data-pmf-formid="form1" />
        </div>
      `;

      (fetchSetInputAsRequired as Mock).mockResolvedValue({ success: 'Set as required' });

      handleFormEdit();

      const checkbox = document.querySelector('#required') as HTMLInputElement;
      checkbox.checked = true;
      checkbox.dispatchEvent(new Event('change'));

      expect(fetchSetInputAsRequired).toHaveBeenCalledWith('csrf', 'form1', 'input1', true);
    });

    it('should handle tab switching', () => {
      document.body.innerHTML = `
        <div id="forms">
          <div id="ask-question-tab"></div>
          <div id="add-content-tab"></div>
          <div id="ask-question"></div>
          <div id="add-content"></div>
        </div>
      `;

      handleFormEdit();

      const tabAskQuestion = document.getElementById('ask-question-tab') as HTMLElement;
      const tabAddContent = document.getElementById('add-content-tab') as HTMLElement;
      const tabContentAskQuestion = document.getElementById('ask-question') as HTMLElement;
      const tabContentAddContent = document.getElementById('add-content') as HTMLElement;

      tabAskQuestion.click();

      expect(tabAskQuestion.classList.contains('active')).toBe(true);
      expect(tabAddContent.classList.contains('active')).toBe(false);
      expect(tabContentAskQuestion.classList.contains('active')).toBe(true);
      expect(tabContentAddContent.classList.contains('active')).toBe(false);

      tabAddContent.click();

      expect(tabAskQuestion.classList.contains('active')).toBe(false);
      expect(tabAddContent.classList.contains('active')).toBe(true);
      expect(tabContentAskQuestion.classList.contains('active')).toBe(false);
      expect(tabContentAddContent.classList.contains('active')).toBe(true);
    });
  });

  describe('handleFormTranslations', () => {
    it('should handle edit translation', async () => {
      document.body.innerHTML = `
        <div id="formTranslations">
          <button id="editTranslation" data-pmf-lang="en" data-pmf-csrf="csrf" data-pmf-formId="form1" data-pmf-inputId="input1"></button>
          <input id="labelInput_en" disabled />
        </div>
      `;

      (fetchEditTranslation as Mock).mockResolvedValue({ success: 'Translation updated' });

      handleFormTranslations();

      const editButton = document.querySelector('#editTranslation') as HTMLElement;
      const input = document.querySelector('#labelInput_en') as HTMLInputElement;

      editButton.click();
      expect(input.disabled).toBe(false);

      input.value = 'Updated';
      editButton.click();
      expect(input.disabled).toBe(true);

      // Signature: (csrf, formId, inputId, label, lang)
      expect(fetchEditTranslation).toHaveBeenCalledWith('csrf', 'form1', 'input1', 'Updated', 'en');
    });

    it('should handle delete translation', async () => {
      document.body.innerHTML = `
        <div id="formTranslations">
          <button id="deleteTranslation" data-pmf-csrf="csrf" data-pmf-formId="form1" data-pmf-inputId="input1" data-pmf-lang="en" data-pmf-langname="English"></button>
          <div id="item_en"></div>
          <select id="languageSelect"></select>
        </div>
      `;

      (fetchDeleteTranslation as Mock).mockResolvedValue({ success: 'Translation deleted' });

      handleFormTranslations();

      const deleteButton = document.querySelector('#deleteTranslation') as HTMLElement;
      deleteButton.click();

      expect(fetchDeleteTranslation).toHaveBeenCalledWith('csrf', 'form1', 'input1', 'en');
    });

    it('should show the error when deleting a translation is refused', async () => {
      document.body.innerHTML = `
        <table id="formTranslations">
          <tr id="item_en"><td>
            <button id="deleteTranslation" data-pmf-csrf="csrf" data-pmf-formId="form1" data-pmf-inputId="input1" data-pmf-lang="en"></button>
          </td></tr>
        </table>
      `;

      (fetchDeleteTranslation as Mock).mockResolvedValue({ error: 'No permission' });

      handleFormTranslations();

      (document.getElementById('deleteTranslation') as HTMLElement).click();

      await vi.waitFor(() => {
        expect(pushErrorNotification).toHaveBeenCalledWith('No permission');
      });
      expect(document.getElementById('item_en')).not.toBeNull();
    });

    it('should handle add translation', async () => {
      document.body.innerHTML = `
        <div id="formTranslations">
          <button id="addTranslation" data-pmf-csrf="csrf" data-pmf-formId="form1" data-pmf-inputId="input1"></button>
          <select id="languageSelect"><option value="en">English</option></select>
          <input id="translationText" value="New Translation" />
        </div>
      `;

      (fetchAddTranslation as Mock).mockResolvedValue({ success: 'Translation added' });

      handleFormTranslations();

      const addButton = document.querySelector('#addTranslation') as HTMLElement;
      addButton.click();

      expect(fetchAddTranslation).toHaveBeenCalledWith('csrf', 'form1', 'input1', 'en', 'New Translation');
    });
  });
});
