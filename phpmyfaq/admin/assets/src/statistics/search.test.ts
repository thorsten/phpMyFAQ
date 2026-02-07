import { describe, it, expect, vi, beforeEach } from 'vitest';
import { handleTruncateSearchTerms } from './search';
import { truncateSearchTerms } from '../api';
import { pushErrorNotification, pushNotification } from '../../../../assets/src/utils';

vi.mock('../api', () => ({
  truncateSearchTerms: vi.fn(),
}));

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

describe('handleTruncateSearchTerms', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    document.body.innerHTML = '';
    // Mock window.confirm
    global.confirm = vi.fn();
  });

  it('should do nothing when button is missing', () => {
    document.body.innerHTML = '<div></div>';

    handleTruncateSearchTerms();

    expect(document.body.innerHTML).toBe('<div></div>');
  });

  it('should show error when csrf is missing', async () => {
    document.body.innerHTML = `
      <button id="pmf-button-truncate-search-terms">Truncate</button>
    `;

    handleTruncateSearchTerms();

    const button = document.getElementById('pmf-button-truncate-search-terms') as HTMLButtonElement;
    button.click();

    await flushPromises();

    expect(pushErrorNotification).toHaveBeenCalledWith('Missing CSRF token');
    expect(truncateSearchTerms).not.toHaveBeenCalled();
  });

  it('should not truncate when user cancels confirmation', async () => {
    document.body.innerHTML = `
      <button id="pmf-button-truncate-search-terms" data-pmf-csrf-token="test-csrf">Truncate</button>
      <table id="pmf-table-search-terms"></table>
    `;

    (global.confirm as ReturnType<typeof vi.fn>).mockReturnValue(false);

    handleTruncateSearchTerms();

    const button = document.getElementById('pmf-button-truncate-search-terms') as HTMLButtonElement;
    button.click();

    await flushPromises();

    expect(global.confirm).toHaveBeenCalledWith('Are you sure?');
    expect(truncateSearchTerms).not.toHaveBeenCalled();
  });

  it('should truncate search terms and remove table on success', async () => {
    document.body.innerHTML = `
      <button id="pmf-button-truncate-search-terms" data-pmf-csrf-token="test-csrf">Truncate</button>
      <table id="pmf-table-search-terms"></table>
    `;

    (global.confirm as ReturnType<typeof vi.fn>).mockReturnValue(true);
    (truncateSearchTerms as ReturnType<typeof vi.fn>).mockResolvedValue({
      success: 'Search terms truncated successfully',
    });

    handleTruncateSearchTerms();

    const button = document.getElementById('pmf-button-truncate-search-terms') as HTMLButtonElement;
    button.click();

    await flushPromises();

    expect(global.confirm).toHaveBeenCalledWith('Are you sure?');
    expect(truncateSearchTerms).toHaveBeenCalledWith('test-csrf');
    expect(pushNotification).toHaveBeenCalledWith('Search terms truncated successfully');
    expect(document.getElementById('pmf-table-search-terms')).toBeNull();
  });

  it('should show error on error response', async () => {
    document.body.innerHTML = `
      <button id="pmf-button-truncate-search-terms" data-pmf-csrf-token="test-csrf">Truncate</button>
      <table id="pmf-table-search-terms"></table>
    `;

    (global.confirm as ReturnType<typeof vi.fn>).mockReturnValue(true);
    (truncateSearchTerms as ReturnType<typeof vi.fn>).mockResolvedValue({
      error: 'Failed to truncate search terms',
    });

    handleTruncateSearchTerms();

    const button = document.getElementById('pmf-button-truncate-search-terms') as HTMLButtonElement;
    button.click();

    await flushPromises();

    expect(truncateSearchTerms).toHaveBeenCalledWith('test-csrf');
    expect(pushErrorNotification).toHaveBeenCalledWith('Failed to truncate search terms');
    expect(pushNotification).not.toHaveBeenCalled();
    // Table should still exist
    expect(document.getElementById('pmf-table-search-terms')).not.toBeNull();
  });
});
