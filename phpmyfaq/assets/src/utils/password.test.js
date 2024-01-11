import { handlePasswordStrength, handlePasswordToggle, passwordStrength } from './password';

describe('handlePasswordToggle', () => {
  document.body.innerHTML = `
    <div>
      <input type="password" data-pmf-toggle="toggle1">
      <span id="toggle1"></span>
      <span id="toggle1_icon" class="bi-eye"></span>
    </div>
    <div>
      <input type="password" data-pmf-toggle="toggle2">
      <span id="toggle2"></span>
      <span id="toggle2_icon" class="bi-eye"></span>
    </div>
  `;

  afterEach(() => {
    document.body.innerHTML = '';
  });

  test('should toggle the password visibility when clicked', () => {
    handlePasswordToggle();

    // Trigger a click event on the first toggle
    const toggle1 = document.getElementById('toggle1');
    toggle1.click();

    // Check if the first password input type is 'text'
    const passwordInput1 = document.querySelector('input[data-pmf-toggle="toggle1"]');
    expect(passwordInput1.getAttribute('type')).toBe('text');

    // Check if the first icon has the 'bi-eye-slash' class
    const icon1 = document.getElementById('toggle1_icon');
    expect(icon1.classList.contains('bi-eye-slash')).toBe(true);

    // Trigger a click event on the second toggle
    const toggle2 = document.getElementById('toggle2');
    toggle2.click();

    // Check if the second password input type is 'text'
    const passwordInput2 = document.querySelector('input[data-pmf-toggle="toggle2"]');
    expect(passwordInput2.getAttribute('type')).toBe('text');

    // Check if the second icon has the 'bi-eye-slash' class
    const icon2 = document.getElementById('toggle2_icon');
    expect(icon2.classList.contains('bi-eye-slash')).toBe(true);
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
