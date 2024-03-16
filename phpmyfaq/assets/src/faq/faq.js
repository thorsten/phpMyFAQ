import { addElement } from '../utils';
import { createFaq } from '../api';

export const handleAddFaq = () => {
  const addFaqSubmit = document.getElementById('pmf-submit-faq');

  if (addFaqSubmit) {
    addFaqSubmit.addEventListener('click', async (event) => {
      event.preventDefault();
      event.stopPropagation();

      const formValidation = document.querySelector('.needs-validation');
      if (!formValidation.checkValidity()) {
        formValidation.classList.add('was-validated');
      } else {
        const form = document.querySelector('#pmf-add-faq-form');
        const loader = document.getElementById('loader');
        const formData = new FormData(form);
        const response = await createFaq(formData);

        if (response.success) {
          loader.classList.add('d-none');
          const message = document.getElementById('pmf-add-faq-response');
          message.insertAdjacentElement(
            'afterend',
            addElement('div', { classList: 'alert alert-success', innerText: response.success })
          );
          form.reset();
        }

        if (response.error) {
          loader.classList.add('d-none');
          const message = document.getElementById('pmf-add-faq-response');
          message.insertAdjacentElement(
            'afterend',
            addElement('div', { classList: 'alert alert-danger', innerText: response.error })
          );
        }
      }
    });
  }
};
