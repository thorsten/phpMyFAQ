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

import {
  fetchContentHealth,
  fetchPopularSearches,
  fetchRecentNews,
  getLatestVersion,
  renderVisitorCharts,
  renderTopTenCharts,
  handleVerificationModal,
} from './dashboard';
import { handleDashboardLayout } from './dashboard-layout';
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
  handleLdap,
  handleSaveConfiguration,
  handleFormEdit,
  handleFormTranslations,
  handleOpenSearch,
} from './configuration';
import {
  handleAttachmentUploads,
  handleCategories,
  handleCategoryOverview,
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
  handleAddPage,
  handlePages,
  handleEditPage,
  handleTranslatePage,
  handleSaveFaqData,
  handleUpdateQuestion,
  handleRefreshAttachments,
  handleToggleVisibility,
  handleResetCategoryImage,
  handleResetButton,
  handleDeleteFaqEditorModal,
  handleFaqTranslate,
  handleCategoryTranslate,
  handleFleschReadingEase,
  handleDeleteFaqModal,
} from './content';
import { handleUserList, handleUsers } from './user';
import { handleGroups } from './group';
import { handleLoginForm, handlePasswordStrength, handlePasswordToggle } from '../../../assets/src/utils';
import { handleSessionTimeout, initializeTooltips, sidebarToggle } from './utils';
import '../../../assets/src/utils/theme-switcher';

document.addEventListener('DOMContentLoaded', async (): Promise<void> => {
  'use strict';

  // Session timeout
  handleSessionTimeout();

  // Login
  handlePasswordToggle();
  handlePasswordStrength();
  handleLoginForm();

  // Sidebar
  sidebarToggle();

  // Dashboard — run concurrently; allSettled so one failed remote call cannot block the rest
  const dashboardTasks = [
    'renderVisitorCharts',
    'renderTopTenCharts',
    'getLatestVersion',
    'handleVerificationModal',
    'fetchRecentNews',
    'fetchContentHealth',
    'fetchPopularSearches',
    'handleDashboardLayout',
  ];
  const dashboardResults = await Promise.allSettled([
    renderVisitorCharts(),
    renderTopTenCharts(),
    getLatestVersion(),
    handleVerificationModal(),
    fetchRecentNews(),
    fetchContentHealth(),
    fetchPopularSearches(),
    handleDashboardLayout(),
  ]);
  // Surface failures without blocking — each task is independent
  dashboardResults.forEach((result, index) => {
    if (result.status === 'rejected') {
      console.error('Dashboard init task failed: ', dashboardTasks[index], result.reason);
    }
  });

  // User → User Management
  await handleUsers();
  handleUserList();

  // Group → Group Management
  await handleGroups();

  // Content → Categories
  handleCategories();
  handleCategoryOverview();
  handleResetCategoryImage();
  handleCategoryTranslate();
  await handleCategoryDelete();

  // Content → add/edit FAQs
  renderEditor();
  handleFaqForm();
  handleFaqTranslate();
  handleMarkdownForm();
  handleAttachmentUploads();
  handleFileFilter();
  handleSaveFaqData();
  handleUpdateQuestion();
  handleDeleteFaqEditorModal();
  handleResetButton();
  handleFleschReadingEase();
  await handleFaqOverview();
  handleDeleteFaqModal();

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

  // Configuration → Elasticsearch / OpenSearch / LDAP configuration
  await handleElasticsearch();
  await handleLdap();
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

  // Custom Pages
  handleAddPage();
  handlePages();
  handleEditPage();
  handleTranslatePage();

  // Initialize tooltips everywhere
  initializeTooltips();
});
