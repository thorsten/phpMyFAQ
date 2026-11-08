/**
 * Behaviours that used to be inline event handler attributes
 *
 * The Content-Security-Policy no longer allows 'unsafe-inline' for scripts, and a nonce cannot
 * whitelist an onclick/onchange attribute, so those handlers were replaced by the data attributes
 * below. Markup declares the behaviour, this module binds it.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2026 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2026-11-08
 */

/** Submits the surrounding form as soon as the control changes. */
export const SUBMIT_ON_CHANGE_ATTRIBUTE = 'data-pmf-submit-on-change';

/** Navigates the top-level window to the value of the selected option. */
export const NAVIGATE_ON_CHANGE_ATTRIBUTE = 'data-pmf-navigate-on-change';

/** Reloads the current page when the element is clicked. */
export const RELOAD_ON_CLICK_ATTRIBUTE = 'data-pmf-reload-on-click';

const bindSubmitOnChange = (root: ParentNode): void => {
  root.querySelectorAll(`[${SUBMIT_ON_CHANGE_ATTRIBUTE}]`).forEach((element: Element): void => {
    element.addEventListener('change', (): void => {
      const form = (element as HTMLInputElement | HTMLSelectElement).form;
      form?.submit();
    });
  });
};

const bindNavigateOnChange = (root: ParentNode): void => {
  root.querySelectorAll(`select[${NAVIGATE_ON_CHANGE_ATTRIBUTE}]`).forEach((element: Element): void => {
    element.addEventListener('change', (): void => {
      const select = element as HTMLSelectElement;
      const target = select.options[select.selectedIndex]?.value;

      if (target === undefined || target === '') {
        return;
      }

      // The selector may sit in a frame; navigate the outermost window, as the inline handler did.
      const targetWindow = window.top ?? window;
      targetWindow.location.href = target;
    });
  });
};

const bindReloadOnClick = (root: ParentNode): void => {
  root.querySelectorAll(`[${RELOAD_ON_CLICK_ATTRIBUTE}]`).forEach((element: Element): void => {
    element.addEventListener('click', (): void => window.location.reload());
  });
};

/**
 * Binds every declarative action in the given root, the whole document by default.
 */
export const initDeclarativeActions = (root: ParentNode = document): void => {
  bindSubmitOnChange(root);
  bindNavigateOnChange(root);
  bindReloadOnClick(root);
};
