import { describe, it, expect, vi, beforeEach } from 'vitest';

// Track all handler calls
const calls: string[] = [];
const trackCall = (name: string) => {
  calls.push(name);
};

// Mock dashboard
vi.mock('./dashboard', () => ({
  fetchRecentNews: vi.fn(async () => trackCall('fetchRecentNews')),
  getLatestVersion: vi.fn(async () => trackCall('getLatestVersion')),
  renderVisitorCharts: vi.fn(async () => trackCall('renderVisitorCharts')),
  renderTopTenCharts: vi.fn(async () => trackCall('renderTopTenCharts')),
  handleVerificationModal: vi.fn(async () => trackCall('handleVerificationModal')),
}));

// Mock statistics
vi.mock('./statistics', () => ({
  handleClearRatings: vi.fn(() => trackCall('handleClearRatings')),
  handleClearVisits: vi.fn(() => trackCall('handleClearVisits')),
  handleCreateReport: vi.fn(() => trackCall('handleCreateReport')),
  handleDeleteAdminLog: vi.fn(() => trackCall('handleDeleteAdminLog')),
  handleDeleteSessions: vi.fn(() => trackCall('handleDeleteSessions')),
  handleExportAdminLog: vi.fn(() => trackCall('handleExportAdminLog')),
  handleSessions: vi.fn(() => trackCall('handleSessions')),
  handleSessionsFilter: vi.fn(() => trackCall('handleSessionsFilter')),
  handleStatistics: vi.fn(() => trackCall('handleStatistics')),
  handleTruncateSearchTerms: vi.fn(() => trackCall('handleTruncateSearchTerms')),
  handleVerifyAdminLog: vi.fn(async () => trackCall('handleVerifyAdminLog')),
}));

// Mock configuration
vi.mock('./configuration', () => ({
  handleConfiguration: vi.fn(async () => trackCall('handleConfiguration')),
  handleInstances: vi.fn(() => trackCall('handleInstances')),
  handleStopWords: vi.fn(() => trackCall('handleStopWords')),
  handleElasticsearch: vi.fn(async () => trackCall('handleElasticsearch')),
  handleCheckForUpdates: vi.fn(() => trackCall('handleCheckForUpdates')),
  handleLdap: vi.fn(async () => trackCall('handleLdap')),
  handleSaveConfiguration: vi.fn(async () => trackCall('handleSaveConfiguration')),
  handleFormEdit: vi.fn(() => trackCall('handleFormEdit')),
  handleFormTranslations: vi.fn(() => trackCall('handleFormTranslations')),
  handleOpenSearch: vi.fn(async () => trackCall('handleOpenSearch')),
}));

