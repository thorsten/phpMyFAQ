/**
 * phpMyFAQ cookie consent
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne
 * @copyright 2023 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2023-01-21
 */

import 'vanilla-cookieconsent';

const cc = initCookieConsent();

cc.run({
  current_lang: 'en',
  autoclear_cookies: true, // default: false
  page_scripts: true, // default: false
  cookie_name: 'phpmyfaq_cc_cookie',
  // mode: 'opt-in'                          // default: 'opt-in'; value: 'opt-in' or 'opt-out'
  // delay: 0,                               // default: 0
  // auto_language: null                     // default: null; could also be 'browser' or 'document'
  // autorun: true,                          // default: true
  // force_consent: false,                   // default: false
  // hide_from_bots: true,                   // default: true
  // remove_cookie_tables: false             // default: false
  // cookie_expiration: 182,                 // default: 182 (days)
  // cookie_necessary_only_expiration: 182   // default: disabled
  // cookie_domain: location.hostname,       // default: current domain
  // cookie_path: '/',                       // default: root
  // cookie_same_site: 'Lax',                // default: 'Lax'
  // use_rfc_cookie: false,                  // default: false
  // revision: 0,                            // default: 0

  onFirstAction: (user_preferences, cookie) => {
    // callback triggered only once
  },

  onAccept: (cookie) => {
    // ...
  },

  onChange: (cookie, changed_preferences) => {
    // ...
  },

  languages: {
    en: {
      consent_modal: {
        title: 'phpMyFAQ use cookies!',
        description:
          'Hi, phpMyFAQ uses essential cookies to ensure its proper operation and tracking cookies to understand how you interact with it. The latter will be set only after consent. <button type="button" data-cc="c-settings" class="cc-link">Let me choose</button>',
        primary_btn: {
          text: 'Accept all',
          role: 'accept_all',
        },
        secondary_btn: {
          text: 'Reject all',
          role: 'accept_necessary',
        },
      },
      settings_modal: {
        title: 'Cookie preferences',
        save_settings_btn: 'Save settings',
        accept_all_btn: 'Accept all',
        reject_all_btn: 'Reject all',
        close_btn_label: 'Close',
        cookie_table_headers: [{ col1: 'Name' }, { col2: 'Domain' }, { col3: 'Expiration' }, { col4: 'Description' }],
        blocks: [
          {
            title: 'Cookie usage 📢',
            description:
              'I use cookies to ensure the basic functionalities of the website and to enhance your online experience. You can choose for each category to opt-in/out whenever you want. For more details relative to cookies and other sensitive data, please read the full <a href="./privacy.html" class="cc-link">privacy policy</a>.',
          },
          {
            title: 'Strictly necessary cookies',
            description:
              'These cookies are essential for the proper functioning of my website. Without these cookies, the website would not work properly',
            toggle: {
              value: 'necessary',
              enabled: true,
              readonly: true,
            },
          },
        ],
      },
    },
  },
});
