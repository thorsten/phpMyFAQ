import { describe, it, expect, vi, beforeEach, Mock } from 'vitest';
import { handleOpenQuestions, handleToggleVisibility } from './question';
import { toggleQuestionVisibility } from '../api';
import { pushErrorNotification } from '../../../../assets/src/utils';

vi.mock('../api');
vi.mock('../../../../assets/src/utils', async (importOriginal) => {
  const actual = (await importOriginal()) as Record<string, unknown>;
  return {
    ...actual,
    pushErrorNotification: vi.fn(),
    pushNotification: vi.fn(),
  };
});

describe('Question Functions', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    document.body.innerHTML = '';
    vi.restoreAllMocks();
  });

  describe('handleOpenQuestions', () => {
    it('should do nothing when delete button is missing', () => {
      document.body.innerHTML = '<div></div>';

      handleOpenQuestions();

      // No error should be thrown and no fetch should be called
      expect(document.body.innerHTML).toBe('<div></div>');
    });

    it('should successfully delete checked questions and remove their rows', async () => {
      document.body.innerHTML = `
        <div id="returnMessage"></div>
        <form id="phpmyfaq-open-questions">
          <table>
            <tbody>
              <tr>
                <td><span><input type="checkbox" name="questions[]" value="1" checked /></span></td>
                <td>Question 1</td>
              </tr>
              <tr>
                <td><span><input type="checkbox" name="questions[]" value="2" checked /></span></td>
                <td>Question 2</td>
              </tr>
              <tr>
                <td><span><input type="checkbox" name="questions[]" value="3" /></span></td>
                <td>Question 3</td>
              </tr>
            </tbody>
          </table>
        </form>
        <button id="pmf-delete-questions">Delete</button>
      `;

      global.fetch = vi.fn(() =>
        Promise.resolve({
          ok: true,
          json: () => Promise.resolve({ success: 'Questions deleted successfully' }),
        } as Response)
      );

      handleOpenQuestions();

      const deleteButton = document.getElementById('pmf-delete-questions') as HTMLButtonElement;
      deleteButton.click();

      await new Promise((resolve) => setTimeout(resolve, 10));

      expect(global.fetch).toHaveBeenCalledWith('./api/question/delete', {
        method: 'POST',
        headers: {
          Accept: 'application/json, text/plain, */*',
          'Content-Type': 'application/json',
        },
        body: expect.any(String),
      });

      const responseMessage = document.getElementById('returnMessage') as HTMLElement;
      const successAlert = responseMessage.querySelector('div') as HTMLElement;
      expect(successAlert).not.toBeNull();
      expect(successAlert.innerText).toBe('Questions deleted successfully');

      // Checked rows should be removed (tr containing checked inputs)
      const rows = document.querySelectorAll('tr');
      expect(rows.length).toBe(1);
      expect(rows[0].innerHTML).toContain('Question 3');
    });

    it('should show error alert on error response', async () => {
      document.body.innerHTML = `
        <div id="returnMessage"></div>
        <form id="phpmyfaq-open-questions">
          <input type="checkbox" name="questions[]" value="1" checked />
        </form>
        <button id="pmf-delete-questions">Delete</button>
      `;

      const errorMessage = 'Deletion failed';
      global.fetch = vi.fn(() =>
        Promise.resolve({
          ok: false,
          json: () => Promise.resolve(errorMessage),
        } as unknown as Response)
      );

      handleOpenQuestions();

      const deleteButton = document.getElementById('pmf-delete-questions') as HTMLButtonElement;
      deleteButton.click();

      await new Promise((resolve) => setTimeout(resolve, 10));

      const responseMessageEl = document.getElementById('returnMessage') as HTMLElement;
      const errorAlert = responseMessageEl.querySelector('div') as HTMLElement;
      expect(errorAlert).not.toBeNull();
      expect(errorAlert.innerText).toBe(errorMessage);
    });
  });

  describe('handleToggleVisibility', () => {
    it('should do nothing when no toggle elements exist', () => {
      document.body.innerHTML = '<div></div>';

      handleToggleVisibility();

      expect(toggleQuestionVisibility).not.toHaveBeenCalled();
    });

    it('should call toggleQuestionVisibility with correct params on click', async () => {
      document.body.innerHTML = `
        <span class="pmf-toggle-visibility"
              data-pmf-question-id="42"
              data-pmf-visibility="true"
              data-pmf-csrf="csrf-token-abc">Toggle</span>
      `;

      (toggleQuestionVisibility as Mock).mockResolvedValue({ success: 'Visibility updated' });

      handleToggleVisibility();

      const element = document.querySelector('.pmf-toggle-visibility') as HTMLElement;
      element.click();

      await new Promise((resolve) => setTimeout(resolve, 10));

      expect(toggleQuestionVisibility).toHaveBeenCalledWith('42', true, 'csrf-token-abc');
    });

    it('should convert visibility string "false" to boolean false', async () => {
      document.body.innerHTML = `
        <span class="pmf-toggle-visibility"
              data-pmf-question-id="7"
              data-pmf-visibility="false"
              data-pmf-csrf="csrf-token-xyz">Toggle</span>
      `;

      (toggleQuestionVisibility as Mock).mockResolvedValue({ success: 'Visibility updated' });

      handleToggleVisibility();

      const element = document.querySelector('.pmf-toggle-visibility') as HTMLElement;
      element.click();

      await new Promise((resolve) => setTimeout(resolve, 10));

      expect(toggleQuestionVisibility).toHaveBeenCalledWith('7', false, 'csrf-token-xyz');
    });

    it('should show success text on element when API returns success', async () => {
      document.body.innerHTML = `
        <span class="pmf-toggle-visibility"
              data-pmf-question-id="42"
              data-pmf-visibility="true"
              data-pmf-csrf="csrf-token-abc">Toggle</span>
      `;

      (toggleQuestionVisibility as Mock).mockResolvedValue({ success: 'Visibility toggled successfully' });

      handleToggleVisibility();

      const element = document.querySelector('.pmf-toggle-visibility') as HTMLElement;
      element.click();

      await new Promise((resolve) => setTimeout(resolve, 10));

      expect(element.innerText).toBe('Visibility toggled successfully');
    });

    it('should show error notification when API returns error', async () => {
      document.body.innerHTML = `
        <span class="pmf-toggle-visibility"
              data-pmf-question-id="42"
              data-pmf-visibility="true"
              data-pmf-csrf="csrf-token-abc">Toggle</span>
      `;

      (toggleQuestionVisibility as Mock).mockResolvedValue({ error: 'Toggle failed' });

      handleToggleVisibility();

      const element = document.querySelector('.pmf-toggle-visibility') as HTMLElement;
      element.click();

      await new Promise((resolve) => setTimeout(resolve, 10));

      expect(pushErrorNotification).toHaveBeenCalledWith('Toggle failed');
    });

    it('should show default error notification when API returns undefined', async () => {
      document.body.innerHTML = `
        <span class="pmf-toggle-visibility"
              data-pmf-question-id="42"
              data-pmf-visibility="true"
              data-pmf-csrf="csrf-token-abc">Toggle</span>
      `;

      (toggleQuestionVisibility as Mock).mockResolvedValue(undefined);

      handleToggleVisibility();

      const element = document.querySelector('.pmf-toggle-visibility') as HTMLElement;
      element.click();

      await new Promise((resolve) => setTimeout(resolve, 10));

      expect(pushErrorNotification).toHaveBeenCalledWith('An error occurred');
    });
  });
});
