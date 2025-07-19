/**
 * Utility functions
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2023-2024 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2023-01-04
 */

import { Tooltip } from 'bootstrap';

export const selectAll = (selectId) => {
  for (const options of [...document.querySelector(`#${selectId}`).options]) {
    options.selected = true;
  }
};

export const unSelectAll = (selectId) => {
  for (const options of [...document.querySelector(`#${selectId}`).options]) {
    options.selected = false;
  }
};

export const formatBytes = (bytes, decimals = 2) => {
  if (!+bytes) {
    return '0 Bytes';
  }

  const k = 1024;
  const dm = decimals < 0 ? 0 : decimals;
  const sizes = ['Bytes', 'KiB', 'MiB', 'GiB', 'TiB', 'PiB', 'EiB', 'ZiB', 'YiB'];

  const i = Math.floor(Math.log(bytes) / Math.log(k));

  return `${parseFloat((bytes / Math.pow(k, i)).toFixed(dm))} ${sizes[i]}`;
};

export const initializeTooltips = () => {
  const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
  const tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
    return new Tooltip(tooltipTriggerEl);
  });
};

export const normalizeLanguageCode = (code) => {
  if (!code) {
    return code;
  }
  return code.replace(/_/g, '-').replace(/-([a-z]{2})$/i, (_, region) => '-' + region.toUpperCase());
};
