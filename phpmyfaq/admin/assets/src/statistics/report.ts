import { createReport } from '../api/export';
import { pushErrorNotification, serialize } from '../../../../assets/src/utils';

const wireSelectAllReportFields = (): void => {
  const selectAll = document.getElementById('pmf-admin-report-select-all') as HTMLInputElement | null;
  const fields = Array.from(document.querySelectorAll<HTMLInputElement>('.pmf-report-field'));

  if (!selectAll || fields.length === 0) {
    return;
  }

  const syncSelectAllState = (): void => {
    const checkedCount = fields.filter((field) => field.checked).length;
    selectAll.checked = checkedCount === fields.length;
    selectAll.indeterminate = checkedCount > 0 && checkedCount < fields.length;
  };

  selectAll.addEventListener('change', (): void => {
    fields.forEach((field) => {
      field.checked = selectAll.checked;
    });
  });

  fields.forEach((field) => field.addEventListener('change', syncSelectAllState));

  syncSelectAllState();
};

export const handleCreateReport = (): void => {
  wireSelectAllReportFields();

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
        pushErrorNotification(response.error ?? 'An error occurred');
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
