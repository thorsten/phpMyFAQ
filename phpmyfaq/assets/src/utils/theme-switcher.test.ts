import { describe, expect, test, vi, beforeEach, afterEach } from 'vitest';
import { ThemeSwitcher } from './theme-switcher';

// Mock DOM elements
const mockLightButton = {
  addEventListener: vi.fn(),
  classList: {
    add: vi.fn(),
    remove: vi.fn(),
  },
} as unknown as HTMLButtonElement;

const mockDarkButton = {
  addEventListener: vi.fn(),
  classList: {
    add: vi.fn(),
    remove: vi.fn(),
  },
} as unknown as HTMLButtonElement;

const mockHighContrastButton = {
  addEventListener: vi.fn(),
  classList: {
    add: vi.fn(),
    remove: vi.fn(),
  },
} as unknown as HTMLButtonElement;

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

interface MockDocument {
  getElementById: ReturnType<typeof vi.fn>;
  documentElement: {
    setAttribute: ReturnType<typeof vi.fn>;
    getAttribute: ReturnType<typeof vi.fn>;
  };
  addEventListener: ReturnType<typeof vi.fn>;
}

interface MockWindow {
  localStorage: Storage;
  matchMedia: ReturnType<typeof vi.fn>;
}

describe('ThemeSwitcher', (): void => {
  let themeSwitcher: ThemeSwitcher;
  let originalDocument: Document;
  let originalWindow: Window & typeof globalThis;
  let originalLocalStorage: Storage;
  let mockDocument: MockDocument;
  let mockWindow: MockWindow;

  beforeEach((): void => {
    // Store original globals
    originalDocument = globalThis.document;
    originalWindow = globalThis.window;
    originalLocalStorage = globalThis.localStorage;

    // Reset all mocks
    vi.clearAllMocks();

    // Create a simple attribute store for realistic getAttribute/setAttribute behavior
    let currentTheme: string | null = null;

    // Mock document with proper mock functions
    mockDocument = {
      getElementById: vi.fn(),
      documentElement: {
        setAttribute: vi.fn((attr: string, value: string) => {
          if (attr === 'data-bs-theme') {
            currentTheme = value;
          }
        }),
        getAttribute: vi.fn((attr: string) => {
          if (attr === 'data-bs-theme') {
            return currentTheme;
          }
          return null;
        }),
      },
      addEventListener: vi.fn(),
    };

    // Mock window
    mockWindow = {
      localStorage: mockLocalStorage,
      matchMedia: vi.fn().mockReturnValue(mockMatchMedia),
    };

    // Replace global objects
    Object.defineProperty(globalThis, 'document', { value: mockDocument, writable: true });
    Object.defineProperty(globalThis, 'window', { value: mockWindow, writable: true });
    Object.defineProperty(globalThis, 'localStorage', { value: mockLocalStorage, writable: true });

    // Setup default DOM element returns
    mockDocument.getElementById.mockImplementation((id: string) => {
      switch (id) {
        case 'theme-light':
          return mockLightButton;
        case 'theme-dark':
          return mockDarkButton;
        case 'theme-high-contrast':
          return mockHighContrastButton;
        default:
          return null;
      }
    });

    // Reset localStorage mock to default value
    vi.mocked(mockLocalStorage.getItem).mockReturnValue(null);
  });

  afterEach((): void => {
    // Restore original globals
    Object.defineProperty(globalThis, 'document', { value: originalDocument, writable: true });
    Object.defineProperty(globalThis, 'window', { value: originalWindow, writable: true });
    Object.defineProperty(globalThis, 'localStorage', { value: originalLocalStorage, writable: true });

    vi.restoreAllMocks();
  });

  test('should initialize with light theme when no stored preference and system prefers light', (): void => {
    mockWindow.matchMedia.mockReturnValue({ ...mockMatchMedia, matches: false });

    themeSwitcher = new ThemeSwitcher();

    expect(mockDocument.documentElement.setAttribute).toHaveBeenCalledWith('data-bs-theme', 'light');
    expect(mockLocalStorage.setItem).toHaveBeenCalledWith('pmf-theme', 'light');
    expect(mockLightButton.classList.add).toHaveBeenCalledWith('active');
  });

  test('should initialize with dark theme when no stored preference and system prefers dark', (): void => {
    mockWindow.matchMedia.mockReturnValue({ ...mockMatchMedia, matches: true });

    themeSwitcher = new ThemeSwitcher();

    expect(mockDocument.documentElement.setAttribute).toHaveBeenCalledWith('data-bs-theme', 'dark');
    expect(mockLocalStorage.setItem).toHaveBeenCalledWith('pmf-theme', 'dark');
    expect(mockDarkButton.classList.add).toHaveBeenCalledWith('active');
  });

  test('should apply stored theme from localStorage', (): void => {
    vi.mocked(mockLocalStorage.getItem).mockReturnValue('dark');

    themeSwitcher = new ThemeSwitcher();

    expect(mockDocument.documentElement.setAttribute).toHaveBeenCalledWith('data-bs-theme', 'dark');
    expect(mockDarkButton.classList.add).toHaveBeenCalledWith('active');
  });

  test('should setup event listeners for all three theme buttons', (): void => {
    themeSwitcher = new ThemeSwitcher();

    expect(mockDocument.getElementById).toHaveBeenCalledWith('theme-light');
    expect(mockDocument.getElementById).toHaveBeenCalledWith('theme-dark');
    expect(mockDocument.getElementById).toHaveBeenCalledWith('theme-high-contrast');
    expect(mockLightButton.addEventListener).toHaveBeenCalledWith('click', expect.any(Function));
    expect(mockDarkButton.addEventListener).toHaveBeenCalledWith('click', expect.any(Function));
    expect(mockHighContrastButton.addEventListener).toHaveBeenCalledWith('click', expect.any(Function));
  });

  test('should set light theme when light button is clicked', (): void => {
    vi.mocked(mockLocalStorage.getItem).mockReturnValue('dark');
    themeSwitcher = new ThemeSwitcher();

    // Simulate light button click
    const clickHandler = vi.mocked(mockLightButton.addEventListener).mock.calls[0][1] as EventListener;
    clickHandler(new Event('click'));

    expect(mockDocument.documentElement.setAttribute).toHaveBeenCalledWith('data-bs-theme', 'light');
    expect(mockLocalStorage.setItem).toHaveBeenCalledWith('pmf-theme', 'light');
    expect(mockLightButton.classList.add).toHaveBeenCalledWith('active');
  });

  test('should set dark theme when dark button is clicked', (): void => {
    vi.mocked(mockLocalStorage.getItem).mockReturnValue('light');
    themeSwitcher = new ThemeSwitcher();

    // Simulate dark button click
    const clickHandler = vi.mocked(mockDarkButton.addEventListener).mock.calls[0][1] as EventListener;
    clickHandler(new Event('click'));

    expect(mockDocument.documentElement.setAttribute).toHaveBeenCalledWith('data-bs-theme', 'dark');
    expect(mockLocalStorage.setItem).toHaveBeenCalledWith('pmf-theme', 'dark');
    expect(mockDarkButton.classList.add).toHaveBeenCalledWith('active');
  });

  test('should set high-contrast theme when high-contrast button is clicked', (): void => {
    vi.mocked(mockLocalStorage.getItem).mockReturnValue('light');
    themeSwitcher = new ThemeSwitcher();

    // Simulate high-contrast button click
    const clickHandler = vi.mocked(mockHighContrastButton.addEventListener).mock.calls[0][1] as EventListener;
    clickHandler(new Event('click'));

    expect(mockDocument.documentElement.setAttribute).toHaveBeenCalledWith('data-bs-theme', 'high-contrast');
    expect(mockLocalStorage.setItem).toHaveBeenCalledWith('pmf-theme', 'high-contrast');
    expect(mockHighContrastButton.classList.add).toHaveBeenCalledWith('active');
  });

  test('should update button states correctly for light theme', (): void => {
    vi.mocked(mockLocalStorage.getItem).mockReturnValue('light');
    themeSwitcher = new ThemeSwitcher();

    expect(mockLightButton.classList.remove).toHaveBeenCalledWith('active');
    expect(mockDarkButton.classList.remove).toHaveBeenCalledWith('active');
    expect(mockHighContrastButton.classList.remove).toHaveBeenCalledWith('active');
    expect(mockLightButton.classList.add).toHaveBeenCalledWith('active');
  });

  test('should update button states correctly for dark theme', (): void => {
    vi.mocked(mockLocalStorage.getItem).mockReturnValue('dark');
    themeSwitcher = new ThemeSwitcher();

    expect(mockLightButton.classList.remove).toHaveBeenCalledWith('active');
    expect(mockDarkButton.classList.remove).toHaveBeenCalledWith('active');
    expect(mockHighContrastButton.classList.remove).toHaveBeenCalledWith('active');
    expect(mockDarkButton.classList.add).toHaveBeenCalledWith('active');
  });

  test('should update button states correctly for high-contrast theme', (): void => {
    vi.mocked(mockLocalStorage.getItem).mockReturnValue('high-contrast');
    themeSwitcher = new ThemeSwitcher();

    expect(mockLightButton.classList.remove).toHaveBeenCalledWith('active');
    expect(mockDarkButton.classList.remove).toHaveBeenCalledWith('active');
    expect(mockHighContrastButton.classList.remove).toHaveBeenCalledWith('active');
    expect(mockHighContrastButton.classList.add).toHaveBeenCalledWith('active');
  });

  test('should remove active class from all buttons before adding to selected', (): void => {
    vi.mocked(mockLocalStorage.getItem).mockReturnValue('light');
    themeSwitcher = new ThemeSwitcher();

    // Click dark button
    const clickHandler = vi.mocked(mockDarkButton.addEventListener).mock.calls[0][1] as EventListener;
    clickHandler(new Event('click'));

    // Should remove active from all buttons first
    expect(mockLightButton.classList.remove).toHaveBeenCalledWith('active');
    expect(mockDarkButton.classList.remove).toHaveBeenCalledWith('active');
    expect(mockHighContrastButton.classList.remove).toHaveBeenCalledWith('active');

    // Then add to the dark button
    expect(mockDarkButton.classList.add).toHaveBeenCalledWith('active');
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

    // Simulate system theme change to dark
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

    // Simulate system theme change to dark
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
