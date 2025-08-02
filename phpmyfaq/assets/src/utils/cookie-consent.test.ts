import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import * as cc from 'vanilla-cookieconsent';

vi.mock('vanilla-cookieconsent', () => ({
  run: vi.fn(),
  showPreferences: vi.fn(),
}));

describe('cookie-consent', () => {
  const mockRun = vi.mocked(cc.run);
  const mockShowPreferences = vi.mocked(cc.showPreferences);

  beforeEach(() => {
    vi.clearAllMocks();
    vi.resetModules();
  });

  afterEach(() => {
    document.body.innerHTML = '';
  });

  it('should initialize cookie consent with correct configuration', async (): Promise<void> => {
    await import('./cookie-consent');

    expect(mockRun).toHaveBeenCalledTimes(1);
    expect(mockRun).toHaveBeenCalledWith({
      autoShow: true,
      mode: 'opt-in',
      cookie: {
        name: 'phpmyfaq_cc_cookie',
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
      onFirstConsent: expect.any(Function),
      onConsent: expect.any(Function),
      onChange: expect.any(Function),
      onModalReady: expect.any(Function),
      onModalShow: expect.any(Function),
      onModalHide: expect.any(Function),
      categories: {
        necessary: {
          enabled: true,
          readOnly: true,
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
  });

  it('should add click event listener to showCookieConsent element', async (): Promise<void> => {
    document.body.innerHTML = '<button id="showCookieConsent">Show Cookie Settings</button>';

    const element = document.getElementById('showCookieConsent');
    const addEventListenerSpy = vi.spyOn(element!, 'addEventListener');

    await import('./cookie-consent');

    expect(addEventListenerSpy).toHaveBeenCalledWith('click', expect.any(Function));
  });

  it('should call showPreferences when showCookieConsent is clicked', async (): Promise<void> => {
    document.body.innerHTML = '<button id="showCookieConsent">Show Cookie Settings</button>';

    await import('./cookie-consent');

    const element = document.getElementById('showCookieConsent');
    element!.click();

    expect(mockShowPreferences).toHaveBeenCalledTimes(1);
  });

  it('should not add event listener when showCookieConsent element does not exist', async (): Promise<void> => {
    document.body.innerHTML = '<div>No cookie consent button</div>';

    await import('./cookie-consent');

    expect(mockShowPreferences).not.toHaveBeenCalled();
  });
});
