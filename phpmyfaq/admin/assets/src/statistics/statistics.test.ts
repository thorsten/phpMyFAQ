import { describe, it, expect, vi, beforeEach } from 'vitest';
import { handleStatistics } from './statistics';

const mockFetch = vi.fn();
global.fetch = mockFetch;

const flushPromises = async (): Promise<void> => {
  await new Promise((resolve) => setTimeout(resolve, 50));
};

describe('handleStatistics', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    document.body.innerHTML = '';
    vi.spyOn(console, 'error').mockImplementation(() => {});
    global.confirm = vi.fn();
  });

  it('should do nothing when no delete buttons exist', () => {
    document.body.innerHTML = '<div></div>';

    handleStatistics();

    expect(document.body.innerHTML).toBe('<div></div>');
  });

  it('should not delete when user cancels confirmation', async () => {
    document.body.innerHTML = `
      <button class="pmf-delete-search-term" data-delete-search-term-id="123" data-csrf-token="csrf-token">Delete</button>
    `;

    (global.confirm as ReturnType<typeof vi.fn>).mockReturnValue(false);

    handleStatistics();

    const button = document.querySelector('.pmf-delete-search-term') as HTMLButtonElement;
    button.click();

    await flushPromises();

    expect(global.confirm).toHaveBeenCalledWith('Are you sure?');
    expect(mockFetch).not.toHaveBeenCalled();
  });

  it('should delete search term and remove row on success', async () => {
    document.body.innerHTML = `
      <table>
        <tr id="row-search-id-123">
          <td>
            <button class="pmf-delete-search-term" data-delete-search-term-id="123" data-csrf-token="csrf-token">Delete</button>
          </td>
        </tr>
      </table>
    `;

    (global.confirm as ReturnType<typeof vi.fn>).mockReturnValue(true);
    mockFetch.mockResolvedValue({
      ok: true,
      json: () => Promise.resolve({ deleted: '123' }),
    });

    handleStatistics();

    const button = document.querySelector('.pmf-delete-search-term') as HTMLButtonElement;
    button.click();

    await flushPromises();

    expect(global.confirm).toHaveBeenCalledWith('Are you sure?');
    expect(mockFetch).toHaveBeenCalledWith('./api/search/term', {
      method: 'DELETE',
      headers: {
        Accept: 'application/json, text/plain, */*',
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        csrf: 'csrf-token',
        searchTermId: '123',
      }),
    });

    const row = document.getElementById('row-search-id-123') as HTMLElement;
    expect(row).not.toBeNull();

    // Simulate click and transitionend events
    row.click();
    expect(row.style.opacity).toBe('0');

    row.dispatchEvent(new Event('transitionend'));
    expect(document.getElementById('row-search-id-123')).toBeNull();
  });

  it('should log error on non-ok response', async () => {
    document.body.innerHTML = `
      <button class="pmf-delete-search-term" data-delete-search-term-id="123" data-csrf-token="csrf-token">Delete</button>
    `;

    (global.confirm as ReturnType<typeof vi.fn>).mockReturnValue(true);
    mockFetch.mockResolvedValue({
      ok: false,
      json: () => Promise.resolve({ error: 'Failed to delete' }),
    });

    handleStatistics();

    const button = document.querySelector('.pmf-delete-search-term') as HTMLButtonElement;
    button.click();

    await flushPromises();

    expect(console.error).toHaveBeenCalledWith('Network response was not ok: Failed to delete');
  });

  it('should log error on fetch failure', async () => {
    document.body.innerHTML = `
      <button class="pmf-delete-search-term" data-delete-search-term-id="123" data-csrf-token="csrf-token">Delete</button>
    `;

    (global.confirm as ReturnType<typeof vi.fn>).mockReturnValue(true);
    mockFetch.mockRejectedValue(new Error('Network error'));

    handleStatistics();

    const button = document.querySelector('.pmf-delete-search-term') as HTMLButtonElement;
    button.click();

    await flushPromises();

    expect(console.error).toHaveBeenCalledWith('Network error');
  });

  it('should handle multiple delete buttons', async () => {
    document.body.innerHTML = `
      <button class="pmf-delete-search-term" data-delete-search-term-id="123" data-csrf-token="csrf-token">Delete 1</button>
      <button class="pmf-delete-search-term" data-delete-search-term-id="456" data-csrf-token="csrf-token">Delete 2</button>
    `;

    (global.confirm as ReturnType<typeof vi.fn>).mockReturnValue(true);
    mockFetch.mockResolvedValue({
      ok: true,
      json: () => Promise.resolve({ deleted: '123' }),
    });

    handleStatistics();

    const buttons = document.querySelectorAll('.pmf-delete-search-term');
    expect(buttons.length).toBe(2);

    (buttons[0] as HTMLButtonElement).click();

    await flushPromises();

    expect(mockFetch).toHaveBeenCalledWith('./api/search/term', {
      method: 'DELETE',
      headers: {
        Accept: 'application/json, text/plain, */*',
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        csrf: 'csrf-token',
        searchTermId: '123',
      }),
    });
  });
});
