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
        event.stopPropagation();

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
