/**
 * phpMyFAQ admin backend code
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne
 * @copyright 2019-2023 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2019-12-20
 */

import { renderVisitorCharts } from './dashboard';
import { sidebarToggle } from './sidebar';
import {
  handleConfiguration,
  handleInstances,
  handleStopWords,
  handleTemplateMetaData,
  handleElasticsearch,
} from './configuration';
import { handleStatistics } from './statistics';
import {
  handleAttachmentUploads,
  handleCategories,
  handleDeleteAttachments,
  handleDeleteComments,
  handleFaqForm,
  handleFaqOverview,
  handleMarkdownForm,
  handleOpenQuestions,
  handleTags,
  renderEditor,
} from './content';
import { handleUserList, handleUsers } from './user';
import { handleGroups } from './group';
import { handlePasswordStrength, handlePasswordToggle } from '../../../assets/src/utils';

document.addEventListener('DOMContentLoaded', async () => {
  'use strict';

  // Login
  handlePasswordToggle();
  handlePasswordStrength();

  // Sidebar
  sidebarToggle();

  // Dashboard
  renderVisitorCharts();

  // User -> User Management
  await handleUsers();
  handleUserList();

  // Group -> Group Management
  await handleGroups();

  // Content -> Categories
  handleCategories();

  // Content -> add/edit FAQs
  renderEditor();
  handleFaqForm();
  handleMarkdownForm();
  handleAttachmentUploads();
  await handleFaqOverview();

  // Content -> Comments
  handleDeleteComments();

  // Content -> Open questions
  handleOpenQuestions();

  // Content -> Attachments
  handleDeleteAttachments();

  // Content -> Tags
  handleTags();

  // Statistics
  handleStatistics();

  // Configuration -> FAQ configuration
  handleConfiguration();

  // Configuration -> Instance
  handleInstances();

  // Configuration -> Stop Words
  handleStopWords();

  // Configuration -> Template Meta data
  handleTemplateMetaData();

  // Configuration -> Elasticsearch configuration
  handleElasticsearch();
});
