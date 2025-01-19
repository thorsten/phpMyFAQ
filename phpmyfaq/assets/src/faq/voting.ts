/**
 * FAQ voting function
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2023-2025 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2023-03-20
 */

import { addElement } from '../utils';
import { saveVoting } from '../api';

export const handleUserVoting = (): void => {
  const votingForm = document.querySelector('.pmf-voting-form') as HTMLFormElement | null;

  if (votingForm) {
    const ratings = document.querySelector('#rating span') as HTMLElement | null;

    if (ratings) {
      const rating = parseInt(ratings.getAttribute('data-rating') || '0', 10);
      const stars = Array.from(votingForm.querySelectorAll('.pmf-voting-star')) as HTMLElement[];

      stars.forEach((star, index) => {
        if (index < rating) {
          star.classList.add('selected');
        } else {
          star.classList.remove('selected');
        }
      });
    }

    votingForm.addEventListener(
      'submit',
      async (event: Event) => {
        event.preventDefault();

        const selectedButton = document.activeElement as HTMLElement | null;
        if (!selectedButton) {
          return;
        }

        const selectedIndex = parseInt(selectedButton.getAttribute('data-star') || '0', 10);
        const stars = Array.from(
          (event.target as HTMLFormElement).querySelectorAll('.pmf-voting-star')
        ) as HTMLElement[];

        stars.forEach((star, index) => {
          if (index < selectedIndex) {
            star.classList.add('selected');
          } else {
            star.classList.remove('selected');
          }
        });

        const previousRating = (event.target as HTMLFormElement).querySelector(
          '.star[aria-pressed="true"]'
        ) as HTMLElement | null;
        if (previousRating) {
          previousRating.removeAttribute('aria-pressed');
        }

        selectedButton.setAttribute('aria-pressed', 'true');

        // Save to backend
        const votingId: string = (document.getElementById('voting-id') as HTMLInputElement).value;
        const votingLanguage: string = (document.getElementById('voting-language') as HTMLInputElement).value;

        const response = await saveVoting(votingId, votingLanguage, selectedIndex);

        if (response.success) {
          const message = document.getElementById('pmf-voting-result') as HTMLElement;
          message.insertAdjacentElement(
            'afterend',
            addElement('div', { classList: 'alert alert-success', innerText: response.success })
          );
        }

        if (response.error) {
          const element = document.getElementById('pmf-voting-result') as HTMLElement;
          element.insertAdjacentElement(
            'afterend',
            addElement('div', { classList: 'alert alert-danger', innerText: response.error })
          );
        }
      },
      false
    );
  }
};

const highlightStars = (event: Event): void => {
  const star = (event.target as HTMLElement).closest('.pmf-voting-star') as HTMLElement | null;
  const form = (event.target as HTMLElement).closest('.pmf-voting-form') as HTMLFormElement | null;

  if (!star || !form) {
    return;
  }

  const selectedIndex = parseInt(star.getAttribute('data-star') || '0', 10);
  const stars = Array.from(form.querySelectorAll('.pmf-voting-star')) as HTMLElement[];

  stars.forEach((star, index) => {
    if (index < selectedIndex) {
      star.classList.add('selected');
    } else {
      star.classList.remove('selected');
    }
  });
};

const resetSelected = (event: Event): void => {
  if (!(event.target as HTMLElement).closest) {
    return;
  }

  const form = (event.target as HTMLElement).closest('.rating') as HTMLFormElement | null;
  if (!form) {
    return;
  }

  const stars = Array.from(form.querySelectorAll('.pmf-voting-star')) as HTMLElement[];
  const clickedButton = form.querySelector('.star[aria-pressed="true"]') as HTMLElement | null;
  const selectedIndex = clickedButton ? parseInt(clickedButton.getAttribute('data-star') || '0', 10) : 0;

  stars.forEach((star, index) => {
    if (index < selectedIndex) {
      star.classList.add('selected');
    } else {
      star.classList.remove('selected');
    }
  });
};

document.addEventListener('mouseover', highlightStars, false);
document.addEventListener('focus', highlightStars, true);
document.addEventListener('mouseleave', resetSelected, true);
document.addEventListener('blur', resetSelected, true);
