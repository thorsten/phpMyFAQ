/**
 * phpMyFAQ admin backend code
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne
 * @copyright 2019-2024 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2019-12-20
 */

import { getLatestVersion, renderVisitorCharts, renderTopTenCharts } from './dashboard';
import { handleCreateReport, handleDeleteAdminLog, handleSessions, handleStatistics } from './statistics';
import {
  handleConfiguration,
  handleInstances,
  handleStopWords,
  handleElasticsearch,
  handleCheckForUpdates,
  handleSaveConfiguration,
  handleFormEdit,
  handleFormTranslations,
} from './configuration';
import {
  handleAttachmentUploads,
  handleCategories,
  handleDeleteAttachments,
  handleDeleteComments,
  handleFaqForm,
  handleFaqOverview,
  handleMarkdownForm,
  handleFileFilter,
  handleOpenQuestions,
  handleTags,
  renderEditor,
  handleStickyFaqs,
  handleCategoryDelete,
  handleUploadCSVForm,
  handleDeleteGlossary,
  handleAddGlossary,
  onOpenUpdateGlossaryModal,
  handleUpdateGlossary,
  handleAddNews,
  handleNews,
  handleEditNews,
  handleSaveFaqData,
} from './content';
import { handleUserList, handleUsers } from './user';
import { handleGroups } from './group';
import { handlePasswordStrength, handlePasswordToggle } from '../../../assets/src/utils';
import { handleSessionTimeout, initializeTooltips, sidebarToggle } from './utils';
import { handleTruncateSearchTerms } from './statistics/search';

document.addEventListener('DOMContentLoaded', async () => {
  'use strict';

  // Session timeout
  handleSessionTimeout();

  // Login
  handlePasswordToggle();
  handlePasswordStrength();

  // Sidebar
  sidebarToggle();

  // Dashboard
  await renderVisitorCharts();
  await renderTopTenCharts();
  await getLatestVersion();

  // User → User Management
  await handleUsers();
  handleUserList();

  // Group → Group Management
  await handleGroups();

  // Content → Categories
  handleCategories();
  await handleCategoryDelete();

  // Content → add/edit FAQs
  renderEditor();
  handleFaqForm();
  handleMarkdownForm();
  handleAttachmentUploads();
  handleFileFilter();
  handleSaveFaqData();
  await handleFaqOverview();

  // Content → Comments
  handleDeleteComments();

  // Content → Open questions
  handleOpenQuestions();

  // Content → Attachments
  handleDeleteAttachments();

  // Content → Glossary
  handleDeleteGlossary();
  handleAddGlossary();
  onOpenUpdateGlossaryModal();
  handleUpdateGlossary();

  // Content → Tags
  handleTags();

  // Content → Sticky FAQs
  handleStickyFaqs();

  // Statistics
  handleDeleteAdminLog();
  handleStatistics();
  handleCreateReport();
  handleTruncateSearchTerms();

  // Configuration → FAQ configuration
  await handleConfiguration();
  await handleSaveConfiguration();

  // Configuration → Instance
  handleInstances();

  // Configuration → Stop Words
  handleStopWords();

  // Configuration → Online Update
  handleCheckForUpdates();

  // Configuration → Elasticsearch configuration
  await handleElasticsearch();

  // Import & Export → Import Records
  await handleUploadCSVForm();

  // Statistics → User-tracking
  await handleSessions();

  await handleFormEdit();
  await handleFormTranslations();

  // News
  await handleAddNews();
  await handleNews();
  await handleEditNews();

  // Initialize tooltips everywhere
  await initializeTooltips();
});