// Mock content
vi.mock('./content', () => ({
  handleAttachmentUploads: vi.fn(() => trackCall('handleAttachmentUploads')),
  handleCategories: vi.fn(() => trackCall('handleCategories')),
  handleDeleteAttachments: vi.fn(() => trackCall('handleDeleteAttachments')),
  handleDeleteComments: vi.fn(() => trackCall('handleDeleteComments')),
  handleFaqForm: vi.fn(() => trackCall('handleFaqForm')),
  handleFaqOverview: vi.fn(async () => trackCall('handleFaqOverview')),
  handleMarkdownForm: vi.fn(() => trackCall('handleMarkdownForm')),
  handleFileFilter: vi.fn(() => trackCall('handleFileFilter')),
  handleOpenQuestions: vi.fn(() => trackCall('handleOpenQuestions')),
  handleTags: vi.fn(() => trackCall('handleTags')),
  renderEditor: vi.fn(() => trackCall('renderEditor')),
  handleStickyFaqs: vi.fn(() => trackCall('handleStickyFaqs')),
  handleCategoryDelete: vi.fn(async () => trackCall('handleCategoryDelete')),
  handleUploadCSVForm: vi.fn(async () => trackCall('handleUploadCSVForm')),
  handleDeleteGlossary: vi.fn(() => trackCall('handleDeleteGlossary')),
  handleAddGlossary: vi.fn(() => trackCall('handleAddGlossary')),
  onOpenUpdateGlossaryModal: vi.fn(() => trackCall('onOpenUpdateGlossaryModal')),
  handleUpdateGlossary: vi.fn(() => trackCall('handleUpdateGlossary')),
  handleAddNews: vi.fn(() => trackCall('handleAddNews')),
  handleNews: vi.fn(() => trackCall('handleNews')),
  handleEditNews: vi.fn(() => trackCall('handleEditNews')),
  handleAddPage: vi.fn(() => trackCall('handleAddPage')),
  handlePages: vi.fn(() => trackCall('handlePages')),
  handleEditPage: vi.fn(() => trackCall('handleEditPage')),
  handleTranslatePage: vi.fn(() => trackCall('handleTranslatePage')),
  handleSaveFaqData: vi.fn(() => trackCall('handleSaveFaqData')),
  handleUpdateQuestion: vi.fn(() => trackCall('handleUpdateQuestion')),
  handleRefreshAttachments: vi.fn(() => trackCall('handleRefreshAttachments')),
  handleToggleVisibility: vi.fn(() => trackCall('handleToggleVisibility')),
  handleResetCategoryImage: vi.fn(() => trackCall('handleResetCategoryImage')),
  handleResetButton: vi.fn(() => trackCall('handleResetButton')),
  handleDeleteFaqEditorModal: vi.fn(() => trackCall('handleDeleteFaqEditorModal')),
  handleFaqTranslate: vi.fn(() => trackCall('handleFaqTranslate')),
  handleCategoryTranslate: vi.fn(() => trackCall('handleCategoryTranslate')),
  handleFleschReadingEase: vi.fn(() => trackCall('handleFleschReadingEase')),
  handleDeleteFaqModal: vi.fn(() => trackCall('handleDeleteFaqModal')),
}));

// Mock user
vi.mock('./user', () => ({
  handleUserList: vi.fn(() => trackCall('handleUserList')),
  handleUsers: vi.fn(async () => trackCall('handleUsers')),
}));

// Mock group
vi.mock('./group', () => ({
  handleGroups: vi.fn(async () => trackCall('handleGroups')),
}));

// Mock shared utils
vi.mock('../../../assets/src/utils', () => ({
  handlePasswordStrength: vi.fn(() => trackCall('handlePasswordStrength')),
  handlePasswordToggle: vi.fn(() => trackCall('handlePasswordToggle')),
}));

// Mock admin utils
vi.mock('./utils', () => ({
  handleSessionTimeout: vi.fn(() => trackCall('handleSessionTimeout')),
  initializeTooltips: vi.fn(() => trackCall('initializeTooltips')),
  sidebarToggle: vi.fn(() => trackCall('sidebarToggle')),
}));

// Mock theme switcher (side-effect import)
vi.mock('../../../assets/src/utils/theme-switcher', () => ({}));

