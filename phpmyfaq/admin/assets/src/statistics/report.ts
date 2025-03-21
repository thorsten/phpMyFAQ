import { createReport } from '../api/export';
import { pushErrorNotification, serialize } from '../../../../assets/src/utils';

export const handleCreateReport = (): void => {
  const createReportButton = document.getElementById('pmf-admin-create-report') as HTMLButtonElement | null;

  if (createReportButton) {
    createReportButton.addEventListener('click', async (event: Event): Promise<void> => {
      event.preventDefault();

      const form = document.getElementById('pmf-admin-report-form') as HTMLFormElement;
      const formData = new FormData(form);

      const serializedData = serialize(formData);

      const response = await createReport(serializedData);

      if ('error' in response) {
        pushErrorNotification(response.error);
      } else {
        // Create a download link
        const url = window.URL.createObjectURL(response);
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
