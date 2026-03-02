/**
 * Enhanced Example Plugin - Frontend JavaScript
 *
 * This script demonstrates how plugins can provide their own JavaScript
 * functionality for frontend pages.
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

/* global document, console, setTimeout */

(function () {
  'use strict';

  // Wait for DOM to be ready
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }

  function init() {
    console.log('Enhanced Example Plugin: Frontend script loaded');

    // Find all enhanced greeting elements
    const greetings = document.querySelectorAll('.pmf-plugin-enhanced-greeting');

    greetings.forEach((greeting) => {
      // Add a click handler for interactivity
      greeting.addEventListener('click', function () {
        this.style.transform = 'scale(0.98)';
        setTimeout(() => {
          this.style.transform = '';
        }, 150);
      });

      // Add hover effect
      greeting.style.cursor = 'pointer';
      greeting.title = 'Click me!';
    });
  }
})();
