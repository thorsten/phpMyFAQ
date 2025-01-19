/**
 * Fetch data for open questions management
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2024-2025 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2024-12-02
 */

import { Response } from '../interfaces';

export const toggleQuestionVisibility = async (
  questionId: string,
  visibility: boolean,
  csrfToken: string
): Promise<Response | undefined> => {
  try {
    const response = await fetch(`./api/question/visibility/toggle`, {
      method: 'PUT',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        questionId: questionId,
        visibility: visibility,
        csrfToken: csrfToken,
      }),
    });

    if (response.ok) {
      return await response.json();
    } else {
      throw new Error('Network response was not ok.');
    }
  } catch (error) {
    console.error('Error toggling question visibility:', error);
    throw error;
  }
};
