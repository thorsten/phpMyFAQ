/**
 * Jodit Editor plugin to resize uploaded videos
 *
 * Jodit wraps every <video> in a <jodit-media> element, which the resizer handles
 * once it is listed in allowResizeTags. The wrapper is removed when the content is
 * saved, so this plugin copies its size onto the video as width and height
 * attributes, just like Jodit does for the wrapper of an iframe.
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
 * @since     2026-10-24
 */

import { Jodit } from 'jodit';

const VIDEO_WRAPPER_TAG = 'jodit-media';

/**
 * Stores the size of a resized video wrapper as width and height attributes of its video.
 */
export const applyWrapperSizeToVideo = (wrapper: HTMLElement): void => {
  const video = wrapper.querySelector('video');
  if (!video) {
    return;
  }

  video.setAttribute('width', String(Math.round(wrapper.offsetWidth)));
  video.setAttribute('height', String(Math.round(wrapper.offsetHeight)));

  // An inline size would win over the attributes, and the sanitizer drops style on save
  video.style.removeProperty('width');
  video.style.removeProperty('height');
};

Jodit.plugins.add('videoResize', (editor: Jodit): void => {
  const boundWrappers = new WeakSet<HTMLElement>();

  // The resizer binds an element on click, so binding on mousedown is always in time. The editor
  // event survives a replaced editor element, unlike a listener on editor.editor at plugin start.
  editor.e.on('mousedown', (event: MouseEvent): void => {
    const target = event.target as Element | null;
    const wrapper = target?.closest<HTMLElement>(VIDEO_WRAPPER_TAG);
    if (!wrapper || boundWrappers.has(wrapper)) {
      return;
    }

    boundWrappers.add(wrapper);
    // Fired on every move of a drag; the resizer synchronizes the value once the drag ends
    editor.e.on(wrapper, 'changesize', (): void => applyWrapperSizeToVideo(wrapper));
  });
});
