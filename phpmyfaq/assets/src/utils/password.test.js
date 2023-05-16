import { handlePasswordStrength, handlePasswordToggle, passwordStrength } from './password';

describe('handlePasswordToggle', () => {
  let togglePassword;
  let password;

  beforeEach(() => {
    document.body.innerHTML = `
      <input type="password" id="faqpassword">
      <button id="togglePassword"></button>
    `;
    togglePassword = document.querySelector('#togglePassword');
    password = document.querySelector('#faqpassword');
  });

  afterEach(() => {
    document.body.innerHTML = '';
  });

  test('should toggle the password visibility when clicked', () => {
    handlePasswordToggle();

    togglePassword.click();
    expect(password.getAttribute('type')).toBe('text');
    expect(togglePassword.classList.contains('is-active')).toBe(true);

    togglePassword.click();
    expect(password.getAttribute('type')).toBe('password');
    expect(togglePassword.classList.contains('is-active')).toBe(false);
  });

  test('should not throw error if togglePassword element is missing', () => {
    handlePasswordToggle();

    document.body.innerHTML = `
      <input type="password" id="faqpassword">
    `;

    expect(() => {
      togglePassword.click();
    }).not.toThrow();
  });
});

describe('handlePasswordStrength', () => {
  let passwordElement;
  let strengthElement;

  beforeEach(() => {
    document.body.innerHTML = `
      <input type="password" id="faqpassword">
      <div id="strength"></div>
    `;

    passwordElement = document.querySelector('#faqpassword');
    strengthElement = document.getElementById('strength');
  });

  test('should update strength element width on password keyup', () => {
    handlePasswordStrength();

    passwordElement.value = 'Password123';
    passwordElement.dispatchEvent(new Event('keyup'));

    expect(strengthElement.style.width).toBe('75%');
  });

  test('should not update strength element width if password or strength elements are missing', () => {
    handlePasswordStrength();

    passwordElement.remove();
    passwordElement.dispatchEvent(new Event('keyup'));

    expect(strengthElement.style.width).toBe('0%');
  });
});

describe('passwordStrength', () => {
  test('should calculate password strength correctly', () => {
    expect(passwordStrength('password')).toBe(1);
    expect(passwordStrength('Password')).toBe(2);
    expect(passwordStrength('Password123')).toBe(3);
    expect(passwordStrength('SecureP@ssword123')).toBe(5);
  });
});
