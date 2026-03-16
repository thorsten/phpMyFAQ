import { describe, it, expect, vi, beforeEach } from 'vitest';

const mockHandleUpdateNextStepButton = vi.fn();
const mockHandleUpdateInformation = vi.fn().mockResolvedValue(undefined);
const mockHandleConfigBackup = vi.fn().mockResolvedValue(undefined);
const mockHandleDatabaseUpdate = vi.fn().mockResolvedValue(undefined);

vi.mock('./configuration', () => ({
  handleUpdateNextStepButton: () => mockHandleUpdateNextStepButton(),
  handleUpdateInformation: () => mockHandleUpdateInformation(),
  handleConfigBackup: () => mockHandleConfigBackup(),
  handleDatabaseUpdate: () => mockHandleDatabaseUpdate(),
}));

describe('update.ts', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  it('should call all update handlers on DOMContentLoaded', async () => {
    const callOrder: string[] = [];

    mockHandleUpdateNextStepButton.mockImplementation(() => {
      callOrder.push('nextStep');
    });
    mockHandleUpdateInformation.mockImplementation(async () => {
      callOrder.push('updateInfo');
    });
    mockHandleConfigBackup.mockImplementation(async () => {
      callOrder.push('backup');
    });
    mockHandleDatabaseUpdate.mockImplementation(async () => {
      callOrder.push('dbUpdate');
    });

    await import('./update');
    document.dispatchEvent(new Event('DOMContentLoaded'));

    await vi.waitFor(() => {
      expect(mockHandleUpdateNextStepButton).toHaveBeenCalled();
      expect(mockHandleUpdateInformation).toHaveBeenCalled();
      expect(mockHandleConfigBackup).toHaveBeenCalled();
      expect(mockHandleDatabaseUpdate).toHaveBeenCalled();
    });

    // Verify sequential execution order
    expect(callOrder).toEqual(['nextStep', 'updateInfo', 'backup', 'dbUpdate']);
  });
});
