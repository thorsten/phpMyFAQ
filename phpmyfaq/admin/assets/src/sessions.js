/**
 * Session Handling
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Jan Harms <model_railroader@gmx-topmail.de>
 * @copyright 2022-2024 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2022-03-21
 */

export const handleSessions = () => {
    const firstHour = document.getElementById('firstHour');
    const lastHour = document.getElementById('lastHour');
    const exportSessions = document.getElementById('exportSessions');
    
    exportSessions.addEventListener('click', (event) => {
        event.preventDefault();
        console.log(firstHour.value);
    });
};


