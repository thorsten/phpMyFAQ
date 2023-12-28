/**
 * Stuff for ordering sticky faqs customly
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Jan Harms <model_railroader@gmx-topmail.de>
 * @copyright 2014-2023 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2023-12-27
 */

import Sortable from 'sortablejs';
import { addElement } from '../../../../assets/src/utils';

export const handleStickyFaqs = () => {
    const stickyFAQs = document.getElementById('stickyFAQs');
    if (stickyFAQs) {
        Sortable.create(stickyFAQs, {
            animation: 100,
            draggable: '.list-group-item',
            handle: '.list-group-item',
            sort: true,
            filter: '.sortable-disabled',
            dataIdAttr: 'data-pmf-faqid',
            onEnd: (event) => {
                const currentOrder = Array.from(event.from.children).map(function (item) {
                    return item.getAttribute('data-pmf-faqid');
                });
                saveStatus(currentOrder);
            }
        });
    }
};

const saveStatus = async (currentOrder) => {
    const stickyFAQs = document.getElementById('stickyFAQs');
    const card = document.getElementById('mainCardStickyFAQs');
    const successAlert = document.getElementById('successAlert');
    const csrf = stickyFAQs.getAttribute('data-csrf');
    if (successAlert) {
        successAlert.remove();
    }
    try {
        const response = await fetch('./api/faqs/sticky/order', {
            method: 'POST',
            headers: {
                Accept: 'application/json, text/plain, */*',
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                faqIds: currentOrder,
                csrf: csrf
            })
        });

        if (response.ok) {
            const jsonResponse = await response.json();

            card.insertAdjacentElement(
                'beforebegin',
                addElement('div', {classList: 'alert alert-success', id: 'successAlert', innerText: jsonResponse.success})
            );
        } else {
            const errorResponse = await response.json(); // assuming the error response is in JSON format
            throw new Error('Network response was not ok: ' + JSON.stringify(errorResponse));
        }
    } catch (error) {
        console.error('Error:', error.message);
    }
};
