/**
 * Update functions
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
 * @since     2023-10-22
 */

import {
  handleConfigBackup,
  handleDatabaseUpdate,
  handleUpdateInformation,
  handleUpdateNextStepButton,
} from './configuration';

document.addEventListener('DOMContentLoaded', async () => {
  handleUpdateNextStepButton();
  await handleUpdateInformation();
  await handleConfigBackup();
  await handleDatabaseUpdate();
});
