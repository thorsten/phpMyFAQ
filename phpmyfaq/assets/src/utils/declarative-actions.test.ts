import { afterEach, beforeEach, describe, expect, test, vi } from 'vitest';
import { initDeclarativeActions } from './declarative-actions';

describe('initDeclarativeActions', (): void => {
  beforeEach((): void => {
    document.body.innerHTML = '';
  });

  afterEach((): void => {
    document.body.innerHTML = '';
    vi.restoreAllMocks();
  });

  describe('data-pmf-submit-on-change', (): void => {
    test('submits the surrounding form when the select changes', (): void => {
      document.body.innerHTML = `
        <form id="revisions">
          <select id="revision" data-pmf-submit-on-change>
            <option value="0">none</option>
            <option value="1">one</option>
          </select>
        </form>
      `;
      const form = document.getElementById('revisions') as HTMLFormElement;
      const submit = vi.fn();
      form.submit = submit;

      initDeclarativeActions();
      document.getElementById('revision')?.dispatchEvent(new Event('change'));

      expect(submit).toHaveBeenCalledTimes(1);
    });

    test('does nothing when the control is not inside a form', (): void => {
      document.body.innerHTML = '<select id="orphan" data-pmf-submit-on-change></select>';

      initDeclarativeActions();

      expect((): void => {
        document.getElementById('orphan')?.dispatchEvent(new Event('change'));
      }).not.toThrow();
    });

    test('leaves controls without the attribute alone', (): void => {
      document.body.innerHTML = `
        <form id="plain"><select id="untouched"></select></form>
      `;
      const form = document.getElementById('plain') as HTMLFormElement;
      const submit = vi.fn();
      form.submit = submit;

      initDeclarativeActions();
      document.getElementById('untouched')?.dispatchEvent(new Event('change'));

      expect(submit).not.toHaveBeenCalled();
    });
  });

  describe('data-pmf-navigate-on-change', (): void => {
    test('navigates to the value of the selected option', (): void => {
      document.body.innerHTML = `
        <select id="languages" data-pmf-navigate-on-change>
          <option value="/en/faq.html">English</option>
          <option value="/de/faq.html" selected>Deutsch</option>
        </select>
      `;
      const assigned: string[] = [];
      vi.spyOn(window, 'top', 'get').mockReturnValue({
        location: {
          set href(value: string) {
            assigned.push(value);
          },
        },
      } as unknown as Window);

      initDeclarativeActions();
      document.getElementById('languages')?.dispatchEvent(new Event('change'));

      expect(assigned).toEqual(['/de/faq.html']);
    });

    test('does not navigate when the selected option has no value', (): void => {
      document.body.innerHTML = `
        <select id="languages" data-pmf-navigate-on-change>
          <option value="">choose</option>
        </select>
      `;
      const assigned: string[] = [];
      vi.spyOn(window, 'top', 'get').mockReturnValue({
        location: {
          set href(value: string) {
            assigned.push(value);
          },
        },
      } as unknown as Window);

      initDeclarativeActions();
      document.getElementById('languages')?.dispatchEvent(new Event('change'));

      expect(assigned).toEqual([]);
    });
  });

  describe('data-pmf-reload-on-click', (): void => {
    test('reloads the page on click', (): void => {
      document.body.innerHTML = '<button type="button" id="retry" data-pmf-reload-on-click>Try again</button>';
      const reload = vi.fn();
      vi.spyOn(window, 'location', 'get').mockReturnValue({ reload } as unknown as Location);

      initDeclarativeActions();
      document.getElementById('retry')?.click();

      expect(reload).toHaveBeenCalledTimes(1);
    });
  });

  test('binds only inside the given root', (): void => {
    document.body.innerHTML = `
      <div id="inside"><button type="button" id="bound" data-pmf-reload-on-click></button></div>
      <div id="outside"><button type="button" id="unbound" data-pmf-reload-on-click></button></div>
    `;
    const reload = vi.fn();
    vi.spyOn(window, 'location', 'get').mockReturnValue({ reload } as unknown as Location);

    initDeclarativeActions(document.getElementById('inside') as ParentNode);

    document.getElementById('unbound')?.click();
    expect(reload).not.toHaveBeenCalled();

    document.getElementById('bound')?.click();
    expect(reload).toHaveBeenCalledTimes(1);
  });
});
