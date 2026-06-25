import { describe, it, expect, beforeEach, afterEach, vi, type Mock } from 'vitest';
import { handleOpenQuestions } from './question';
import * as utils from '../../../../assets/src/utils';

vi.mock('../../../../assets/src/utils');

const mockSerialize = utils.serialize as Mock;
const mockAddElement = utils.addElement as Mock;

describe('handleOpenQuestions', () => {
  let deleteButton: HTMLButtonElement;

  beforeEach(() => {
    vi.clearAllMocks();

    document.body.innerHTML = `
      <div id="returnMessage"></div>
      <form id="phpmyfaq-open-questions">
        <input type="hidden" name="pmf-csrf-token" value="token" />
        <input type="checkbox" name="questions[]" value="1" checked />
      </form>
      <button id="pmf-delete-questions" type="button">Delete</button>
    `;

    deleteButton = document.getElementById('pmf-delete-questions') as HTMLButtonElement;

    mockSerialize.mockReturnValue({ 'questions[]': '1', 'pmf-csrf-token': 'token' });
    mockAddElement.mockImplementation((tag: string) => document.createElement(tag));
  });

  afterEach(() => {
    vi.restoreAllMocks();
  });

  it('should not throw when the delete button is missing', () => {
    document.body.innerHTML = '';
    expect(() => handleOpenQuestions()).not.toThrow();
  });

  it('should send the delete request using the DELETE method', async () => {
    global.fetch = vi.fn(() =>
      Promise.resolve({
        ok: true,
        json: () => Promise.resolve({ success: 'deleted' }),
      } as Response)
    );

    handleOpenQuestions();
    deleteButton.dispatchEvent(new Event('click'));

    await new Promise((resolve) => setTimeout(resolve, 0));

    expect(global.fetch).toHaveBeenCalledWith('./api/question/delete', expect.objectContaining({ method: 'DELETE' }));
  });
});
