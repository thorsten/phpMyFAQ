import { describe, it, expect, vi, beforeEach } from 'vitest';
import { handleClearRatings } from './ratings';
import { clearRatings } from '../api';
import { pushErrorNotification, pushNotification } from '../../../../assets/src/utils';

vi.mock('../api', () => ({
  clearRatings: vi.fn(),
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

describe('handleClearRatings', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    document.body.innerHTML = '';
  });

  it('should do nothing when button is missing', () => {
    document.body.innerHTML = '<div></div>';

    handleClearRatings();

    expect(document.body.innerHTML).toBe('<div></div>');
  });

  it('should show error when csrf is missing', async () => {
    document.body.innerHTML = `
      <button id="pmf-admin-clear-ratings">Clear Ratings</button>
    `;

    handleClearRatings();

    const button = document.getElementById('pmf-admin-clear-ratings') as HTMLButtonElement;
    button.click();

    await flushPromises();

    expect(pushErrorNotification).toHaveBeenCalledWith('Missing CSRF token');
    expect(clearRatings).not.toHaveBeenCalled();
  });

  it('should call clearRatings and show notification on success', async () => {
    document.body.innerHTML = `
      <button id="pmf-admin-clear-ratings" data-pmf-csrf="test-csrf-token">Clear Ratings</button>
    `;

    (clearRatings as ReturnType<typeof vi.fn>).mockResolvedValue({
      success: 'Ratings cleared successfully',
    });

    handleClearRatings();

    const button = document.getElementById('pmf-admin-clear-ratings') as HTMLButtonElement;
    button.click();

    await flushPromises();

    expect(clearRatings).toHaveBeenCalledWith('test-csrf-token');
    expect(pushNotification).toHaveBeenCalledWith('Ratings cleared successfully');
    expect(pushErrorNotification).not.toHaveBeenCalled();
  });

  it('should show error on error response', async () => {
    document.body.innerHTML = `
      <button id="pmf-admin-clear-ratings" data-pmf-csrf="test-csrf-token">Clear Ratings</button>
    `;

    (clearRatings as ReturnType<typeof vi.fn>).mockResolvedValue({
      error: 'Failed to clear ratings',
    });

    handleClearRatings();

    const button = document.getElementById('pmf-admin-clear-ratings') as HTMLButtonElement;
    button.click();

    await flushPromises();

    expect(clearRatings).toHaveBeenCalledWith('test-csrf-token');
    expect(pushErrorNotification).toHaveBeenCalledWith('Failed to clear ratings');
    expect(pushNotification).not.toHaveBeenCalled();
  });
});
