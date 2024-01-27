/**
 * Glossary administration stuff
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2023 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2023-03-05
 */
import { deleteGlossary } from '../api';

export const handleDeleteGlossary = () => {
  const deleteButtons = document.querySelectorAll('.pmf-admin-delete-glossary');

  if (deleteButtons) {
    deleteButtons.forEach((button) => {
      button.addEventListener('click', async (event) => {
        event.preventDefault();

        const glossaryId = button.getAttribute('data-pmf-glossary-id');
        const csrfToken = button.getAttribute('data-pmf-csrf-token');

        const response = await deleteGlossary(glossaryId, csrfToken);

        if (response) {
          button.closest('tr').remove();
        }
      });
    });
  }
};
