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

import { renderVisitorCharts } from './dashboard';
import { sidebarToggle } from './sidebar';
import { handleConfiguration } from './configuration';
import { handleInstances } from './instance';
import { handleStopWords } from './stopwords';
import { handleTemplateMetaData } from './template-meta-data';

document.addEventListener('DOMContentLoaded', () => {
  'use strict';

  // Configuration
  handleConfiguration();

  // Dashboard
  renderVisitorCharts();

  // Instance
  handleInstances();

  // Sidebar
  sidebarToggle();

  // Stop Words
  handleStopWords();

  // Template Meta data
  handleTemplateMetaData();

  //
  // User
  //

  //
  // FAQs
  //
  // editor.render();
});
