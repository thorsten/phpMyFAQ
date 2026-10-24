/**
 * Jodit Editor plugin to keep the size of resized videos
 *
 * Jodit's resizer sizes a <video> through its inline style, and the HTML
 * sanitizer drops style attributes when the content is saved. This plugin
 * moves the size into the width and height attributes of the video instead.
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
 * Stores the rendered size of a video as its width and height attributes.
 */
export const applyVideoSize = (video: HTMLVideoElement): void => {
  video.setAttribute('width', String(Math.round(video.offsetWidth)));
  video.setAttribute('height', String(Math.round(video.offsetHeight)));

  video.style.removeProperty('width');
  video.style.removeProperty('height');
  if (video.getAttribute('style')?.trim() === '') {
    video.removeAttribute('style');
  }
};

const findVideo = (target: Element | null): HTMLVideoElement | null =>
  target?.closest('video') ?? target?.closest(VIDEO_WRAPPER_TAG)?.querySelector('video') ?? null;

Jodit.plugins.add('videoResize', (editor: Jodit): void => {
  const boundVideos = new WeakSet<HTMLVideoElement>();

  // A click on a video lands on its media wrapper; the resizer then resizes the
  // video, so the video is bound here before a drag can start.
  editor.e.on('mousedown', (event: MouseEvent): void => {
    const video = findVideo(event.target as Element | null);
    if (!video || boundVideos.has(video)) {
      return;
    }

    boundVideos.add(video);
    // Fired on every move of a drag; the resizer synchronizes the value once the drag ends
    editor.e.on(video, 'changesize', (): void => applyVideoSize(video));
  });
});
