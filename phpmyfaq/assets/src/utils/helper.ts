/**
 * phpMyFAQ utility functions
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2020-2026 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2020-12-13
 */

/**
 * Adds a new node after the given reference node.
 * @param referenceNode
 * @param newNode
 */
export const insertAfter = (referenceNode: Node, newNode: Node): void => {
  if (referenceNode.parentNode) {
    referenceNode.parentNode.insertBefore(newNode, referenceNode.nextSibling);
  }
};

/**
 * Creates a new element with the given tag name.
 * @param HTMLElement
 * @param properties
 * @param children
 * @returns {*}
 */
export const addElement = (
  HTMLElement: string,
  properties: Record<string, unknown> = {},
  children: Node[] = []
): HTMLElement => {
  const element = document.createElement(HTMLElement);

  Object.keys(properties).forEach((key: string): void => {
    if (key.startsWith('data-') || key.startsWith('aria-')) {
      // Set data-* and aria-* attributes directly as HTML attributes
      element.setAttribute(key, properties[key] as string);
    } else if (key === 'classList') {
      // Handle classList specially
      const classes = properties[key] as string;
      classes.split(' ').forEach((cls) => {
        if (cls) element.classList.add(cls);
      });
    } else if (key === 'checked' || key === 'disabled' || key === 'selected') {
      // Handle boolean attributes
      if (properties[key]) {
        element.setAttribute(key, '');
      }
    } else {
      // Set other properties directly
      (element as unknown as Record<string, unknown>)[key] = properties[key];
    }
  });

  children.forEach((child: Node): Node => element.appendChild(child));
  return element;
};

/**
 * Escapes a given string.
 * @param text
 * @returns {Text}
 */
export const escape = (text: string): string => {
  const map: Record<string, string> = {
    '&': '&amp;',
    '<': '&lt;',
    '>': '&gt;',
    '"': '&quot;',
    "'": '&#039;',
  };

  return text.replace(/[&<>"']/g, (mapped: string): string => {
    return map[mapped as keyof typeof map];
  });
};

/**
 * Sort an array containing numbers
 * @param arr
 * @returns {*}
 */
export const sort = (arr: number[]): number[] => arr.sort((a, b) => a - b);

/**
 * Converts a FormData object into a plain object
 * @returns {{}}
 * @param formData
 */
export function serialize(formData: FormData): Record<string, FormDataEntryValue | FormDataEntryValue[]> {
  const obj: Record<string, FormDataEntryValue | FormDataEntryValue[]> = {};
  formData.forEach((value, key) => {
    if (obj[key]) {
      if (Array.isArray(obj[key])) {
        (obj[key] as FormDataEntryValue[]).push(value);
      } else {
        obj[key] = [obj[key] as FormDataEntryValue, value];
      }
    } else {
      obj[key] = value;
    }
  });
  return obj;
}

export const capitalize = (string: string): string => {
  return string.charAt(0).toUpperCase() + string.slice(1);
};
