/**
 * Sort sticky FAQs according to your own wishes
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Jan Harms <model_railroader@gmx-topmail.de>
 * @copyright 2023-2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2023-12-27
 */

import Sortable from 'sortablejs';
import { pushErrorNotification, pushNotification } from '../../../../assets/src/utils';

export const handleStickyFaqs = (): void => {
  const stickyFAQs = document.getElementById('stickyFAQs') as HTMLElement | null;
  if (stickyFAQs) {
    Sortable.create(stickyFAQs, {
      animation: 100,
      draggable: '.list-group-item',
      handle: '.list-group-item',
      sort: true,
      filter: '.sortable-disabled',
      dataIdAttr: 'data-pmf-faqid',
      onEnd: async (event: Sortable.SortableEvent) => {
        const currentOrder = Array.from(event.from.children).map((item) => {
          return item.getAttribute('data-pmf-faqid');
        }) as string[];
        await saveStatus(currentOrder);
      },
    });
  }
};

const saveStatus = async (currentOrder: string[]): Promise<void> => {
  const stickyFAQs = document.getElementById('stickyFAQs') as HTMLElement;
  const successAlert = document.getElementById('successAlert') as HTMLElement | null;
  const csrf = stickyFAQs.getAttribute('data-csrf') as string;

  if (successAlert) {
    successAlert.remove();
  }

  try {
    const response = await fetch('./api/faqs/sticky/order', {
      method: 'POST',
      headers: {
        Accept: 'application/json, text/plain, */*',
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        faqIds: currentOrder,
        csrf: csrf,
      }),
    });

    if (response.ok) {
      const jsonResponse = await response.json();
      pushNotification(jsonResponse.success);
    } else {
      const errorResponse = await response.json();
      throw new Error('Network response was not ok: ' + JSON.stringify(errorResponse));
    }
  } catch (error) {
    if (error instanceof Error) {
      pushErrorNotification(error.message);
    } else {
      console.error('Unknown error:', error);
    }
  }
};
