/**
 * phpMyFAQ admin backend code
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package phpMyFAQ
 * @author Thorsten Rinne
 * @copyright 2019-2022 phpMyFAQ Team
 * @license http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link https://www.phpmyfaq.de
 * @since 2019-12-20
 */

import { Tab } from 'bootstrap';
import { renderVisitorCharts } from './dashboard';
import { sidebarToggle } from './sidebar';
import { fetchConfiguration } from './configuration';
import { handleInstances } from './instance';

document.addEventListener('DOMContentLoaded', () => {
  'use strict';

  //
  // Configuration
  //
  const configTabList = [].slice.call(document.querySelectorAll('#configuration-list a'));
  if (configTabList.length) {
    let tabLoaded = false;
    configTabList.forEach((element) => {
      const configTabTrigger = new Tab(element);
      element.addEventListener('shown.bs.tab', (event) => {
        event.preventDefault();
        let target = event.target.getAttribute('href');
        fetchConfiguration(target);
        tabLoaded = true;
        configTabTrigger.show();
      });
    });

    if (!tabLoaded) {
      fetchConfiguration('#main');
    }
  }

  //
  // Dashboard
  //
  renderVisitorCharts();

  //
  // Instance
  //
  handleInstances();

  //
  // Sidebar
  //
  sidebarToggle();

  //
  // User
  //

  //
  // FAQs
  //
  // editor.render();
});
