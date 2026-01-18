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
  
  if (!stickyFAQs) {
    return;
  }

  Sortable.create(stickyFAQs, {
    animation: 150,
    draggable: '.list-group-item',
    handle: '.drag-handle',
    sort: true,
    filter: '.sortable-disabled',
    dataIdAttr: 'data-pmf-faqid',
    onEnd: async (event: Sortable.SortableEvent) => {
      const currentOrder = event.from
        ? Array.from(event.from.children)
            .map((item) => item.getAttribute('data-pmf-faqid'))
            .filter((id): id is string => id !== null)
        : [];
      await saveStatus(currentOrder, stickyFAQs);
    },
  });

  stickyFAQs.addEventListener('click', async (event: Event) => {
    const target = event.target as HTMLElement;
    const btn = target.closest('.js-unstick-button') as HTMLButtonElement | null;

    if (!btn) {
      return;
    }

    event.preventDefault();
    event.stopPropagation();

    const msgConfirm = stickyFAQs.getAttribute('data-lang-confirm') || 'Do you really want to remove this FAQ?';
    const msgSuccess = stickyFAQs.getAttribute('data-lang-success') || 'Successfully removed.';

    if (!confirm(msgConfirm)) {
      return;
    }

    btn.disabled = true;

    const faqId = btn.getAttribute('data-pmf-faq-id');
    const categoryId = btn.getAttribute('data-pmf-category-id');
    const csrfToken = btn.getAttribute('data-pmf-csrf');
    const lang = btn.getAttribute('data-pmf-lang');

    if (!faqId || !categoryId || !csrfToken || !lang) {
      pushErrorNotification('Missing required FAQ information; cannot remove sticky FAQ.');
      btn.disabled = false;
      return;
    }

    try {
      const response = await fetch('./api/faq/sticky', {
        method: 'POST',
        headers: {
          Accept: 'application/json',
          'Content-Type': 'application/json',
          'X-PMF-CSRF-Token': csrfToken,
          'X-Requested-With': 'XMLHttpRequest',
        },
        body: JSON.stringify({
          csrf: csrfToken,
          categoryId: categoryId,
          faqIds: [faqId],
          faqLanguage: lang,
          checked: false,
        }),
      });

      if (response.ok) {
        const result = await response.json();
        if (result && result.success) {
          const row = btn.closest('.list-group-item') as HTMLElement | null;

          if (!row) {
            pushErrorNotification('Could not find FAQ item in the list.');
            btn.disabled = false;
            return;
          }

          row.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
          row.style.opacity = '0';
          row.style.transform = 'translateX(20px)';

          setTimeout(() => {
            row.remove();
            pushNotification(msgSuccess);
          }, 300);
        } else {
          pushErrorNotification(result?.error || 'Unknown API Error');
          btn.disabled = false;
        }
      } else {
        throw new Error('Network response error');
      }
    } catch (error) {
      console.error(error);
      pushErrorNotification('Error communicating with API');
      btn.disabled = false;
    }
  });
};

const saveStatus = async (currentOrder: string[], container: HTMLElement): Promise<void> => {
  const successAlert = document.getElementById('successAlert');
  const csrf = container.getAttribute('data-csrf') || '';

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
      let jsonResponse: any = {};
      try {
        jsonResponse = await response.json();
      } catch {
        // Non-JSON success response — use generic message
      }
      pushNotification(jsonResponse?.success || 'Order updated');
    } else {
      let errMsg = 'Network response was not ok';
      try {
        const errJson = await response.json();
        errMsg = errJson?.error || errMsg;
      } catch {
        // Could not parse error body; keep generic message
      }
      throw new Error(errMsg);
    }
  } catch (error) {
    if (error instanceof Error) {
      pushErrorNotification(error.message);
    } else {
      console.error('Unknown error:', error);
    }
  }
};
