import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import { handleCreateReport } from './report';
import { createReport } from '../api/export';
import { pushErrorNotification, serialize } from '../../../../assets/src/utils';

vi.mock('../api/export', () => ({
  createReport: vi.fn(),
}));

vi.mock('../../../../assets/src/utils', async (importOriginal) => {
  const actual = (await importOriginal()) as Record<string, unknown>;
  return {
    ...actual,
    pushErrorNotification: vi.fn(),
    serialize: vi.fn((formData: FormData) => {
      const result: Record<string, string> = {};
      formData.forEach((value, key) => {
        result[key] = value.toString();
      });
      return result;
    }),
  };
});

// Mock URL methods
URL.createObjectURL = vi.fn(() => 'blob:http://localhost/fake-url');
URL.revokeObjectURL = vi.fn();

describe('handleCreateReport', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    document.body.innerHTML = '';
    // Mock Date for consistent filename
    vi.useFakeTimers();
    vi.setSystemTime(new Date('2024-06-15T12:00:00Z'));
  });

  afterEach(() => {
    vi.useRealTimers();
  });

  it('should do nothing when button is missing', () => {
    document.body.innerHTML = '<div></div>';

    handleCreateReport();

    expect(document.body.innerHTML).toBe('<div></div>');
  });

  it('should show error when no response received', async () => {
    document.body.innerHTML = `
      <form id="pmf-admin-report-form">
        <input type="text" name="testField" value="testValue" />
      </form>
      <input id="pmf-csrf-token" type="hidden" value="csrf-token" />
      <button id="pmf-admin-create-report">Create Report</button>
    `;

    (createReport as ReturnType<typeof vi.fn>).mockResolvedValue(undefined);

    handleCreateReport();

    const button = document.getElementById('pmf-admin-create-report') as HTMLButtonElement;
    button.click();

    await vi.waitFor(() => {
      expect(pushErrorNotification).toHaveBeenCalledWith('No response received');
    });
  });

  it('should show error on error response', async () => {
    document.body.innerHTML = `
      <form id="pmf-admin-report-form">
        <input type="text" name="testField" value="testValue" />
      </form>
      <input id="pmf-csrf-token" type="hidden" value="csrf-token" />
      <button id="pmf-admin-create-report">Create Report</button>
    `;

    (createReport as ReturnType<typeof vi.fn>).mockResolvedValue({
      error: 'Failed to create report',
    });

    handleCreateReport();

    const button = document.getElementById('pmf-admin-create-report') as HTMLButtonElement;
    button.click();

    await vi.waitFor(() => {
      expect(pushErrorNotification).toHaveBeenCalledWith('Failed to create report');
    });
  });

  it('should create and download report on success', async () => {
    document.body.innerHTML = `
      <form id="pmf-admin-report-form">
        <input type="text" name="testField" value="testValue" />
      </form>
      <input id="pmf-csrf-token" type="hidden" value="csrf-token" />
      <button id="pmf-admin-create-report">Create Report</button>
    `;

    const fakeBlob = new Blob(['csv,data'], { type: 'text/csv' });
    (createReport as ReturnType<typeof vi.fn>).mockResolvedValue(fakeBlob);

    handleCreateReport();

    const button = document.getElementById('pmf-admin-create-report') as HTMLButtonElement;
    button.click();

    await vi.waitFor(() => {
      expect(createReport).toHaveBeenCalledWith({ testField: 'testValue' }, 'csrf-token');
      expect(URL.createObjectURL).toHaveBeenCalledWith(fakeBlob);
      expect(URL.revokeObjectURL).toHaveBeenCalledWith('blob:http://localhost/fake-url');
      expect(pushErrorNotification).not.toHaveBeenCalled();
    });
  });

  it('should handle form with multiple fields', async () => {
    document.body.innerHTML = `
      <form id="pmf-admin-report-form">
        <input type="text" name="field1" value="value1" />
        <input type="text" name="field2" value="value2" />
        <select name="field3">
          <option value="option1" selected>Option 1</option>
        </select>
      </form>
      <input id="pmf-csrf-token" type="hidden" value="csrf-token" />
      <button id="pmf-admin-create-report">Create Report</button>
    `;

    const fakeBlob = new Blob(['csv,data'], { type: 'text/csv' });
    (createReport as ReturnType<typeof vi.fn>).mockResolvedValue(fakeBlob);

    handleCreateReport();

    const button = document.getElementById('pmf-admin-create-report') as HTMLButtonElement;
    button.click();

    await vi.waitFor(() => {
      expect(serialize).toHaveBeenCalled();
      expect(createReport).toHaveBeenCalledWith(
        expect.objectContaining({
          field1: 'value1',
          field2: 'value2',
          field3: 'option1',
        }),
        'csrf-token'
      );
    });
  });

  it('should use empty string for csrf when token is missing', async () => {
    document.body.innerHTML = `
      <form id="pmf-admin-report-form">
        <input type="text" name="testField" value="testValue" />
      </form>
      <button id="pmf-admin-create-report">Create Report</button>
    `;

    const fakeBlob = new Blob(['csv,data'], { type: 'text/csv' });
    (createReport as ReturnType<typeof vi.fn>).mockResolvedValue(fakeBlob);

    handleCreateReport();

    const button = document.getElementById('pmf-admin-create-report') as HTMLButtonElement;
    button.click();

    await vi.waitFor(() => {
      expect(createReport).toHaveBeenCalledWith({ testField: 'testValue' }, '');
    });
  });

  it('should show generic error message when error property is undefined', async () => {
    document.body.innerHTML = `
      <form id="pmf-admin-report-form">
        <input type="text" name="testField" value="testValue" />
      </form>
      <input id="pmf-csrf-token" type="hidden" value="csrf-token" />
      <button id="pmf-admin-create-report">Create Report</button>
    `;

    (createReport as ReturnType<typeof vi.fn>).mockResolvedValue({
      error: undefined,
    });

    handleCreateReport();

    const button = document.getElementById('pmf-admin-create-report') as HTMLButtonElement;
    button.click();

    await vi.waitFor(() => {
      expect(pushErrorNotification).toHaveBeenCalledWith('An error occurred');
    });
  });
});
