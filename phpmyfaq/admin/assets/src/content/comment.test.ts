import { describe, it, expect, beforeEach, afterEach, vi, type Mock } from 'vitest';
import { handleDeleteComments } from './comment';
import * as utils from '../../../../assets/src/utils';

vi.mock('../../../../assets/src/utils');

const mockSerialize = utils.serialize as Mock;
const mockAddElement = utils.addElement as Mock;

describe('handleDeleteComments', () => {
  let deleteFaqButton: HTMLButtonElement;

  beforeEach(() => {
    vi.clearAllMocks();

    document.body.innerHTML = `
      <div id="returnMessage"></div>
      <form id="pmf-comments-selected-faq">
        <input type="hidden" name="pmf-csrf-token" value="token" />
        <input type="checkbox" name="comments[]" value="1" checked />
      </form>
      <button id="pmf-button-delete-faq-comments" type="button">Delete</button>
    `;

    deleteFaqButton = document.getElementById('pmf-button-delete-faq-comments') as HTMLButtonElement;

    mockSerialize.mockReturnValue({ 'comments[]': '1', 'pmf-csrf-token': 'token' });
    mockAddElement.mockImplementation((tag: string) => document.createElement(tag));
  });

  afterEach(() => {
    vi.restoreAllMocks();
  });

  it('should not throw when the delete buttons are missing', () => {
    document.body.innerHTML = '';
    expect(() => handleDeleteComments()).not.toThrow();
  });

  it('should send the delete request to the base-relative API route using the DELETE method', async () => {
    global.fetch = vi.fn(() =>
      Promise.resolve({
        ok: true,
        json: () => Promise.resolve({ success: true }),
      } as Response)
    );

    handleDeleteComments();
    deleteFaqButton.dispatchEvent(new Event('click'));

    await new Promise((resolve) => setTimeout(resolve, 0));

    expect(global.fetch).toHaveBeenCalledWith('./api/content/comments', expect.objectContaining({ method: 'DELETE' }));
  });
});
