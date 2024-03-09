import { send } from './api';
import { addElement } from './utils';

export const handleContactForm = () => {
  const contactSubmit = document.getElementById('pmf-submit-contact');

  if (contactSubmit) {
    contactSubmit.addEventListener('click', async (event) => {
      event.preventDefault();
      event.stopPropagation();

      const formValidation = document.querySelector('.needs-validation');
      if (!formValidation.checkValidity()) {
        formValidation.classList.add('was-validated');
      } else {
        const form = document.querySelector('#pmf-contact-form');
        const loader = document.getElementById('loader');
        const formData = new FormData(form);
        const response = await send(formData);

        if (response.success) {
          loader.classList.add('d-none');
          const message = document.getElementById('pmf-contact-response');
          message.insertAdjacentElement(
            'afterend',
            addElement('div', { classList: 'alert alert-success', innerText: response.success })
          );
        }

        if (response.error) {
          loader.classList.add('d-none');
          const message = document.getElementById('pmf-contact-response');
          message.insertAdjacentElement(
            'afterend',
            addElement('div', { classList: 'alert alert-danger', innerText: response.error })
          );
        }
      }
    });
  }
};
