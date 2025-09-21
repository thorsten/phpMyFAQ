import { describe, expect, test, vi, beforeEach, afterEach } from 'vitest';
import { ThemeSwitcher } from './theme-switcher';

// Mock DOM elements
const mockToggleButton = {
  addEventListener: vi.fn(),
  setAttribute: vi.fn(),
} as unknown as HTMLButtonElement;

const mockLightIcon = {
  style: { display: '' },
} as HTMLElement;

const mockDarkIcon = {
  style: { display: '' },
} as HTMLElement;

// Mock localStorage properly
const mockLocalStorage = {
  getItem: vi.fn().mockReturnValue(null),
  setItem: vi.fn(),
  removeItem: vi.fn(),
  clear: vi.fn(),
  key: vi.fn(),
  length: 0,
} as Storage;

// Mock matchMedia
const mockMatchMedia = {
  matches: false,
  addEventListener: vi.fn(),
  removeEventListener: vi.fn(),
  media: '',
  onchange: null,
  addListener: vi.fn(),
  removeListener: vi.fn(),
  dispatchEvent: vi.fn(),
};

describe('ThemeSwitcher', (): void => {
  let themeSwitcher: ThemeSwitcher;
  let originalDocument: Document;
  let originalWindow: Window & typeof globalThis;
  let originalLocalStorage: Storage;
  let mockDocument: any;
  let mockWindow: any;

  beforeEach((): void => {
    // Store original globals
    originalDocument = global.document;
    originalWindow = global.window;
    originalLocalStorage = global.localStorage;

    // Reset all mocks
    vi.clearAllMocks();

    // Mock document with proper mock functions
    mockDocument = {
      getElementById: vi.fn(),
      documentElement: {
        setAttribute: vi.fn(),
        getAttribute: vi.fn().mockReturnValue(null),
      },
      addEventListener: vi.fn(),
    };

    // Mock window
    mockWindow = {
      localStorage: mockLocalStorage,
      matchMedia: vi.fn().mockReturnValue(mockMatchMedia),
    };

    // Replace global objects
    Object.defineProperty(global, 'document', { value: mockDocument, writable: true });
    Object.defineProperty(global, 'window', { value: mockWindow, writable: true });
    Object.defineProperty(global, 'localStorage', { value: mockLocalStorage, writable: true });

    // Setup default DOM element returns
    mockDocument.getElementById.mockImplementation((id: string) => {
      switch (id) {
        case 'theme-toggle':
          return mockToggleButton;
        case 'theme-icon-light':
          return mockLightIcon;
        case 'theme-icon-dark':
          return mockDarkIcon;
        default:
          return null;
      }
    });

    // Reset localStorage mock to default value
    vi.mocked(mockLocalStorage.getItem).mockReturnValue(null);
  });

  afterEach((): void => {
    // Restore original globals
    Object.defineProperty(global, 'document', { value: originalDocument, writable: true });
    Object.defineProperty(global, 'window', { value: originalWindow, writable: true });
    Object.defineProperty(global, 'localStorage', { value: originalLocalStorage, writable: true });

    vi.restoreAllMocks();
  });

  test('should initialize with light theme when no stored preference and system prefers light', (): void => {
    mockWindow.matchMedia.mockReturnValue({ ...mockMatchMedia, matches: false });

    themeSwitcher = new ThemeSwitcher();

    expect(mockDocument.documentElement.setAttribute).toHaveBeenCalledWith('data-bs-theme', 'light');
    expect(mockLocalStorage.setItem).toHaveBeenCalledWith('pmf-theme', 'light');
  });

  test('should initialize with dark theme when no stored preference and system prefers dark', (): void => {
    mockWindow.matchMedia.mockReturnValue({ ...mockMatchMedia, matches: true });

    themeSwitcher = new ThemeSwitcher();

    expect(mockDocument.documentElement.setAttribute).toHaveBeenCalledWith('data-bs-theme', 'dark');
    expect(mockLocalStorage.setItem).toHaveBeenCalledWith('pmf-theme', 'dark');
  });

  test('should apply stored theme from localStorage', (): void => {
    vi.mocked(mockLocalStorage.getItem).mockReturnValue('dark');

    themeSwitcher = new ThemeSwitcher();

    expect(mockDocument.documentElement.setAttribute).toHaveBeenCalledWith('data-bs-theme', 'dark');
  });

  test('should setup event listeners for toggle button', (): void => {
    themeSwitcher = new ThemeSwitcher();

    expect(mockDocument.getElementById).toHaveBeenCalledWith('theme-toggle');
    expect(mockDocument.getElementById).toHaveBeenCalledWith('theme-icon-light');
    expect(mockDocument.getElementById).toHaveBeenCalledWith('theme-icon-dark');
    expect(mockToggleButton.addEventListener).toHaveBeenCalledWith('click', expect.any(Function));
  });

  test('should toggle theme from light to dark when button is clicked', (): void => {
    vi.mocked(mockDocument.documentElement.getAttribute).mockReturnValue('light');
    themeSwitcher = new ThemeSwitcher();

    // Simulate button click - properly type the handler
    const clickHandler = vi.mocked(mockToggleButton.addEventListener).mock.calls[0][1] as EventListener;
    clickHandler(new Event('click'));

    expect(mockDocument.documentElement.setAttribute).toHaveBeenCalledWith('data-bs-theme', 'dark');
    expect(mockLocalStorage.setItem).toHaveBeenCalledWith('pmf-theme', 'dark');
  });

  test('should toggle theme from dark to light when button is clicked', (): void => {
    vi.mocked(mockDocument.documentElement.getAttribute).mockReturnValue('dark');
    themeSwitcher = new ThemeSwitcher();

    // Simulate button click - properly type the handler
    const clickHandler = vi.mocked(mockToggleButton.addEventListener).mock.calls[0][1] as EventListener;
    clickHandler(new Event('click'));

    expect(mockDocument.documentElement.setAttribute).toHaveBeenCalledWith('data-bs-theme', 'light');
    expect(mockLocalStorage.setItem).toHaveBeenCalledWith('pmf-theme', 'light');
  });

  test('should update button state and icons for dark theme', (): void => {
    vi.mocked(mockDocument.documentElement.getAttribute).mockReturnValue('dark');
    themeSwitcher = new ThemeSwitcher();

    expect(mockLightIcon.style.display).toBe('inline-block');
    expect(mockDarkIcon.style.display).toBe('none');
  });

  test('should update button state and icons for light theme', (): void => {
    vi.mocked(mockDocument.documentElement.getAttribute).mockReturnValue('light');
    themeSwitcher = new ThemeSwitcher();

    expect(mockLightIcon.style.display).toBe('none');
    expect(mockDarkIcon.style.display).toBe('inline-block');
  });

  test('should watch system theme changes', (): void => {
    themeSwitcher = new ThemeSwitcher();
    themeSwitcher.watchSystemTheme();

    expect(mockWindow.matchMedia).toHaveBeenCalledWith('(prefers-color-scheme: dark)');
    expect(mockMatchMedia.addEventListener).toHaveBeenCalledWith('change', expect.any(Function));
  });

  test('should handle system theme change when no stored preference', (): void => {
    vi.mocked(mockLocalStorage.getItem).mockReturnValue(null);
    themeSwitcher = new ThemeSwitcher();
    themeSwitcher.watchSystemTheme();

    // Simulate system theme change to dark - properly type the handler
    const changeHandler = vi.mocked(mockMatchMedia.addEventListener).mock.calls[0][1] as EventListener;
    changeHandler({ matches: true } as MediaQueryListEvent);

    expect(mockDocument.documentElement.setAttribute).toHaveBeenCalledWith('data-bs-theme', 'dark');
    expect(mockLocalStorage.setItem).toHaveBeenCalledWith('pmf-theme', 'dark');
  });

  test('should not change theme on system change when user has stored preference', (): void => {
    vi.mocked(mockLocalStorage.getItem).mockReturnValue('light');
    themeSwitcher = new ThemeSwitcher();

    // Clear mocks before calling watchSystemTheme to avoid interference
    vi.clearAllMocks();

    themeSwitcher.watchSystemTheme();

    // Simulate system theme change to dark - properly type the handler
    const changeHandler = vi.mocked(mockMatchMedia.addEventListener).mock.calls[0][1] as EventListener;
    changeHandler({ matches: true } as MediaQueryListEvent);

    expect(mockDocument.documentElement.setAttribute).not.toHaveBeenCalled();
    expect(mockLocalStorage.setItem).not.toHaveBeenCalled();
  });

  test('should handle missing DOM elements gracefully', (): void => {
    mockDocument.getElementById.mockReturnValue(null);

    expect((): void => {
      themeSwitcher = new ThemeSwitcher();
    }).not.toThrow();
  });
});
