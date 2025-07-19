import { create, update } from '../api';
import { serialize } from '../../../../assets/src/utils';
import { pushErrorNotification, pushNotification } from '../utils';

export const handleSaveFaqData = () => {
  const submitButton = document.getElementById('faqEditorSubmit');

  if (submitButton) {
    submitButton.addEventListener('click', async (event) => {
      event.preventDefault();
      const form = document.getElementById('faqEditor');
      const formData = new FormData(form);

      const serializedData = serialize(formData);

      let response;
      if (serializedData.faqId === '0') {
        response = await create(serializedData);
      } else {
        response = await update(serializedData);
      }

      if (response.success) {
        const data = JSON.parse(response.data);
        const faqId = document.getElementById('faqId');
        const revisionId = document.getElementById('revisionId');

        faqId.value = data.id;
        revisionId.value = data.revisionId;

        pushNotification(response.success);
      } else {
        pushErrorNotification(response.error);
      }
    });
  }
};

export const handleUpdateQuestion = () => {
  const input = document.getElementById('question');
  if (input) {
    input.addEventListener('input', () => {
      document.getElementById('pmf-admin-question-output').innerText = `: ${input.value}`;
    });
  }
};

export const handleResetButton = () => {
  const resetButton = document.querySelector('button[type="reset"]');
  if (resetButton) {
    resetButton.addEventListener('click', (event) => {
      event.preventDefault();
      const form = document.getElementById('faqEditor');
      form.reset();
      const revisionSelect = document.getElementById('selectedRevisionId');

      console.log(revisionSelect);

      if (revisionSelect && revisionSelect.options.length > 0) {
        const lastOption = revisionSelect.options[revisionSelect.options.length - 1];
        revisionSelect.value = lastOption.value;
        // Optional: Trigger change event, falls weitere Logik daran hängt
        revisionSelect.dispatchEvent(new Event('change'));
      }
    });
  }
};
