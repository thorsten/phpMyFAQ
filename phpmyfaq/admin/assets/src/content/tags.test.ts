import { describe, it, expect, vi, beforeEach, Mock } from 'vitest';
import { handleTags } from './tags';
import { deleteTag } from '../api';
import { pushNotification } from '../../../../assets/src/utils';
import { fetchJson } from '../api/fetch-wrapper';

vi.mock('../api');
vi.mock('../api/fetch-wrapper');
vi.mock('autocompleter', () => ({ default: vi.fn() }));
vi.mock('../../../../assets/src/utils', async (importOriginal) => {
  const actual = (await importOriginal()) as Record<string, unknown>;
  return {
    ...actual,
    pushNotification: vi.fn(),
    pushErrorNotification: vi.fn(),
  };
});

describe('handleTags', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    document.body.innerHTML = '';
  });

  it('should not throw when no buttons or form exist', () => {
    document.body.innerHTML = '<div></div>';

    expect(() => handleTags()).not.toThrow();
  });

  describe('edit button', () => {
    const setupEditDom = () => {
      document.body.innerHTML = `
        <button class="btn-edit" data-btn-id="1">Edit</button>
      `;
      const span = document.createElement('span');
      span.id = 'tag-id-1';
      span.innerText = 'Test Tag';
      document.body.prepend(span);
    };

    it('should replace span with input on first click', () => {
      setupEditDom();

      handleTags();

      const editButton = document.querySelector('.btn-edit') as HTMLButtonElement;
      editButton.click();

      const input = document.querySelector('input[id="tag-id-1"]') as HTMLInputElement;
      expect(input).not.toBeNull();
      expect(input.tagName).toBe('INPUT');
      expect(input.value).toBe('Test Tag');
      expect(input.name).toBe('tag');
      expect(input.type).toBe('text');

      const span = document.querySelector('span[id="tag-id-1"]');
      expect(span).toBeNull();
    });

    it('should replace input back with span on second click', () => {
      setupEditDom();

      handleTags();

      const editButton = document.querySelector('.btn-edit') as HTMLButtonElement;

      // First click: span -> input
      editButton.click();
      const input = document.querySelector('input[id="tag-id-1"]') as HTMLInputElement;
      expect(input).not.toBeNull();

      // Second click: input -> span
      editButton.click();
      const span = document.querySelector('span[id="tag-id-1"]') as HTMLSpanElement;
      expect(span).not.toBeNull();
      expect(span.tagName).toBe('SPAN');
      expect(span.innerHTML).toBe('Test Tag');

      const inputAfter = document.querySelector('input[id="tag-id-1"]');
      expect(inputAfter).toBeNull();
    });
  });

  describe('delete button', () => {
    const setupDeleteDom = () => {
      document.body.innerHTML = `
        <table>
          <tbody>
            <tr id="pmf-row-tag-id-1">
              <td>
                <span id="tag-id-1">Test Tag</span>
              </td>
              <td>
                <button class="btn-delete" data-pmf-id="1">Delete</button>
              </td>
            </tr>
          </tbody>
        </table>
      `;
    };

    it('should call deleteTag and remove row on success', async () => {
      setupDeleteDom();

      (deleteTag as Mock).mockResolvedValue({ success: 'Tag deleted successfully' });

      handleTags();

      const deleteButton = document.querySelector('.btn-delete') as HTMLButtonElement;
      deleteButton.click();

      await new Promise((resolve) => setTimeout(resolve, 10));

      expect(deleteTag).toHaveBeenCalledWith('1');
      expect(pushNotification).toHaveBeenCalledWith('Tag deleted successfully');

      const row = document.getElementById('pmf-row-tag-id-1');
      expect(row).toBeNull();
    });

    it('should log error on failure', async () => {
      setupDeleteDom();

      const consoleErrorSpy = vi.spyOn(console, 'error').mockImplementation(() => {});

      (deleteTag as Mock).mockResolvedValue({ error: 'Tag deletion failed' });

      handleTags();

      const deleteButton = document.querySelector('.btn-delete') as HTMLButtonElement;
      deleteButton.click();

      await new Promise((resolve) => setTimeout(resolve, 10));

      expect(deleteTag).toHaveBeenCalledWith('1');
      expect(consoleErrorSpy).toHaveBeenCalledWith('Network response was not ok:', 'Tag deletion failed');

      // Row should still be present
      const row = document.getElementById('pmf-row-tag-id-1');
      expect(row).not.toBeNull();

      consoleErrorSpy.mockRestore();
    });
  });

  describe('form submit', () => {
    const setupFormDom = () => {
      document.body.innerHTML = `
        <table class="table">
          <tbody>
            <tr id="pmf-row-tag-id-1">
              <td>
                <input type="text" id="tag-id-1" name="tag" value="Updated Tag" class="form-control" />
              </td>
              <td>
                <button class="btn-edit" data-btn-id="1">Edit</button>
              </td>
            </tr>
          </tbody>
        </table>
        <form id="tag-form">
          <input name="pmf-csrf-token" value="test-csrf" />
        </form>
      `;
    };

    it('should call fetchJson and replace input with span and badge on success', async () => {
      setupFormDom();

      (fetchJson as Mock).mockResolvedValue({ success: 'Tag saved' });

      handleTags();

      // Focus the input to simulate user editing
      const input = document.querySelector('input[id="tag-id-1"]') as HTMLInputElement;
      input.focus();

      const form = document.getElementById('tag-form') as HTMLFormElement;
      form.dispatchEvent(new Event('submit'));

      await new Promise((resolve) => setTimeout(resolve, 10));

      expect(fetchJson).toHaveBeenCalledWith('./api/content/tag', {
        method: 'PUT',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          csrf: 'test-csrf',
          id: '1',
          tag: 'Updated Tag',
        }),
      });

      // Input should be replaced with a span
      const span = document.querySelector('span[id="tag-id-1"]') as HTMLSpanElement;
      expect(span).not.toBeNull();
      expect(span.innerHTML).toContain('Updated Tag');

      // The badge should be present inside the span
      const badge = span.querySelector('.badge');
      expect(badge).not.toBeNull();

      const inputAfter = document.querySelector('input[id="tag-id-1"]');
      expect(inputAfter).toBeNull();
    });

    it('should show error alert on failure', async () => {
      setupFormDom();

      (fetchJson as Mock).mockRejectedValue(new Error('Save failed'));

      handleTags();

      // Focus the input to simulate user editing
      const input = document.querySelector('input[id="tag-id-1"]') as HTMLInputElement;
      input.focus();

      const form = document.getElementById('tag-form') as HTMLFormElement;
      form.dispatchEvent(new Event('submit'));

      await new Promise((resolve) => setTimeout(resolve, 10));

      const alert = document.querySelector('.alert.alert-danger') as HTMLElement;
      expect(alert).not.toBeNull();
      expect(alert.innerText).toBe('Save failed');
    });
  });
});
