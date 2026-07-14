import { describe, it, expect, vi, beforeEach, afterEach, Mock } from 'vitest';
import {
  handleSaveFaqData,
  handleSaveShortcut,
  handleDeleteFaqEditorModal,
  handleUpdateQuestion,
  handleResetButton,
  handleFleschReadingEase,
  saveFaq,
} from './faqs.editor';
import { create, update, deleteFaq } from '../api';
import { pushErrorNotification, pushNotification } from '../../../../assets/src/utils';
import { analyzeReadability } from '../utils';
import { applyValidationFeedback, showFirstValidationError, validateFaqEditor } from './faqs.editor.validation';

vi.mock('../api');
vi.mock('../../../../assets/src/utils', async (importOriginal) => {
  const actual = (await importOriginal()) as Record<string, unknown>;
  return {
    ...actual,
    pushNotification: vi.fn(),
    pushErrorNotification: vi.fn(),
  };
});
vi.mock('./editor', () => ({
  getJoditEditor: vi.fn(() => null),
}));
vi.mock('../utils', () => ({
  analyzeReadability: vi.fn(() => ({ score: 65, label: 'Standard', colorClass: 'primary' })),
}));
vi.mock('./faqs.editor.validation', () => ({
  validateFaqEditor: vi.fn(() => []),
  applyValidationFeedback: vi.fn(),
  showFirstValidationError: vi.fn(),
  getAnswerContent: vi.fn(() => ''),
}));

