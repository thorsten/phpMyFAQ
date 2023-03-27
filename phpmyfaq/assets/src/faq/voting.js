/**
 * FAQ voting function
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
 * @since     2023-03-20
 */
import { addElement } from '../utils';

export const handleUserVoting = () => {
  const votingForm = document.querySelector('.pmf-voting-form');

  if (votingForm) {
    const ratings = document.querySelector('#rating span');

    if (ratings) {
      const rating = parseInt(ratings.getAttribute('data-rating'), 10);
      const stars = Array.from(votingForm.querySelectorAll('.pmf-voting-star'));

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
      (event) => {
        event.preventDefault();

        const selectedButton = document.activeElement;
        if (!selectedButton) {
          return;
        }

        const selectedIndex = parseInt(selectedButton.getAttribute('data-star'), 10);
        const stars = Array.from(event.target.querySelectorAll('.pmf-voting-star'));

        stars.forEach((star, index) => {
          if (index < selectedIndex) {
            star.classList.add('selected');
          } else {
            star.classList.remove('selected');
          }
        });

        const previousRating = event.target.querySelector('.star[aria-pressed="true"]');
        if (previousRating) {
          previousRating.removeAttribute('aria-pressed');
        }

        selectedButton.setAttribute('aria-pressed', true);

        // Save to backend
        const votingId = document.getElementById('voting-id').value;
        const votingLanguage = document.getElementById('voting-language').value;
        fetch(`api.service.php?action=add-voting`, {
          method: 'POST',
          headers: {
            Accept: 'application/json, text/plain, */*',
            'Content-Type': 'application/json',
          },
          body: JSON.stringify({
            id: votingId,
            lang: votingLanguage,
            value: selectedIndex,
          }),
        })
          .then(async (response) => {
            if (response.ok) {
              return response.json();
            }
            throw new Error('Network response was not ok: ', { cause: { response } });
          })
          .then((response) => {
            if (response.success) {
              const message = document.getElementById('pmf-voting-result');
              message.insertAdjacentElement(
                'afterend',
                addElement('div', { classList: 'alert alert-success', innerText: response.success })
              );
            } else {
              const element = document.getElementById('pmf-voting-result');
              element.insertAdjacentElement(
                'afterend',
                addElement('div', { classList: 'alert alert-danger', innerText: response.error })
              );
            }
          })
          .catch(async (error) => {
            const element = document.getElementById('pmf-voting-result');
            const errorMessage = await error.cause.response.json();
            element.insertAdjacentElement(
              'afterend',
              addElement('div', { classList: 'alert alert-danger', innerText: errorMessage.error })
            );
          });
      },
      false
    );
  }
};

const highlightStars = (event) => {
  const star = event.target.closest('.pmf-voting-star');
  const form = event.target.closest('.pmf-voting-form');

  if (!star || !form) {
    return;
  }

  const selectedIndex = parseInt(star.getAttribute('data-star'), 10);
  const stars = Array.from(form.querySelectorAll('.pmf-voting-star'));

  stars.forEach((star, index) => {
    if (index < selectedIndex) {
      star.classList.add('selected');
    } else {
      star.classList.remove('selected');
    }
  });
};

const resetSelected = (event) => {
  if (!event.target.closest) {
    return;
  }

  const form = event.target.closest('.rating');
  if (!form) {
    return;
  }

  const stars = Array.from(form.querySelectorAll('.pmf-voting-star'));
  const clickedButton = form.querySelector('.star[aria-pressed="true"]');
  const selectedIndex = clickedButton ? parseInt(clickedButton.getAttribute('data-star'), 10) : 0;

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
