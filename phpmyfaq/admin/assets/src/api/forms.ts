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
 * @copyright 2024-2026 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2014-03-21
 */

import { fetchJson } from './fetch-wrapper';

export const fetchActivateInput = async (
  csrf: string,
  formId: string,
  inputId: string,
  checked: boolean
): Promise<unknown> => {
  return await fetchJson('api/forms/activate', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
    },
    body: JSON.stringify({
      csrf: csrf,
      formid: formId,
      inputid: inputId,
      checked: checked,
    }),
  });
};

export const fetchSetInputAsRequired = async (
  csrf: string,
  formId: string,
  inputId: string,
  checked: boolean
): Promise<unknown> => {
  return await fetchJson('api/forms/required', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
    },
    body: JSON.stringify({
      csrf: csrf,
      formid: formId,
      inputid: inputId,
      checked: checked,
    }),
  });
};

export const fetchEditTranslation = async (
  csrf: string,
  formId: string,
  inputId: string,
  label: string,
  lang: string
): Promise<unknown> => {
  return await fetchJson('api/forms/translation-edit', {
    method: 'POST',
    headers: {
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
};

export const fetchDeleteTranslation = async (
  csrf: string,
  formId: string,
  inputId: string,
  lang: string
): Promise<unknown> => {
  return await fetchJson('api/forms/translation-delete', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
    },
    body: JSON.stringify({
      csrf: csrf,
      formId: formId,
      inputId: inputId,
      lang: lang,
    }),
  });
};

export const fetchAddTranslation = async (
  csrf: string,
  formId: string,
  inputId: string,
  lang: string,
  translation: string
): Promise<unknown> => {
  return await fetchJson('api/forms/translation-add', {
    method: 'POST',
    headers: {
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
};
