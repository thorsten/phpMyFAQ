/**
 * FAQ editor dirty state tracking
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

import { getJoditEditor } from './editor';

let dirty = false;

export const markDirty = (): void => {
  dirty = true;
};

export const markClean = (): void => {
  dirty = false;
};

export const isFaqEditorDirty = (): boolean => dirty;

// The #faqEditor form spans invalid HTML nesting, so several controls (sidebar
// radios, meta fields) are parser-associated with the form without being DOM
// descendants of it — form-level event delegation would miss them. Checking the
// form owner on document-level events covers all associated controls. Only
// named controls count: unnamed helpers inside the form (the category filter
// box, the dropzone file input) never reach FormData, so touching them must
// not mark the FAQ dirty.
const isFaqEditorField = (target: EventTarget | null): boolean => {
  if (!(target instanceof Element)) {
    return false;
  }
  const control = target as HTMLInputElement;
  return Boolean(control.name) && control.form?.id === 'faqEditor';
};

const onFormMutation = (event: Event): void => {
  if (isFaqEditorField(event.target)) {
    markDirty();
  }
};

const onBeforeUnload = (event: BeforeUnloadEvent): void => {
  if (!dirty) {
    return;
  }
  event.preventDefault();
  event.returnValue = '';
};

export const handleDirtyState = (): void => {
  const form = document.getElementById('faqEditor');
  if (!form) {
    return;
  }

  markClean();

  document.removeEventListener('input', onFormMutation);
  document.removeEventListener('change', onFormMutation);
  document.addEventListener('input', onFormMutation);
  document.addEventListener('change', onFormMutation);

  getJoditEditor()?.events.on('change', markDirty);

  window.removeEventListener('beforeunload', onBeforeUnload);
  window.addEventListener('beforeunload', onBeforeUnload);
};
