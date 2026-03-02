/**
 * Enhanced Example Plugin - Admin JavaScript
 *
 * This script demonstrates admin-specific JavaScript functionality for plugins.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2025 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2025-12-28
 */

/* global document, console */

(function () {
  'use strict';

  // Wait for DOM to be ready
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }

  function init() {
    console.log('Enhanced Example Plugin: Admin script loaded');

    // Find all enhanced greeting elements in admin
    const greetings = document.querySelectorAll('.pmf-plugin-enhanced-greeting');

    greetings.forEach((greeting) => {
      // Add subtle admin-specific interaction
      greeting.addEventListener('mouseenter', function () {
        this.style.borderLeftWidth = '6px';
      });

      greeting.addEventListener('mouseleave', function () {
        this.style.borderLeftWidth = '4px';
      });
    });
  }
})();
