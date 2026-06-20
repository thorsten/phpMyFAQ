import { afterEach, describe, expect, it, vi } from 'vitest';
import { getShortcutHintLabel, isMacPlatform } from './platform';

const setPlatform = (value: string): void => {
  Object.defineProperty(window.navigator, 'platform', { value, configurable: true });
};

describe('platform helpers', () => {
  afterEach(() => {
    vi.restoreAllMocks();
    setPlatform('');
  });

  it('detects macOS from navigator.platform', () => {
    setPlatform('MacIntel');
    expect(isMacPlatform()).toBe(true);
  });

  it('returns false for Windows', () => {
    setPlatform('Win32');
    expect(isMacPlatform()).toBe(false);
  });

  it('defaults to non-Mac for an unknown platform', () => {
    setPlatform('Linux x86_64');
    expect(isMacPlatform()).toBe(false);
  });

  it('returns the Cmd label on macOS', () => {
    setPlatform('MacIntel');
    expect(getShortcutHintLabel()).toBe('⌘ K');
  });

  it('returns the Ctrl label off macOS', () => {
    setPlatform('Win32');
    expect(getShortcutHintLabel()).toBe('Ctrl K');
  });
});
