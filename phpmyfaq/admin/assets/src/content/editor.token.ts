/**
 * CSRF token of the WYSIWYG editor's own API calls
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2026-11-21
 */

/**
 * The image upload, the media browser and the internal FAQ search verify the
 * `pmf-csrf-token` page token. On the FAQ editor that is the token of the form
 * itself (`#pmf-csrf-token`); the news and custom page forms use that field for
 * their own save/update token and carry the editor's one in `#pmf-editor-csrf-token`.
 */
export const getEditorCsrfToken = (): string => {
  const editorToken = (document.getElementById('pmf-editor-csrf-token') as HTMLInputElement | null)?.value;
  if (editorToken) {
    return editorToken;
  }

  return (document.getElementById('pmf-csrf-token') as HTMLInputElement | null)?.value ?? '';
};
