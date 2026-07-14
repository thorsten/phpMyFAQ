/**
 * FAQ editor live character counters
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2026 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2026-07-13
 */

const renderCounter = (counter: HTMLElement, field: HTMLInputElement | HTMLTextAreaElement, max: number): void => {
  const length = field.value.length;

  counter.textContent = `${length} / ${max}`;
  counter.classList.remove('text-muted', 'text-warning', 'text-danger');
  if (length > max) {
    counter.classList.add('text-danger');
  } else if (length > max * 0.9) {
    counter.classList.add('text-warning');
  } else {
    counter.classList.add('text-muted');
  }
};

export const handleCharacterCounters = (): void => {
  document.querySelectorAll<HTMLElement>('[data-pmf-counter-for]').forEach((counter) => {
    const fieldId = counter.getAttribute('data-pmf-counter-for') ?? '';
    const max = Number(counter.getAttribute('data-pmf-counter-max'));
    const field = document.getElementById(fieldId) as HTMLInputElement | HTMLTextAreaElement | null;

    if (!field || !Number.isFinite(max) || max <= 0) {
      return;
    }

    renderCounter(counter, field, max);
    field.addEventListener('input', () => renderCounter(counter, field, max));
  });
};