describe('admin index.ts', () => {
  beforeEach(() => {
    calls.length = 0;
    vi.clearAllMocks();
  });

  it('should call all handlers on DOMContentLoaded', async () => {
    await import('./index');
    document.dispatchEvent(new Event('DOMContentLoaded'));

    await vi.waitFor(() => {
      // Verify the last handler in the chain was called
      expect(calls).toContain('initializeTooltips');
    });

    // Session & login
    expect(calls).toContain('handleSessionTimeout');
    expect(calls).toContain('handlePasswordToggle');
    expect(calls).toContain('handlePasswordStrength');
    expect(calls).toContain('sidebarToggle');

    // Dashboard
    expect(calls).toContain('renderVisitorCharts');
    expect(calls).toContain('renderTopTenCharts');
    expect(calls).toContain('getLatestVersion');
    expect(calls).toContain('handleVerificationModal');
    expect(calls).toContain('fetchRecentNews');

    // Users & Groups
    expect(calls).toContain('handleUsers');
    expect(calls).toContain('handleUserList');
    expect(calls).toContain('handleGroups');

    // Categories
    expect(calls).toContain('handleCategories');
    expect(calls).toContain('handleResetCategoryImage');
    expect(calls).toContain('handleCategoryTranslate');
    expect(calls).toContain('handleCategoryDelete');

    // FAQs
    expect(calls).toContain('renderEditor');
    expect(calls).toContain('handleFaqForm');
    expect(calls).toContain('handleFaqTranslate');
    expect(calls).toContain('handleMarkdownForm');
    expect(calls).toContain('handleAttachmentUploads');
    expect(calls).toContain('handleFileFilter');
    expect(calls).toContain('handleSaveFaqData');
    expect(calls).toContain('handleUpdateQuestion');
    expect(calls).toContain('handleDeleteFaqEditorModal');
    expect(calls).toContain('handleResetButton');
    expect(calls).toContain('handleFleschReadingEase');
    expect(calls).toContain('handleFaqOverview');
    expect(calls).toContain('handleDeleteFaqModal');

    // Comments & Questions
    expect(calls).toContain('handleDeleteComments');
    expect(calls).toContain('handleOpenQuestions');
    expect(calls).toContain('handleToggleVisibility');

    // Attachments
    expect(calls).toContain('handleDeleteAttachments');
    expect(calls).toContain('handleRefreshAttachments');

    // Glossary
    expect(calls).toContain('handleDeleteGlossary');
    expect(calls).toContain('handleAddGlossary');
    expect(calls).toContain('onOpenUpdateGlossaryModal');
    expect(calls).toContain('handleUpdateGlossary');

    // Tags & Sticky
    expect(calls).toContain('handleTags');
    expect(calls).toContain('handleStickyFaqs');

    // Statistics
    expect(calls).toContain('handleDeleteAdminLog');
    expect(calls).toContain('handleExportAdminLog');
    expect(calls).toContain('handleVerifyAdminLog');
    expect(calls).toContain('handleStatistics');
    expect(calls).toContain('handleCreateReport');
    expect(calls).toContain('handleTruncateSearchTerms');
    expect(calls).toContain('handleClearRatings');
    expect(calls).toContain('handleClearVisits');
    expect(calls).toContain('handleDeleteSessions');
    expect(calls).toContain('handleSessionsFilter');
    expect(calls).toContain('handleSessions');

    // Configuration
    expect(calls).toContain('handleConfiguration');
    expect(calls).toContain('handleSaveConfiguration');
    expect(calls).toContain('handleInstances');
    expect(calls).toContain('handleStopWords');
    expect(calls).toContain('handleCheckForUpdates');
    expect(calls).toContain('handleElasticsearch');
    expect(calls).toContain('handleLdap');
    expect(calls).toContain('handleOpenSearch');

    // Import
    expect(calls).toContain('handleUploadCSVForm');

    // Forms
    expect(calls).toContain('handleFormEdit');
    expect(calls).toContain('handleFormTranslations');

    // News
    expect(calls).toContain('handleAddNews');
    expect(calls).toContain('handleNews');
    expect(calls).toContain('handleEditNews');

    // Pages
    expect(calls).toContain('handleAddPage');
    expect(calls).toContain('handlePages');
    expect(calls).toContain('handleEditPage');
    expect(calls).toContain('handleTranslatePage');

    // Tooltips
    expect(calls).toContain('initializeTooltips');
  });

  it('should call handlers in correct initialization order', async () => {
    await import('./index');
    document.dispatchEvent(new Event('DOMContentLoaded'));

    await vi.waitFor(() => {
      expect(calls).toContain('initializeTooltips');
    });

    // Session/login must come before dashboard
    expect(calls.indexOf('handleSessionTimeout')).toBeLessThan(calls.indexOf('renderVisitorCharts'));
    expect(calls.indexOf('handlePasswordToggle')).toBeLessThan(calls.indexOf('renderVisitorCharts'));
    expect(calls.indexOf('sidebarToggle')).toBeLessThan(calls.indexOf('renderVisitorCharts'));

    // Dashboard before users
    expect(calls.indexOf('fetchRecentNews')).toBeLessThan(calls.indexOf('handleUsers'));

    // Users before groups
    expect(calls.indexOf('handleUsers')).toBeLessThan(calls.indexOf('handleGroups'));

    // Tooltips last
    expect(calls.indexOf('initializeTooltips')).toBe(calls.length - 1);
  });
});
