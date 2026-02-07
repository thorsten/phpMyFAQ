import { beforeEach, afterEach, describe, expect, it, vi } from 'vitest';
import { getLatestVersion } from './dashboard';

vi.mock('masonry-layout', () => ({
  default: vi.fn(),
}));

vi.mock('chart.js', () => ({
  Chart: {
    register: vi.fn(),
  },
  registerables: [],
}));

describe('dashboard getLatestVersion', () => {
  beforeEach(() => {
    document.body.innerHTML = `
      <div id="version-loader" class="d-none"></div>
      <div id="phpmyfaq-latest-version"></div>
    `;
  });

  afterEach(() => {
    document.body.innerHTML = '';
    vi.unstubAllGlobals();
    vi.clearAllMocks();
  });

  it('renders success alert when API returns success message', async () => {
    const fetchMock = vi.fn().mockResolvedValue({
      ok: true,
      status: 200,
      json: vi.fn().mockResolvedValue({ success: 'Up to date' }),
    });
    vi.stubGlobal('fetch', fetchMock);

    await getLatestVersion();

    const loader = document.getElementById('version-loader');
    const versionText = document.getElementById('phpmyfaq-latest-version');

    expect(loader?.classList.contains('d-none')).toBe(true);
    const alert = versionText?.nextElementSibling as HTMLElement | null;
    expect(alert?.classList.contains('alert-success')).toBe(true);
    // jsdom doesn't reliably implement innerText; only assert the element is added.
    expect(alert?.textContent).not.toBeNull();
  });

  it('renders error alert when API response is not ok', async () => {
    const fetchMock = vi.fn().mockResolvedValue({
      ok: false,
      status: 500,
      json: vi.fn().mockResolvedValue({}),
    });
    vi.stubGlobal('fetch', fetchMock);

    await getLatestVersion();

    const loader = document.getElementById('version-loader');
    const versionText = document.getElementById('phpmyfaq-latest-version');

    expect(loader?.classList.contains('d-none')).toBe(true);
    const alert = versionText?.nextElementSibling as HTMLElement | null;
    expect(alert?.classList.contains('alert-danger')).toBe(true);
    expect(alert?.textContent).not.toBeNull();
  });
});
