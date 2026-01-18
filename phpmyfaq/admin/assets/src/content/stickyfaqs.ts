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
import { Modal } from 'bootstrap';
import { pushErrorNotification, pushNotification } from '../../../../assets/src/utils';
import { updateStickyFaqsOrder, removeStickyFaq } from '../api/sticky-faqs';

/**
 * Show a Bootstrap confirmation modal using the template modal
 * @param message The confirmation message to display
 * @returns Promise that resolves to true if confirmed, false if cancelled
 */
const showConfirmModal = (message: string): Promise<boolean> => {
  return new Promise((resolve) => {
    const modal = document.getElementById('confirmUnstickyModal');
    const modalBody = document.getElementById('confirmUnstickyModalBody');
    const confirmBtn = document.getElementById('confirmUnstickyModalConfirm');

    if (!modal || !modalBody || !confirmBtn) {
      console.error('Confirmation modal not found in DOM');
      resolve(false);
      return;
    }

    // Set the message
    modalBody.textContent = message;

    // Initialize Bootstrap modal
    const bsModal = new Modal(modal);

    // Remove old event listeners by cloning the button
    const newConfirmBtn = confirmBtn.cloneNode(true) as HTMLElement;
    confirmBtn.parentNode?.replaceChild(newConfirmBtn, confirmBtn);

    // Handle confirm button
    const handleConfirm = () => {
      bsModal.hide();
      resolve(true);
      newConfirmBtn.removeEventListener('click', handleConfirm);
    };

    newConfirmBtn.addEventListener('click', handleConfirm);

    // Handle modal close/cancel
    const handleHidden = () => {
      modal.removeEventListener('hidden.bs.modal', handleHidden);
      resolve(false);
    };

    modal.addEventListener('hidden.bs.modal', handleHidden, { once: true });

    // Show modal
    bsModal.show();
  });
};

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

    const confirmed = await showConfirmModal(msgConfirm);
    if (!confirmed) {
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
      await removeStickyFaq(faqId, categoryId, csrfToken, lang);

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
    } catch (error) {
      if (error instanceof Error) {
        pushErrorNotification(error.message);
      } else {
        console.error('Unknown error:', error);
        pushErrorNotification('Error communicating with API');
      }
      btn.disabled = false;
    }
  });
};

const saveStatus = async (currentOrder: string[], container: HTMLElement): Promise<void> => {
  const successAlert = document.getElementById('successAlert');
  const csrf = container.getAttribute('data-csrf') || '';

  if (!csrf) {
    console.warn('CSRF token not found on container');
  }
   
  if (successAlert) {
    successAlert.remove();
  }

  try {
    const response = await updateStickyFaqsOrder(currentOrder, csrf);
    pushNotification(response?.success || 'Order updated');
  } catch (error) {
    if (error instanceof Error) {
      pushErrorNotification(error.message);
    } else {
      console.error('Unknown error:', error);
    }
  }
};
