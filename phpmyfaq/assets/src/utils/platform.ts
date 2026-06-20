/**
 * Platform detection for keyboard shortcuts
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2026-06-20
 */

interface UserAgentDataLike {
  platform?: string;
}

export const isMacPlatform = (): boolean => {
  const uaData = (navigator as Navigator & { userAgentData?: UserAgentDataLike }).userAgentData;
  const platform = uaData?.platform ?? navigator.platform ?? '';
  return /mac/i.test(platform);
};

export const getShortcutHintLabel = (): string => (isMacPlatform() ? '⌘ K' : 'Ctrl K');
