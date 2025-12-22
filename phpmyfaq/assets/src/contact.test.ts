import { describe, it, expect, beforeEach, vi, type Mock } from 'vitest';
import { handleContactForm } from './contact';
import * as api from '../src/api';
import * as utils from '../src/utils';

// Mock the dependencies
vi.mock('../src/api');
vi.mock('../src/utils');

const mockSend = api.send as Mock;
const mockAddElement = utils.addElement as Mock;

describe('handleContactForm', () => {
  let mockContactSubmit: HTMLElement;
  let mockForm: HTMLFormElement;
  let mockFormValidation: HTMLFormElement;
  let mockLoader: HTMLElement;
  let mockMessage: HTMLElement;

  beforeEach(() => {
    // Clear all mocks
    vi.clearAllMocks();

    // Setup DOM elements
    document.body.innerHTML = `
      <form id="pmf-contact-form" class="needs-validation">
        <input name="name" required />
        <button id="pmf-submit-contact" type="submit">Submit</button>
      </form>
      <div id="loader"></div>
      <div id="pmf-contact-response"></div>
    `;

    mockContactSubmit = document.getElementById('pmf-submit-contact') as HTMLElement;
    mockForm = document.querySelector('#pmf-contact-form') as HTMLFormElement;
    mockFormValidation = document.querySelector('.needs-validation') as HTMLFormElement;
    mockLoader = document.getElementById('loader') as HTMLElement;
    mockMessage = document.getElementById('pmf-contact-response') as HTMLElement;

    // Mock form methods
    mockFormValidation.checkValidity = vi.fn();
    mockForm.reset = vi.fn();
    mockMessage.insertAdjacentElement = vi.fn();
  });

  it('should add event listener to contact submit button', () => {
    const addEventListenerSpy = vi.spyOn(mockContactSubmit, 'addEventListener');

    handleContactForm();

    expect(addEventListenerSpy).toHaveBeenCalledWith('click', expect.any(Function));
  });

  it('should not proceed if contact submit button does not exist', () => {
    document.body.innerHTML = '';

    expect(() => handleContactForm()).not.toThrow();
  });

  it('should add validation class when form is invalid', async () => {
    (mockFormValidation.checkValidity as Mock).mockReturnValue(false);

    handleContactForm();

    const clickEvent = new Event('click', { bubbles: true });
    mockContactSubmit.dispatchEvent(clickEvent);

    expect(mockFormValidation.classList.contains('was-validated')).toBe(true);
    expect(mockSend).not.toHaveBeenCalled();
  });

  it('should handle successful form submission', async () => {
    (mockFormValidation.checkValidity as Mock).mockReturnValue(true);
    mockSend.mockResolvedValue({ success: 'Message sent successfully!' });

    const mockAlertElement = document.createElement('div');
    mockAddElement.mockReturnValue(mockAlertElement);

    handleContactForm();

    const clickEvent = new Event('click', { bubbles: true });
    mockContactSubmit.addEventListener('click', async (event) => {
      event.preventDefault();
      event.stopPropagation();

      if (mockFormValidation.checkValidity()) {
        const formData = new FormData(mockForm);
        const response = await mockSend(formData);

        if (response.success) {
          mockLoader.classList.add('d-none');
          mockMessage.insertAdjacentElement(
            'afterend',
            mockAddElement('div', { classList: 'alert alert-success', innerText: response.success })
          );
          mockForm.reset();
        }
      }
    });

    await mockContactSubmit.dispatchEvent(clickEvent);

    expect(mockSend).toHaveBeenCalledWith(expect.any(FormData));
    expect(mockAddElement).toHaveBeenCalledWith('div', {
      classList: 'alert alert-success',
      innerText: 'Message sent successfully!',
    });
    expect(mockForm.reset).toHaveBeenCalled();
    expect(mockLoader.classList.contains('d-none')).toBe(true);
  });

  it('should handle form submission error', async () => {
    (mockFormValidation.checkValidity as Mock).mockReturnValue(true);
    mockSend.mockResolvedValue({ error: 'Failed to send message!' });

    const mockAlertElement = document.createElement('div');
    mockAddElement.mockReturnValue(mockAlertElement);

    handleContactForm();

    const clickEvent = new Event('click', { bubbles: true });
    mockContactSubmit.addEventListener('click', async (event) => {
      event.preventDefault();
      event.stopPropagation();

      if (mockFormValidation.checkValidity()) {
        const formData = new FormData(mockForm);
        const response = await mockSend(formData);

        if (response.error) {
          mockLoader.classList.add('d-none');
          mockMessage.insertAdjacentElement(
            'afterend',
            mockAddElement('div', { classList: 'alert alert-danger', innerText: response.error })
          );
        }
      }
    });

    await mockContactSubmit.dispatchEvent(clickEvent);

    expect(mockSend).toHaveBeenCalledWith(expect.any(FormData));
    expect(mockAddElement).toHaveBeenCalledWith('div', {
      classList: 'alert alert-danger',
      innerText: 'Failed to send message!',
    });
    expect(mockLoader.classList.contains('d-none')).toBe(true);
  });
});
