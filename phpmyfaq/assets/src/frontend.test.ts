import { describe, it, expect, vi, beforeEach } from 'vitest';

// Mock all imported modules
const mockHandleReloadCaptcha = vi.fn();
const mockHandlePasswordToggle = vi.fn();
const mockHandlePasswordStrength = vi.fn();
const mockCalculateReadingTime = vi.fn();

vi.mock('./utils', () => ({
  handleReloadCaptcha: (...args: unknown[]) => mockHandleReloadCaptcha(...args),
  handlePasswordToggle: () => mockHandlePasswordToggle(),
  handlePasswordStrength: () => mockHandlePasswordStrength(),
  calculateReadingTime: () => mockCalculateReadingTime(),
}));

vi.mock('./utils/tooltip', () => ({}));

const mockHandleContactForm = vi.fn();
vi.mock('./contact', () => ({
  handleContactForm: () => mockHandleContactForm(),
}));

const mockHandleAddFaq = vi.fn();
const mockHandleComments = vi.fn();
const mockHandleSaveComment = vi.fn();
const mockHandleShareLinkButton = vi.fn();
const mockHandleShowFaq = vi.fn();
const mockHandleSidebarToggle = vi.fn();
const mockHandleUserVoting = vi.fn();
const mockRenderFaqEditor = vi.fn();

vi.mock('./faq', () => ({
  handleAddFaq: () => mockHandleAddFaq(),
  handleComments: () => mockHandleComments(),
  handleSaveComment: () => mockHandleSaveComment(),
  handleShareLinkButton: () => mockHandleShareLinkButton(),
  handleShowFaq: () => mockHandleShowFaq(),
  handleSidebarToggle: () => mockHandleSidebarToggle(),
  handleUserVoting: () => mockHandleUserVoting(),
  renderFaqEditor: () => mockRenderFaqEditor(),
}));

const mockHandleAutoComplete = vi.fn();
const mockHandleCategorySelection = vi.fn();
const mockHandleQuestion = vi.fn();

vi.mock('./search', () => ({
  handleAutoComplete: () => mockHandleAutoComplete(),
  handleCategorySelection: () => mockHandleCategorySelection(),
  handleQuestion: () => mockHandleQuestion(),
}));

const mockHandleDeleteBookmarks = vi.fn();
const mockHandleRegister = vi.fn();
const mockHandleRemoveAllBookmarks = vi.fn();
const mockHandleRequestRemoval = vi.fn();
const mockHandleUserControlPanel = vi.fn();
const mockHandleUserPassword = vi.fn();

vi.mock('./user', () => ({
  handleDeleteBookmarks: () => mockHandleDeleteBookmarks(),
  handleRegister: () => mockHandleRegister(),
  handleRemoveAllBookmarks: () => mockHandleRemoveAllBookmarks(),
  handleRequestRemoval: () => mockHandleRequestRemoval(),
  handleUserControlPanel: () => mockHandleUserControlPanel(),
  handleUserPassword: () => mockHandleUserPassword(),
}));

const mockHandleWebAuthn = vi.fn();
vi.mock('./webauthn/webauthn', () => ({
  handleWebAuthn: () => mockHandleWebAuthn(),
}));

const mockHandleChat = vi.fn();
vi.mock('./chat', () => ({
  handleChat: () => mockHandleChat(),
}));

const mockHandlePushNotifications = vi.fn();
vi.mock('./push', () => ({
  handlePushNotifications: () => mockHandlePushNotifications(),
}));

const mockMasonry = vi.fn();
vi.mock('masonry-layout', () => ({
  default: mockMasonry,
}));

