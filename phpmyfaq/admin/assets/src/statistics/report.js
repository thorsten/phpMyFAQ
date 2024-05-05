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
        window.open(encodeURI(response));

        let hiddenElement = document.createElement('a');
        hiddenElement.setAttribute('href', 'data:text/csv;charset=utf-8,' + encodeURI(response));
        hiddenElement.setAttribute('download', 'phpmyfaq-report-' + new Date().toISOString().substring(0, 10) + '.csv');
        hiddenElement.click();
      }
    });
  }
};