describe('faqs.editor', () => {
  let consoleErrorSpy: ReturnType<typeof vi.spyOn>;

  beforeEach(() => {
    vi.clearAllMocks();
    document.body.innerHTML = '';
    consoleErrorSpy = vi.spyOn(console, 'error').mockImplementation(() => undefined);
  });

  afterEach(() => {
    consoleErrorSpy.mockRestore();
  });

  describe('handleSaveFaqData', () => {
    it('should do nothing when submit button is missing', () => {
      document.body.innerHTML = '<div></div>';

      handleSaveFaqData();

      expect(create).not.toHaveBeenCalled();
      expect(update).not.toHaveBeenCalled();
    });

    it('should call create() when faqId is "0" and update inputs on success', async () => {
      document.body.innerHTML = `
        <form id="faqEditor">
          <input name="faqId" value="0" />
          <input name="question" value="Test question" />
        </form>
        <input id="faqId" value="0" />
        <input id="revisionId" value="0" />
        <button id="faqEditorSubmit">Save</button>
      `;

      const responseData = JSON.stringify({ id: '42', revisionId: '1' });
      (create as Mock).mockResolvedValue({ success: 'FAQ created', data: responseData });

      handleSaveFaqData();

      const button = document.getElementById('faqEditorSubmit') as HTMLButtonElement;
      button.click();

      await new Promise((resolve) => setTimeout(resolve, 10));

      expect(create).toHaveBeenCalledWith(expect.objectContaining({ faqId: '0', question: 'Test question' }));
      expect(update).not.toHaveBeenCalled();
      expect(pushNotification).toHaveBeenCalledWith('FAQ created');

      const faqIdInput = document.getElementById('faqId') as HTMLInputElement;
      const revisionIdInput = document.getElementById('revisionId') as HTMLInputElement;
      expect(faqIdInput.value).toBe('42');
      expect(revisionIdInput.value).toBe('1');
    });

    it('should call update() when faqId is not "0"', async () => {
      document.body.innerHTML = `
        <form id="faqEditor">
          <input name="faqId" value="10" />
          <input name="question" value="Updated question" />
        </form>
        <input id="faqId" value="10" />
        <input id="revisionId" value="1" />
        <button id="faqEditorSubmit">Save</button>
      `;

      const responseData = JSON.stringify({ id: '10', revisionId: '2' });
      (update as Mock).mockResolvedValue({ success: 'FAQ updated', data: responseData });

      handleSaveFaqData();

      const button = document.getElementById('faqEditorSubmit') as HTMLButtonElement;
      button.click();

      await new Promise((resolve) => setTimeout(resolve, 10));

      expect(update).toHaveBeenCalledWith(expect.objectContaining({ faqId: '10', question: 'Updated question' }));
      expect(create).not.toHaveBeenCalled();
      expect(pushNotification).toHaveBeenCalledWith('FAQ updated');
    });

    it('should show error notification on error response', async () => {
      document.body.innerHTML = `
        <form id="faqEditor">
          <input name="faqId" value="0" />
        </form>
        <input id="faqId" value="0" />
        <input id="revisionId" value="0" />
        <button id="faqEditorSubmit">Save</button>
      `;

      (create as Mock).mockResolvedValue({ error: 'Validation failed' });

      handleSaveFaqData();

      const button = document.getElementById('faqEditorSubmit') as HTMLButtonElement;
      button.click();

      await new Promise((resolve) => setTimeout(resolve, 10));

      expect(pushErrorNotification).toHaveBeenCalledWith('Validation failed');
      expect(pushNotification).not.toHaveBeenCalled();
    });

    it('should disable the button and show a spinner while saving', async () => {
      document.body.innerHTML = `
        <form id="faqEditor">
          <input name="faqId" value="0" />
        </form>
        <input id="faqId" value="0" />
        <input id="revisionId" value="0" />
        <button id="faqEditorSubmit" data-pmf-label-saving="Saving…">Save</button>
      `;

      let resolveCreate: (value: unknown) => void = () => undefined;
      (create as Mock).mockReturnValue(new Promise((resolve) => (resolveCreate = resolve)));

      const button = document.getElementById('faqEditorSubmit') as HTMLButtonElement;
      const pendingSave = saveFaq();

      expect(button.disabled).toBe(true);
      expect(button.innerHTML).toContain('spinner-border');
      expect(button.innerHTML).toContain('Saving…');

      resolveCreate({ success: 'FAQ created', data: JSON.stringify({ id: '1', revisionId: '1' }) });
      await pendingSave;

      expect(button.disabled).toBe(false);
      expect(button.innerHTML).toBe('Save');
    });

    it('should restore the button and notify the user when saving fails', async () => {
      document.body.innerHTML = `
        <form id="faqEditor">
          <input name="faqId" value="0" />
        </form>
        <input id="faqId" value="0" />
        <input id="revisionId" value="0" />
        <button id="faqEditorSubmit" data-pmf-msg-save-error="Could not save the FAQ.">Save</button>
      `;

      (create as Mock).mockRejectedValue(new Error('Network error'));

      const button = document.getElementById('faqEditorSubmit') as HTMLButtonElement;

      await saveFaq();

      expect(pushErrorNotification).toHaveBeenCalledWith('Network error');
      expect(button.disabled).toBe(false);
      expect(button.innerHTML).toBe('Save');
    });

    it('should fall back to the translated save-error message for non-Error rejections', async () => {
      document.body.innerHTML = `
        <form id="faqEditor">
          <input name="faqId" value="0" />
        </form>
        <input id="faqId" value="0" />
        <input id="revisionId" value="0" />
        <button id="faqEditorSubmit" data-pmf-msg-save-error="Could not save the FAQ.">Save</button>
      `;

      (create as Mock).mockRejectedValue('boom');

      await saveFaq();

      expect(pushErrorNotification).toHaveBeenCalledWith('Could not save the FAQ.');
    });

    it('should update the saved indicator on success', async () => {
      document.body.innerHTML = `
        <form id="faqEditor">
          <input name="faqId" value="0" />
        </form>
        <input id="faqId" value="0" />
        <input id="revisionId" value="0" />
        <button id="faqEditorSubmit" data-pmf-label-saved="Saved">Save</button>
        <small id="pmf-faq-saved-indicator"></small>
      `;

      (create as Mock).mockResolvedValue({
        success: 'FAQ created',
        data: JSON.stringify({ id: '1', revisionId: '1' }),
      });

      await saveFaq();

      const indicator = document.getElementById('pmf-faq-saved-indicator') as HTMLElement;
      expect(indicator.textContent).toContain('Saved');
    });

    it('should block saving and surface errors when validation fails', async () => {
      document.body.innerHTML = `
        <form id="faqEditor">
          <input name="faqId" value="0" />
        </form>
        <button id="faqEditorSubmit">Save</button>
      `;

      const errors = [{ fieldId: 'question', tabHref: '#tab-question-answer' }];
      (validateFaqEditor as Mock).mockReturnValueOnce(errors);

      await saveFaq();

      expect(applyValidationFeedback).toHaveBeenCalledWith(errors);
      expect(showFirstValidationError).toHaveBeenCalledWith(errors);
      expect(create).not.toHaveBeenCalled();
      expect(update).not.toHaveBeenCalled();
    });

    it('should not save when the button is disabled', async () => {
      document.body.innerHTML = `
        <form id="faqEditor">
          <input name="faqId" value="0" />
        </form>
        <button id="faqEditorSubmit" disabled>Save</button>
      `;

      await saveFaq();

      expect(create).not.toHaveBeenCalled();
      expect(update).not.toHaveBeenCalled();
    });
  });

  describe('handleSaveShortcut', () => {
    it('should save on Ctrl+S when the editor is present', async () => {
      document.body.innerHTML = `
        <form id="faqEditor">
          <input name="faqId" value="0" />
        </form>
        <input id="faqId" value="0" />
        <input id="revisionId" value="0" />
        <button id="faqEditorSubmit">Save</button>
      `;

      (create as Mock).mockResolvedValue({
        success: 'FAQ created',
        data: JSON.stringify({ id: '1', revisionId: '1' }),
      });

      handleSaveShortcut();

      const event = new KeyboardEvent('keydown', { key: 's', ctrlKey: true, cancelable: true });
      document.dispatchEvent(event);

      await new Promise((resolve) => setTimeout(resolve, 10));

      expect(event.defaultPrevented).toBe(true);
      expect(create).toHaveBeenCalled();
    });

    it('should ignore a plain "s" key press', async () => {
      document.body.innerHTML = `
        <form id="faqEditor">
          <input name="faqId" value="0" />
        </form>
        <button id="faqEditorSubmit">Save</button>
      `;

      handleSaveShortcut();

      document.dispatchEvent(new KeyboardEvent('keydown', { key: 's', cancelable: true }));

      await new Promise((resolve) => setTimeout(resolve, 10));

      expect(create).not.toHaveBeenCalled();
    });
  });

  describe('handleDeleteFaqEditorModal', () => {
    it('should do nothing when buttons are missing', () => {
      document.body.innerHTML = '<div></div>';

      handleDeleteFaqEditorModal();

      expect(deleteFaq).not.toHaveBeenCalled();
    });

    it('should call deleteFaq with correct params and show notification', async () => {
      document.body.innerHTML = `
        <button id="faqEditorDelete"
          data-faq-id="42"
          data-faq-language="en"
          data-pmf-csrf-token="test-csrf">Delete</button>
        <button id="pmf-confirm-delete-faq">Confirm</button>
      `;

      (deleteFaq as Mock).mockResolvedValue({ success: 'FAQ deleted successfully' });

      handleDeleteFaqEditorModal();

      const confirmButton = document.getElementById('pmf-confirm-delete-faq') as HTMLButtonElement;
      confirmButton.click();

      await new Promise((resolve) => setTimeout(resolve, 10));

      expect(deleteFaq).toHaveBeenCalledWith('42', 'en', 'test-csrf');
      expect(pushNotification).toHaveBeenCalledWith('FAQ deleted successfully');
    });

    it('should show error notification when required params are missing', async () => {
      document.body.innerHTML = `
        <button id="faqEditorDelete">Delete</button>
        <button id="pmf-confirm-delete-faq">Confirm</button>
      `;

      handleDeleteFaqEditorModal();

      const confirmButton = document.getElementById('pmf-confirm-delete-faq') as HTMLButtonElement;
      confirmButton.click();

      await new Promise((resolve) => setTimeout(resolve, 10));

      expect(deleteFaq).not.toHaveBeenCalled();
      expect(pushErrorNotification).toHaveBeenCalledWith('Could not delete the FAQ.');
    });
  });

  describe('handleUpdateQuestion', () => {
    it('should do nothing when input is missing', () => {
      document.body.innerHTML = '<div></div>';

      handleUpdateQuestion();

      const output = document.getElementById('pmf-admin-question-output');
      expect(output).toBeNull();
    });

    it('should update output on input event', () => {
      document.body.innerHTML = `
        <input id="question" value="" />
        <span id="pmf-admin-question-output"></span>
      `;

      handleUpdateQuestion();

      const input = document.getElementById('question') as HTMLInputElement;
      input.value = 'What is phpMyFAQ?';
      input.dispatchEvent(new Event('input'));

      const output = document.getElementById('pmf-admin-question-output') as HTMLElement;
      expect(output.innerText).toBe(': What is phpMyFAQ?');
    });
  });

  describe('handleResetButton', () => {
    it('should do nothing when reset button is missing', () => {
      document.body.innerHTML = '<div></div>';

      handleResetButton();

      expect(document.body.innerHTML).toBe('<div></div>');
    });

    it('should reset form and restore defaults', () => {
      document.body.innerHTML = `
        <form id="faqEditor">
          <input id="question" value="Original question" />
          <textarea id="editor">Original editor content</textarea>
          <textarea id="answer-markdown">Original markdown</textarea>
          <span id="pmf-admin-question-output">: Modified question</span>
          <select id="selectedRevisionId">
            <option value="1">Rev 1</option>
            <option value="2">Rev 2</option>
            <option value="3">Rev 3</option>
          </select>
          <button type="reset">Reset</button>
        </form>
      `;

      // Modify current values away from defaults
      const questionInput = document.getElementById('question') as HTMLInputElement;
      questionInput.value = 'Modified question';

      const markdownTextarea = document.getElementById('answer-markdown') as HTMLTextAreaElement;
      markdownTextarea.value = 'Modified markdown';

      const revisionSelect = document.getElementById('selectedRevisionId') as HTMLSelectElement;
      revisionSelect.value = '1';

      handleResetButton();

      const resetButton = document.querySelector('button[type="reset"]') as HTMLButtonElement;
      resetButton.click();

      // Markdown textarea should be restored to its defaultValue
      expect(markdownTextarea.value).toBe('Original markdown');

      // Question output should show the original question default value
      const questionOutput = document.getElementById('pmf-admin-question-output') as HTMLElement;
      expect(questionOutput.innerText).toBe(': Original question');

      // Revision select should be set to the last option
      expect(revisionSelect.value).toBe('3');
    });
  });

  describe('handleFleschReadingEase', () => {
    it('should do nothing when required elements are missing', () => {
      document.body.innerHTML = '<div></div>';

      handleFleschReadingEase();

      expect(analyzeReadability).not.toHaveBeenCalled();
    });
  });
});
