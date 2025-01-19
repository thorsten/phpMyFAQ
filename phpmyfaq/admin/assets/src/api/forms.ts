/**
 * API fetch requests for form editing
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Jan Harms <modelrailroader@gmx-topmail.de>
 * @copyright 2024-2025 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2014-03-21
 */

import { pushNotification } from '../../../../assets/src/utils';
import { Response } from '../interfaces';

export const fetchActivateInput = async (
  csrf: string,
  formId: string,
  inputId: string,
  checked: boolean
): Promise<void> => {
  try {
    const response = await fetch('api/forms/activate', {
      method: 'POST',
      headers: {
        Accept: 'application/json, text/plain, */*',
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        csrf: csrf,
        formid: formId,
        inputid: inputId,
        checked: checked,
      }),
    });

    if (response.ok) {
      const result: Response = await response.json();
      if (result.success) {
        pushNotification(result.success); // @todo move that to the forms.ts file in the content folder
      } else {
        console.error(result.error);
      }
    } else {
      throw new Error('Network response was not ok: ' + (await response.text()));
    }
  } catch (error) {
    console.error('Error activating/deactivating input:', error);
    throw error;
  }
};

export const fetchSetInputAsRequired = async (
  csrf: string,
  formId: string,
  inputId: string,
  checked: boolean
): Promise<void> => {
  try {
    const response = await fetch('api/forms/required', {
      method: 'POST',
      headers: {
        Accept: 'application/json, text/plain, */*',
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        csrf: csrf,
        formid: formId,
        inputid: inputId,
        checked: checked,
      }),
    });

    if (response.ok) {
      const result: Response = await response.json();
      if (result.success) {
        pushNotification(result.success); // @todo move that to the forms.ts file in the content folder
      } else {
        console.error(result.error);
      }
    } else {
      throw new Error('Network response was not ok: ' + (await response.text()));
    }
  } catch (error) {
    console.error('Error setting input as required:', error);
    throw error;
  }
};

export const fetchEditTranslation = async (
  csrf: string,
  formId: string,
  inputId: string,
  label: string,
  lang: string
): Promise<void> => {
  try {
    const response = await fetch('api/forms/translation-edit', {
      method: 'POST',
      headers: {
        Accept: 'application/json, text/plain, */*',
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        csrf: csrf,
        formId: formId,
        inputId: inputId,
        lang: lang,
        label: label,
      }),
    });

    if (response.ok) {
      const result: Response = await response.json();
      if (result.success) {
        pushNotification(result.success); // @todo move that to the forms.ts file in the content folder
      } else {
        console.error(result.error);
      }
    } else {
      throw new Error('Network response was not ok: ' + (await response.text()));
    }
  } catch (error) {
    console.error('Error editing translation:', error);
    throw error;
  }
};

export const fetchDeleteTranslation = async (
  csrf: string,
  formId: string,
  inputId: string,
  lang: string,
  element: HTMLElement
): Promise<void> => {
  try {
    const response = await fetch('api/forms/translation-delete', {
      method: 'POST',
      headers: {
        Accept: 'application/json, text/plain, */*',
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        csrf: csrf,
        formId: formId,
        inputId: inputId,
        lang: lang,
      }),
    });

    if (response.ok) {
      const result: Response = await response.json();
      if (result.success) {
        // @todo move that to the forms.ts file in the content folder
        pushNotification(result.success);
        document.getElementById('item_' + element.getAttribute('data-pmf-lang'))?.remove();
        const option = document.createElement('option');
        option.innerText = element.getAttribute('data-pmf-langname')!;
        document.getElementById('languageSelect')?.appendChild(option);
      } else {
        console.error(result.error);
      }
    } else {
      throw new Error('Network response was not ok: ' + (await response.text()));
    }
  } catch (error) {
    console.error('Error deleting translation:', error);
    throw error;
  }
};

export const fetchAddTranslation = async (
  csrf: string,
  formId: string,
  inputId: string,
  lang: string,
  translation: string
): Promise<void> => {
  try {
    const response = await fetch('api/forms/translation-add', {
      method: 'POST',
      headers: {
        Accept: 'application/json, text/plain, */*',
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        csrf: csrf,
        formId: formId,
        inputId: inputId,
        lang: lang,
        translation: translation,
      }),
    });

    if (response.ok) {
      const result: Response = await response.json();
      if (result.success) {
        // @todo move that to the forms.ts file in the content folder
        pushNotification(result.success);
        setTimeout(function () {
          window.location.reload();
        }, 3000);
      } else {
        console.error(result.error);
      }
    } else {
      throw new Error('Network response was not ok: ' + (await response.text()));
    }
  } catch (error) {
    console.error('Error adding translation:', error);
    throw error;
  }
};
