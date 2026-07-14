/**
 * FAQ editor client-side validation
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

import { Tab } from 'bootstrap';
import { getJoditEditor } from './editor';

export interface ValidationError {
  fieldId: string;
  tabHref: string;
  feedbackId: string;
}

export const getAnswerContent = (): string => {
  const joditEditor = getJoditEditor();
  if (joditEditor) {
    return joditEditor.value;
  }

  const markdownEditor = document.getElementById('answer-markdown') as HTMLTextAreaElement | null;
  if (markdownEditor) {
    return markdownEditor.value;
  }

  const plainEditor = document.getElementById('editor') as HTMLTextAreaElement | null;
  if (plainEditor) {
    return plainEditor.value;
  }

  return '';
};

// An answer counts as empty only when it has neither text nor embedded media —
// image-only or video-only answers are valid content. DOMParser parses the
// markup inertly (no script execution or resource loading).
const isAnswerEmpty = (html: string): boolean => {
  const parsed = new DOMParser().parseFromString(html, 'text/html');

  if ((parsed.body.textContent ?? '').trim() !== '') {
    return false;
  }

  return parsed.body.querySelector('img, video, audio, iframe, embed, object') === null;
};

export const validateFaqEditor = (): ValidationError[] => {
  const errors: ValidationError[] = [];

  const question = document.getElementById('question') as HTMLInputElement | null;
  if (question && question.value.trim() === '') {
    errors.push({ fieldId: 'question', tabHref: '#tab-question-answer', feedbackId: 'question-required-feedback' });
  } else if (question && question.value.includes('#')) {
    errors.push({ fieldId: 'question', tabHref: '#tab-question-answer', feedbackId: 'question-hash-feedback' });
  }

  if (isAnswerEmpty(getAnswerContent())) {
    errors.push({ fieldId: 'answer', tabHref: '#tab-question-answer', feedbackId: 'answer-invalid-feedback' });
  }

  const email = document.getElementById('email') as HTMLInputElement | null;
  if (email && email.value.trim() !== '' && email.validity.typeMismatch) {
    errors.push({ fieldId: 'email', tabHref: '#tab-meta-data', feedbackId: 'email-invalid-feedback' });
  }

  const categoryCheckboxes = document.querySelectorAll<HTMLInputElement>(
    '#pmf-faq-category-tree input[name="categories[]"]'
  );
  if (categoryCheckboxes.length > 0 && !Array.from(categoryCheckboxes).some((checkbox) => checkbox.checked)) {
    errors.push({
      fieldId: 'pmf-faq-category-tree',
      tabHref: '#tab-meta-data',
      feedbackId: 'categories-invalid-feedback',
    });
  }

  return errors;
};

export const updateTabErrorBadges = (errors: ValidationError[]): void => {
  const counts = new Map<string, number>();
  errors.forEach((error) => counts.set(error.tabHref, (counts.get(error.tabHref) ?? 0) + 1));

  document.querySelectorAll<HTMLElement>('[data-pmf-tab-error-badge]').forEach((badge) => {
    const count = counts.get(badge.getAttribute('data-pmf-tab-error-badge') ?? '') ?? 0;
    badge.textContent = count > 0 ? count.toString() : '';
    badge.classList.toggle('d-none', count === 0);
  });
};

export const clearValidationFeedback = (): void => {
  document.querySelectorAll('.is-invalid').forEach((element) => element.classList.remove('is-invalid'));
  document.querySelectorAll<HTMLElement>('[data-pmf-validation-feedback]').forEach((feedback) => {
    feedback.classList.remove('d-block');
    feedback.classList.add('d-none');
  });
  updateTabErrorBadges([]);
};

export const applyValidationFeedback = (errors: ValidationError[]): void => {
  clearValidationFeedback();

  for (const error of errors) {
    const feedback = document.getElementById(error.feedbackId);
    if (feedback) {
      feedback.classList.remove('d-none');
      feedback.classList.add('d-block');
    }

    const field = document.getElementById(error.fieldId);
    if (field && field instanceof HTMLInputElement) {
      field.classList.add('is-invalid');
      field.addEventListener('input', () => field.classList.remove('is-invalid'), { once: true });
    }
  }

  updateTabErrorBadges(errors);
};

export const showFirstValidationError = (errors: ValidationError[]): void => {
  const [firstError] = errors;
  if (!firstError) {
    return;
  }

  const tabLink = document.querySelector(`a[data-bs-toggle="tab"][href="${firstError.tabHref}"]`);
  if (tabLink) {
    Tab.getOrCreateInstance(tabLink).show();
  }

  // Focus the field when it is focusable; otherwise focus the feedback
  // message itself (feedback elements carry tabindex="-1"), so keyboard and
  // screen-reader users land on the error even for the editor/tree widgets.
  const field = document.getElementById(firstError.fieldId);
  field?.focus();
  if (!field || document.activeElement !== field) {
    document.getElementById(firstError.feedbackId)?.focus();
  }
};