describe('frontend.ts', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    vi.resetModules();
    document.body.innerHTML = '';
  });

  const loadAndFireDOMContentLoaded = async (): Promise<void> => {
    await import('./frontend');
    document.dispatchEvent(new Event('DOMContentLoaded'));
  };

  it('should call all unconditional handlers on DOMContentLoaded', async () => {
    await loadAndFireDOMContentLoaded();

    expect(mockHandlePasswordToggle).toHaveBeenCalled();
    expect(mockHandlePasswordStrength).toHaveBeenCalled();
    expect(mockHandleUserVoting).toHaveBeenCalled();
    expect(mockHandleSaveComment).toHaveBeenCalled();
    expect(mockHandleComments).toHaveBeenCalled();
    expect(mockHandleAddFaq).toHaveBeenCalled();
    expect(mockHandleShowFaq).toHaveBeenCalled();
    expect(mockHandleShareLinkButton).toHaveBeenCalled();
    expect(mockHandleSidebarToggle).toHaveBeenCalled();
    expect(mockHandleQuestion).toHaveBeenCalled();
    expect(mockHandleDeleteBookmarks).toHaveBeenCalled();
    expect(mockHandleRemoveAllBookmarks).toHaveBeenCalled();
    expect(mockHandleUserControlPanel).toHaveBeenCalled();
    expect(mockHandleUserPassword).toHaveBeenCalled();
    expect(mockHandleRequestRemoval).toHaveBeenCalled();
    expect(mockHandleContactForm).toHaveBeenCalled();
    expect(mockHandleRegister).toHaveBeenCalled();
    expect(mockHandleWebAuthn).toHaveBeenCalled();
    expect(mockHandleAutoComplete).toHaveBeenCalled();
    expect(mockHandleCategorySelection).toHaveBeenCalled();
    expect(mockHandleChat).toHaveBeenCalled();
    expect(mockHandlePushNotifications).toHaveBeenCalled();
  });

  it('should call handleReloadCaptcha when captcha button exists', async () => {
    document.body.innerHTML = '<button id="captcha-button">Reload</button>';

    await loadAndFireDOMContentLoaded();

    expect(mockHandleReloadCaptcha).toHaveBeenCalledWith(document.getElementById('captcha-button'));
  });

  it('should not call handleReloadCaptcha when captcha button is missing', async () => {
    await loadAndFireDOMContentLoaded();

    expect(mockHandleReloadCaptcha).not.toHaveBeenCalled();
  });

  it('should call calculateReadingTime when FAQ body element exists', async () => {
    document.body.innerHTML = '<div class="pmf-faq-body">Some FAQ content</div>';

    await loadAndFireDOMContentLoaded();

    expect(mockCalculateReadingTime).toHaveBeenCalled();
  });

  it('should not call calculateReadingTime when FAQ body is missing', async () => {
    await loadAndFireDOMContentLoaded();

    expect(mockCalculateReadingTime).not.toHaveBeenCalled();
  });

  it('should call renderFaqEditor when add FAQ form has wysiwyg enabled', async () => {
    document.body.innerHTML = '<form id="pmf-add-faq-form" data-wysiwyg-enabled="true"></form>';

    await loadAndFireDOMContentLoaded();

    expect(mockRenderFaqEditor).toHaveBeenCalled();
  });

  it('should not call renderFaqEditor when add FAQ form is missing', async () => {
    await loadAndFireDOMContentLoaded();

    expect(mockRenderFaqEditor).not.toHaveBeenCalled();
  });

  it('should not call renderFaqEditor when wysiwyg is not enabled', async () => {
    document.body.innerHTML = '<form id="pmf-add-faq-form"></form>';

    await loadAndFireDOMContentLoaded();

    expect(mockRenderFaqEditor).not.toHaveBeenCalled();
  });

  it('should not call renderFaqEditor when wysiwyg is set to false', async () => {
    document.body.innerHTML = '<form id="pmf-add-faq-form" data-wysiwyg-enabled="false"></form>';

    await loadAndFireDOMContentLoaded();

    expect(mockRenderFaqEditor).not.toHaveBeenCalled();
  });

  it('should initialize Masonry when masonry-grid element exists', async () => {
    document.body.innerHTML = '<div class="masonry-grid"></div>';

    await loadAndFireDOMContentLoaded();

    expect(mockMasonry).toHaveBeenCalledWith(document.querySelector('.masonry-grid'), { columnWidth: 0 });
  });

  it('should not initialize Masonry when masonry-grid is missing', async () => {
    await loadAndFireDOMContentLoaded();

    expect(mockMasonry).not.toHaveBeenCalled();
  });
});
