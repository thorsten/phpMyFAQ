import { afterEach, describe, expect, it, vi } from 'vitest';
import { getShortcutHintLabel, isMacPlatform } from './platform';

const setPlatform = (value: string): void => {
  Object.defineProperty(window.navigator, 'platform', { value, configurable: true });
};

const setUserAgentData = (platform: string | undefined): void => {
  Object.defineProperty(window.navigator, 'userAgentData', {
    value: platform === undefined ? undefined : { platform },
    configurable: true,
  });
};

describe('platform helpers', () => {
  afterEach(() => {
    vi.restoreAllMocks();
    setPlatform('');
    setUserAgentData(undefined);
  });

  it('detects macOS from navigator.userAgentData.platform', () => {
    setUserAgentData('macOS');
    setPlatform('Win32'); // userAgentData must take precedence
    expect(isMacPlatform()).toBe(true);
  });

  it('returns false when userAgentData.platform is not macOS even if navigator.platform is Mac', () => {
    setUserAgentData('Windows');
    setPlatform('MacIntel'); // userAgentData must take precedence over the legacy value
    expect(isMacPlatform()).toBe(false);
  });

  it('falls back to non-Mac when userAgentData is absent and platform is empty', () => {
    setUserAgentData(undefined);
    setPlatform('');
    expect(isMacPlatform()).toBe(false);
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
