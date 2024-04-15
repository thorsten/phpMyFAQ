/**
 * phpMyFAQ cookie consent
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne
 * @copyright 2023-2024 phpMyFAQ Team
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

  // https://cookieconsent.orestbida.com/reference/configuration-reference.html#guioptions
  guiOptions: {
    consentModal: {
      layout: 'cloud inline',
      position: 'bottom right',
      equalWeightButtons: true,
      flipButtons: false
    },
    preferencesModal: {
      layout: 'box',
      equalWeightButtons: true,
      flipButtons: false
    }
  },

  onFirstConsent: ({cookie}) => {
    console.log('onFirstConsent fired',cookie);
  },

  onConsent: ({cookie}) => {
    console.log('onConsent fired!', cookie)
  },

  onChange: ({changedCategories, changedServices}) => {
    console.log('onChange fired!', changedCategories, changedServices);
  },

  onModalReady: ({modalName}) => {
    console.log('ready:', modalName);
  },

  onModalShow: ({modalName}) => {
    console.log('visible:', modalName);
  },

  onModalHide: ({modalName}) => {
    console.log('hidden:', modalName);
  },

  categories: {
    necessary: {
      enabled: true,  // this category is enabled by default
      readOnly: true  // this category cannot be disabled
    },
    analytics: {
      autoClear: {
        cookies: [
          {
            name: /^_ga/,   // regex: match all cookies starting with '_ga'
          },
          {
            name: '_gid',   // string: exact cookie name
          }
        ]
      },

      services: {
        ga: {
          label: 'Google Analytics',
          onAccept: () => {},
          onReject: () => {}
        },
        youtube: {
          label: 'Youtube Embed',
          onAccept: () => {},
          onReject: () => {}
        },
      }
    },
    ads: {}
  },

  language: {
    default: 'en',
    autoDetect: 'browser',
    translations: {
      en: {
        consentModal: {
          title: 'phpMyFAQ use cookies!',
          description:
            'Hi, phpMyFAQ uses essential cookies to ensure its proper operation and tracking cookies to understand how you interact with it. The latter will be set only after consent.',
          acceptAllBtn: 'Accept all',
          acceptNecessaryBtn: 'Reject all',
          showPreferencesBtn: 'Let me choose',
          footer: `
                        <a href="contact.html" target="_blank">Impressum</a>
                        <a href="privacy.html" target="_blank">Privacy Policy</a>
                    `,
        },
        preferencesModal: {
          title: 'Cookie preferences',
          acceptAllBtn: 'Accept all',
          savePreferencesBtn: 'Reject all',
          closeIconLabel: 'Close',
          cookie_table_headers: [{ col1: 'Name' }, { col2: 'Domain' }, { col3: 'Expiration' }, { col4: 'Description' }],
          sections: [
            {
              title: 'Cookie usage 📢',
              description:
                'I use cookies to ensure the basic functionalities of the website and to enhance your online experience. You can choose for each category to opt-in/out whenever you want. For more details relative to cookies and other sensitive data, please read the full <a href="./privacy.html" class="cc-link">privacy policy</a>.',
            },
            {
              title: 'Strictly necessary cookies',
              description:
                'These cookies are essential for the proper functioning of my website. Without these cookies, the website would not work properly',
              linkedCategory: 'necessary'
            },
          ],
        },
      },
      de: {
        consentModal: {
          title: 'phpMyFAQ nutzt Cookies!',
          description:
            'Hallo, phpMyFAQ nutzt technisch notwendige und funktionale Cookies, um den Betrieb zu gewährleisten und Marketing-Cookies, um den Erfolg unserer Seite messen zu können. Diese werden erst nach Zustimmung gesetzt.',
          acceptAllBtn: 'Alle akzeptieren',
          acceptNecessaryBtn: 'Alle ablehnen',
          showPreferencesBtn: 'Anpassen',
          footer: `
                        <a href="contact.html" target="_blank">Impressum</a>
                        <a href="privacy.html" target="_blank">Datenschutzerklärung</a>
                    `,
        },
        preferencesModal: {
          title: 'Cookie-Einstellungen',
          acceptAllBtn: 'Alle akzeptieren',
          savePreferencesBtn: 'Alle ablehnen',
          closeIconLabel: 'Schließen',
          sections: [
            {
              title: 'Cookie-Einstellungen 📢',
              description:
                'Ich nutze Cookies, um den ordnungsgemäßen Betrieb der Seite zu garantieren und deine Benutzererfahrung zu verbessern. Du kannst die Verwendung von Cookies für jede Kategorie aktivieren und deaktivieren. Weitere Informationen finden Sie in unserer <a href="./privacy.html" class="cc-link">Datenschutzerklärung</a>.',
            },
            {
              title: 'Technisch notwendige und funktionale Cookies',
              description:
                'Diese Cookies sind für den reibungslosen Betrieb der Website unbedingt erforderlich. Ohne diese würde die Website nicht ordnungsgemäß funktionieren.',
              linkedCategory: 'necessary'
            },
          ],
        },
      },
    },
  }
});

const cookiePreferences = document.getElementById('showCookieConsent');
if(cookiePreferences) {
  cookiePreferences.addEventListener('click', function() {
    cc.showPreferences();
  });
}
