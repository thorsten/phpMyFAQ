/* @vitest-environment jsdom */
import { afterEach, beforeEach, describe, expect, test, vi, type MockedFunction } from 'vitest';
import { handlePasswordToggle } from './utils';
import { addElasticsearchServerInput, selectDatabaseSetup, stepIndicator } from './configuration';

// Mock dependencies used by setup.ts
vi.mock('./utils', () => ({
  handlePasswordToggle: vi.fn(),
}));

vi.mock('./configuration', () => ({
  addElasticsearchServerInput: vi.fn(),
  selectDatabaseSetup: vi.fn(),
  stepIndicator: vi.fn(),
}));

const mockHandlePasswordToggle = handlePasswordToggle as unknown as MockedFunction<typeof handlePasswordToggle>;
const mockAddElasticsearchServerInput = addElasticsearchServerInput as unknown as MockedFunction<
  typeof addElasticsearchServerInput
>;
const mockSelectDatabaseSetup = selectDatabaseSetup as unknown as MockedFunction<typeof selectDatabaseSetup>;
const mockStepIndicator = stepIndicator as unknown as MockedFunction<typeof stepIndicator>;

// Helper to (re)load the module under test after setting up DOM
const loadSetupModule = async () => {
  vi.resetModules();
  await import('./setup');
  document.dispatchEvent(new Event('DOMContentLoaded'));
};

describe('Setup wizard', () => {
  beforeEach(() => {
    document.body.innerHTML = `
      <form id="phpmyfaq-setup-form">
        <select id="sql_type"></select>
        <button id="pmf-add-elasticsearch-host" type="button"></button>
        <button id="nextBtn" type="button">Next</button>
        <button id="prevBtn" type="button">Previous</button>
        <button id="installingBtn" type="button" class="d-none">Installing</button>
        <div class="step" style="display: none;">
          <input required value="">
        </div>
        <div class="step" style="display: none;">
          <select required>
            <option value="test">Test</option>
          </select>
        </div>
        <div class="stepIndicator"></div>
        <div class="stepIndicator"></div>
      </form>
    `;

    vi.clearAllMocks();
  });

  afterEach(() => {
    document.body.innerHTML = '';
    vi.restoreAllMocks();
  });

  test('initializes password toggle on DOMContentLoaded', async () => {
    await loadSetupModule();
    expect(mockHandlePasswordToggle).toHaveBeenCalled();
  });

  test('attaches change listener to database select and calls selectDatabaseSetup on change', async () => {
    await loadSetupModule();
    const select = document.getElementById('sql_type') as HTMLSelectElement;
    select.dispatchEvent(new Event('change', { bubbles: true }));
    expect(mockSelectDatabaseSetup).toHaveBeenCalledTimes(1);
  });

  test('attaches click listener to elasticsearch add button and calls handler on click', async () => {
    await loadSetupModule();
    const btn = document.getElementById('pmf-add-elasticsearch-host') as HTMLButtonElement;
    btn.dispatchEvent(new Event('click', { bubbles: true }));
    expect(mockAddElasticsearchServerInput).toHaveBeenCalledTimes(1);
  });

  test('shows first tab initially and hides previous button', async () => {
    await loadSetupModule();

    const steps = document.getElementsByClassName('step');
    expect((steps[0] as HTMLElement).style.display).toBe('block');

    const prevBtn = document.getElementById('prevBtn') as HTMLElement;
    expect(prevBtn.style.display).toBe('none');

    expect(mockStepIndicator).toHaveBeenCalledWith(0);
  });

  test('clicking next prevents default and validates form - invalid stays on first step', async () => {
    await loadSetupModule();

    const nextBtn = document.getElementById('nextBtn') as HTMLElement;
    const evt = new Event('click', { bubbles: true, cancelable: true });

    // Ensure input is empty and required so it becomes invalid
    const requiredInput = document.querySelector('input[required]') as HTMLInputElement;
    requiredInput.value = '';

    nextBtn.dispatchEvent(evt);

    // preventDefault() should be called in the handler
    expect(evt.defaultPrevented).toBe(true);

    // Still on first step
    const firstStep = document.getElementsByClassName('step')[0] as HTMLElement;
    expect(firstStep.style.display).toBe('block');
    expect(requiredInput.className).toContain('is-invalid');
  });

  test('proceeds to next step when form is valid', async () => {
    await loadSetupModule();

    const requiredInput = document.querySelector('input[required]') as HTMLInputElement;
    requiredInput.value = 'ok';

    const nextBtn = document.getElementById('nextBtn') as HTMLElement;
    nextBtn.dispatchEvent(new Event('click', { bubbles: true, cancelable: true }));

    const secondStep = document.getElementsByClassName('step')[1] as HTMLElement;
    expect(secondStep.style.display).toBe('block');
  });

  test('shows "Submit" on last step and calls submit when advancing past last step', async () => {
    // Prepare DOM with a single step so first tab is the last one
    document.body.innerHTML = `
      <form id="phpmyfaq-setup-form">
        <button id="nextBtn" type="button">Next</button>
        <button id="prevBtn" type="button">Previous</button>
        <button id="installingBtn" type="button" class="d-none">Installing</button>
        <div class="step" style="display: none;">
          <input required value="ready">
        </div>
        <div class="stepIndicator"></div>
      </form>
    `;

    const submitSpy = vi.spyOn(HTMLFormElement.prototype, 'submit').mockImplementation(() => {});

    await loadSetupModule();

    const nextBtn = document.getElementById('nextBtn') as HTMLElement;
    // On the last step the label should be "Submit"
    expect(nextBtn.innerHTML).toBe('Submit');

    // Advance to submit
    nextBtn.dispatchEvent(new Event('click', { bubbles: true, cancelable: true }));

    expect(submitSpy).toHaveBeenCalled();

    const prevBtn = document.getElementById('prevBtn') as HTMLElement;
    const installBtn = document.getElementById('installingBtn') as HTMLElement;
    expect(prevBtn.classList.contains('d-none')).toBe(true);
    expect(nextBtn.classList.contains('d-none')).toBe(true);
    expect(installBtn.classList.contains('d-none')).toBe(false);
  });
});
