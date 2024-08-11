import { createReport } from '../api/export';
import { serialize } from '../../../../assets/src/utils';
import { pushErrorNotification, pushNotification } from '../utils';

export const handleCreateReport = () => {
  const createReportButton = document.getElementById('pmf-admin-create-report');

  if (createReportButton) {
    createReportButton.addEventListener('click', async (event) => {
      event.preventDefault();

      const form = document.getElementById('pmf-admin-report-form');
      const formData = new FormData(form);

      const serializedData = serialize(formData);

      const response = await createReport(serializedData);

      if (response.error) {
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
