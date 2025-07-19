/**
 * Utility functions
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2023-2025 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2023-01-04
 */

import { Tooltip } from 'bootstrap';

export const selectAll = (selectId: string): void => {
  const selectElement = document.querySelector(`#${selectId}`) as HTMLSelectElement;
  if (selectElement) {
    for (const option of selectElement.options) {
      option.selected = true;
    }
  }
};

export const unSelectAll = (selectId: string): void => {
  const selectElement = document.querySelector(`#${selectId}`) as HTMLSelectElement;
  if (selectElement) {
    for (const option of selectElement.options) {
      option.selected = false;
    }
  }
};

export const formatBytes = (bytes: number, decimals: number = 2): string => {
  if (bytes === 0) {
    return '0 Bytes';
  }

  const k = 1024;
  const dm = decimals < 0 ? 0 : decimals;
  const sizes = ['Bytes', 'KiB', 'MiB', 'GiB', 'TiB', 'PiB', 'EiB', 'ZiB', 'YiB'];

  const i = Math.floor(Math.log(bytes) / Math.log(k));

  return `${parseFloat((bytes / Math.pow(k, i)).toFixed(dm))} ${sizes[i]}`;
};

export const initializeTooltips = (): void => {
  const tooltipTriggerList = Array.from(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
  tooltipTriggerList.forEach((tooltipTriggerEl) => {
    new Tooltip(tooltipTriggerEl);
  });
};

export const normalizeLanguageCode = (code: string): string => {
  if (!code) {
    return code;
  }
  return code.replace(/_/g, '-').replace(/-([a-z]{2})$/i, (_, region) => '-' + region.toUpperCase());
};
