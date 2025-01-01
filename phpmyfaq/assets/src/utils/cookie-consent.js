/**
 * phpMyFAQ cookie consent
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne
 * @copyright 2023-2025 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2023-01-21
 */

import * as cc from 'vanilla-cookieconsent';

cc.run({
  // root: 'body',
  autoShow: true,
  // disablePageInteraction: true,
  // hideFromBots: true,
  mode: 'opt-in',
  // revision: 0,

  cookie: {
    name: 'phpmyfaq_cc_cookie',
    // domain: location.hostname,
    // path: '/',
    // sameSite: "Lax",
    expiresAfterDays: 182,
  },
  guiOptions: {
    consentModal: {
      layout: 'box inline',
      position: 'top center',
      equalWeightButtons: true,
      flipButtons: false,
    },
    preferencesModal: {
      layout: 'box',
      equalWeightButtons: true,
      flipButtons: false,
    },
  },

  onFirstConsent: ({ cookie }) => {},

  onConsent: ({ cookie }) => {},

  onChange: ({ changedCategories, changedServices }) => {},

  onModalReady: ({ modalName }) => {},

  onModalShow: ({ modalName }) => {},

  onModalHide: ({ modalName }) => {},

  categories: {
    necessary: {
      enabled: true, // this category is enabled by default
      readOnly: true, // this category cannot be disabled
    },
  },

  language: {
    default: 'en',
    autoDetect: 'document',
    translations: {
      de: './translations/cookie-consent/de.json',
      en: './translations/cookie-consent/en.json',
      pl: './translations/cookie-consent/pl.json',
    },
  },
});

const cookiePreferences = document.getElementById('showCookieConsent');
if (cookiePreferences) {
  cookiePreferences.addEventListener('click', (event) => {
    event.preventDefault();
    cc.showPreferences();
  });
}
