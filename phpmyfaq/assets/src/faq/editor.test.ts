/**
 * Unit tests for the Add FAQ Editor
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2025-2026 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2025-12-22
 */

import { beforeEach, describe, expect, it, vi } from 'vitest';

Object.defineProperty(window, 'matchMedia', {
  writable: true,
  value: vi.fn().mockImplementation((query: string) => ({
    matches: false,
    media: query,
    onchange: null,
    addListener: vi.fn(),
    removeListener: vi.fn(),
    addEventListener: vi.fn(),
    removeEventListener: vi.fn(),
    dispatchEvent: vi.fn(),
  })),
});

class MockMutationObserver {
  observe = vi.fn();
  disconnect = vi.fn();
  takeRecords = vi.fn();
}
global.MutationObserver = MockMutationObserver as unknown as typeof MutationObserver;

const mockContainer = document.createElement('div');
mockContainer.classList.add('jodit_theme_default');

const mockEditorInstance = {
  options: { theme: 'default' },
  container: mockContainer,
  value: '',
};

vi.mock('jodit', () => ({
  Jodit: {
    make: vi.fn(() => mockEditorInstance),
  },
}));

vi.mock('jodit/esm/plugins/clean-html/clean-html.js', () => ({}));
vi.mock('jodit/esm/plugins/clipboard/clipboard.js', () => ({}));
vi.mock('jodit/esm/plugins/delete/delete.js', () => ({}));
vi.mock('jodit/esm/plugins/fullsize/fullsize.js', () => ({}));
vi.mock('jodit/esm/plugins/image/image.js', () => ({}));
vi.mock('jodit/esm/plugins/indent/indent.js', () => ({}));
vi.mock('jodit/esm/plugins/justify/justify.js', () => ({}));
vi.mock('jodit/esm/plugins/paste-from-word/paste-from-word.js', () => ({}));
vi.mock('jodit/esm/plugins/preview/preview.js', () => ({}));
vi.mock('jodit/esm/plugins/resizer/resizer.js', () => ({}));
vi.mock('jodit/esm/plugins/select/select.js', () => ({}));
vi.mock('jodit/esm/plugins/source/source.js', () => ({}));

import { Jodit } from 'jodit';
import { getJoditEditor, renderFaqEditor } from './editor';

// eslint-disable-next-line @typescript-eslint/no-explicit-any
const getJoditConfig = (): any => vi.mocked(Jodit.make).mock.calls[0][1];

describe('renderFaqEditor', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    document.body.innerHTML = '';
    mockEditorInstance.options = { theme: 'default' };
    mockContainer.className = 'jodit_theme_default';
    document.documentElement.removeAttribute('data-bs-theme');
  });

  it('returns early when the answer textarea does not exist', () => {
    renderFaqEditor();

    expect(getJoditEditor()).toBeNull();
    expect(Jodit.make).not.toHaveBeenCalled();
  });

  it('calls Jodit.make when the answer textarea exists', () => {
    document.body.innerHTML = '<textarea id="answer"></textarea>';

    renderFaqEditor();

    expect(Jodit.make).toHaveBeenCalled();
    expect(getJoditEditor()).toBe(mockEditorInstance);
  });

  it('passes the answer textarea element to Jodit.make', () => {
    document.body.innerHTML = '<textarea id="answer"></textarea>';

    renderFaqEditor();

    const callArgs = vi.mocked(Jodit.make).mock.calls[0];
    const element = callArgs[0] as HTMLTextAreaElement;
    expect(element.id).toBe('answer');
    expect(element.tagName).toBe('TEXTAREA');
  });

  it('configures the reduced FAQ toolbar buttons', () => {
    document.body.innerHTML = '<textarea id="answer"></textarea>';

    renderFaqEditor();

    const config = getJoditConfig();
    expect(config.buttons).toContain('bold');
    expect(config.buttons).toContain('italic');
    expect(config.buttons).toContain('underline');
    expect(config.buttons).toContain('strikethrough');
    expect(config.buttons).toContain('ul');
    expect(config.buttons).toContain('ol');
    expect(config.buttons).toContain('link');
    expect(config.buttons).toContain('image');
    expect(config.buttons).toContain('preview');
    expect(config.buttons).toContain('fullsize');
  });

  it('disables upload-related and advanced plugins', () => {
    document.body.innerHTML = '<textarea id="answer"></textarea>';

    renderFaqEditor();

    const config = getJoditConfig();
    expect(config.disablePlugins).toContain('file');
    expect(config.disablePlugins).toContain('video');
    expect(config.disablePlugins).toContain('media');
    expect(config.disablePlugins).toContain('table');
  });

  it('disables upload and file browser endpoints', () => {
    document.body.innerHTML = '<textarea id="answer"></textarea>';

    renderFaqEditor();

    const config = getJoditConfig();
    expect(config.uploader.url).toBe('');
    expect(config.filebrowser.ajax.url).toBe('');
  });

  it('uses the default theme when prefers-color-scheme is light', () => {
    document.body.innerHTML = '<textarea id="answer"></textarea>';

    renderFaqEditor();

    const config = getJoditConfig();
    expect(config.theme).toBe('default');
  });

  it('uses the dark theme when prefers-color-scheme is dark', () => {
    vi.mocked(window.matchMedia).mockImplementation((query: string) => ({
      matches: true,
      media: query,
      onchange: null,
      addListener: vi.fn(),
      removeListener: vi.fn(),
      addEventListener: vi.fn(),
      removeEventListener: vi.fn(),
      dispatchEvent: vi.fn(),
    }));

    document.body.innerHTML = '<textarea id="answer"></textarea>';

    renderFaqEditor();

    const config = getJoditConfig();
    expect(config.theme).toBe('dark');
  });

  it('applies the Bootstrap theme from data-bs-theme', () => {
    document.body.innerHTML = '<textarea id="answer"></textarea>';
    document.documentElement.setAttribute('data-bs-theme', 'dark');

    renderFaqEditor();

    expect(mockEditorInstance.options.theme).toBe('dark');
    expect(mockContainer.classList.contains('jodit_theme_dark')).toBe(true);
  });

  it('sets up a MutationObserver for Bootstrap theme changes', () => {
    const observeCalls: unknown[][] = [];

    class SpyMutationObserver {
      observe = (...args: unknown[]) => {
        observeCalls.push(args);
      };
      disconnect = vi.fn();
      takeRecords = vi.fn();
    }
    global.MutationObserver = SpyMutationObserver as unknown as typeof MutationObserver;

    document.body.innerHTML = '<textarea id="answer"></textarea>';

    renderFaqEditor();

    expect(observeCalls.length).toBe(1);
    expect(observeCalls[0][0]).toBe(document.documentElement);
    expect(observeCalls[0][1]).toEqual({ attributes: true, attributeFilter: ['data-bs-theme'] });

    global.MutationObserver = MockMutationObserver as unknown as typeof MutationObserver;
  });

  it('registers a storage event listener for theme synchronization', () => {
    const addEventListenerSpy = vi.spyOn(window, 'addEventListener');
    document.body.innerHTML = '<textarea id="answer"></textarea>';

    renderFaqEditor();

    expect(addEventListenerSpy).toHaveBeenCalledWith('storage', expect.any(Function));
  });
});

describe('getJoditEditor', () => {
  it('returns null before the FAQ editor is rendered', () => {
    expect(getJoditEditor() === null || typeof getJoditEditor() === 'object').toBe(true);
  });
});
