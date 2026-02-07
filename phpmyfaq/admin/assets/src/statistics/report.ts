import { createReport } from '../api/export';
import { pushErrorNotification, serialize } from '../../../../assets/src/utils';
import { Response } from '../interfaces';

export const handleCreateReport = (): void => {
  const createReportButton = document.getElementById('pmf-admin-create-report') as HTMLButtonElement | null;

  if (createReportButton) {
    createReportButton.addEventListener('click', async (event: Event): Promise<void> => {
      event.preventDefault();

      const form = document.getElementById('pmf-admin-report-form') as HTMLFormElement;
      const formData = new FormData(form);
      const csrfToken = (document.getElementById('pmf-csrf-token') as HTMLInputElement)?.value ?? '';

      const serializedData = serialize(formData);

      const response = await createReport(serializedData, csrfToken);

      if (!response) {
        pushErrorNotification('No response received');
        return;
      }

      if ('error' in response) {
        pushErrorNotification((response as Response).error ?? 'An error occurred');
      } else {
        // Create a download link
        const url = window.URL.createObjectURL(response as Blob);
        const anchor = document.createElement('a');
        anchor.href = url;
        anchor.download = 'phpmyfaq-report-' + new Date().toISOString().substring(0, 10) + '.csv';
        document.body.appendChild(anchor);
        anchor.click();
        document.body.removeChild(anchor);
        window.URL.revokeObjectURL(url);
      }
    });
  }
};
