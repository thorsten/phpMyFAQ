/**
 * phpMyFAQ admin backend code
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne
 * @copyright 2019-2026 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2019-12-20
 */

import { getLatestVersion, renderVisitorCharts, renderTopTenCharts, handleVerificationModal } from './dashboard';
import {
  handleClearRatings,
  handleClearVisits,
  handleCreateReport,
  handleDeleteAdminLog,
  handleDeleteSessions,
  handleExportAdminLog,
  handleSessions,
  handleSessionsFilter,
  handleStatistics,
  handleTruncateSearchTerms,
  handleVerifyAdminLog,
} from './statistics';
import {
  handleConfiguration,
  handleInstances,
  handleStopWords,
  handleElasticsearch,
  handleCheckForUpdates,
  handleSaveConfiguration,
  handleFormEdit,
  handleFormTranslations,
  handleOpenSearch,
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
  handleUpdateQuestion,
  handleRefreshAttachments,
  handleToggleVisibility,
  handleResetCategoryImage,
  handleResetButton,
  handleDeleteFaqEditorModal,
} from './content';
import { handleUserList, handleUsers } from './user';
import { handleGroups } from './group';
import { handlePasswordStrength, handlePasswordToggle } from '../../../assets/src/utils';
import { handleSessionTimeout, initializeTooltips, sidebarToggle } from './utils';
import '../../../assets/src/utils/theme-switcher';

document.addEventListener('DOMContentLoaded', async (): Promise<void> => {
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
  await handleVerificationModal();

  // User → User Management
  await handleUsers();
  handleUserList();

  // Group → Group Management
  await handleGroups();

  // Content → Categories
  handleCategories();
  handleResetCategoryImage();
  await handleCategoryDelete();

  // Content → add/edit FAQs
  renderEditor();
  handleFaqForm();
  handleMarkdownForm();
  handleAttachmentUploads();
  handleFileFilter();
  handleSaveFaqData();
  handleUpdateQuestion();
  handleDeleteFaqEditorModal();
  handleResetButton();
  await handleFaqOverview();

  // Content → Comments
  handleDeleteComments();

  // Content → Open questions
  handleOpenQuestions();
  handleToggleVisibility();

  // Content → Attachments
  handleDeleteAttachments();
  handleRefreshAttachments();

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
  handleExportAdminLog();
  await handleVerifyAdminLog();
  handleStatistics();
  handleCreateReport();
  handleTruncateSearchTerms();
  handleClearRatings();
  handleClearVisits();
  handleDeleteSessions();
  handleSessionsFilter();

  // Configuration → FAQ configuration
  await handleConfiguration();
  await handleSaveConfiguration();

  // Configuration → Instance
  handleInstances();

  // Configuration → Stop Words
  handleStopWords();

  // Configuration → Online Update
  handleCheckForUpdates();

  // Configuration → Elasticsearch / OpenSearch configuration
  await handleElasticsearch();
  await handleOpenSearch();

  // Import & Export → Import Records
  await handleUploadCSVForm();

  // Statistics → User-tracking
  handleSessions();

  handleFormEdit();
  handleFormTranslations();

  // News
  handleAddNews();
  handleNews();
  handleEditNews();

  // Initialize tooltips everywhere
  initializeTooltips();
});
