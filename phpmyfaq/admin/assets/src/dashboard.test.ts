import { beforeEach, afterEach, describe, expect, it, vi } from 'vitest';
import {
  getLatestVersion,
  fetchRecentNews,
  parseNewsMarkdown,
  renderVisitorCharts,
  renderTopTenCharts,
  handleVerificationModal,
} from './dashboard';

vi.mock('masonry-layout', () => ({
  default: vi.fn(),
}));

vi.mock('chart.js', () => {
  const sharedInstance = {
    data: { labels: [] as string[], datasets: [{ data: [] as number[] }] },
    options: { plugins: {}, scales: {} },
    update: vi.fn(),
  };
  class ChartMock {
    static register = vi.fn();
    static __instance = sharedInstance;
    data = sharedInstance.data;
    options = sharedInstance.options;
    update = sharedInstance.update;
    constructor() {
      // Sync reference so tests can read the same object
      Object.assign(this, sharedInstance);
    }
  }
  return { Chart: ChartMock, registerables: [] };
});

vi.mock('./api', () => ({
  getRemoteHashes: vi.fn(),
  verifyHashes: vi.fn(),
}));

vi.mock('../../../assets/src/utils', () => ({
  addElement: vi.fn((tag: string, props: Record<string, string>) => {
    const el = document.createElement(tag);
    if (props.classList) el.className = props.classList;
    if (props.innerText) el.innerText = props.innerText;
    return el;
  }),
  TranslationService: vi.fn(function () {
    return {
      loadTranslations: vi.fn().mockResolvedValue(undefined),
      translate: vi.fn().mockImplementation((key: string) => `translated_${key}`),
    };
  }),
}));

import { Chart } from 'chart.js';
import { getRemoteHashes, verifyHashes } from './api';

// eslint-disable-next-line @typescript-eslint/no-explicit-any
const getChartInstance = () => (Chart as any).__instance;

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

describe('parseNewsMarkdown', () => {
  it('converts markdown links with absolute URLs', () => {
    const result = parseNewsMarkdown('Check [our site](https://example.com) now');
    expect(result).toContain('<a href="https://example.com" target="_blank" rel="noopener noreferrer">our site</a>');
  });

  it('resolves relative URLs against phpmyfaq.de base', () => {
    const result = parseNewsMarkdown('See [downloads](/download)');
    expect(result).toContain(
      '<a href="https://www.phpmyfaq.de/download" target="_blank" rel="noopener noreferrer">downloads</a>'
    );
  });

  it('resolves relative URLs without leading slash', () => {
    const result = parseNewsMarkdown('See [news](news/latest)');
    expect(result).toContain(
      '<a href="https://www.phpmyfaq.de/news/latest" target="_blank" rel="noopener noreferrer">news</a>'
    );
  });

  it('converts bold markdown', () => {
    const result = parseNewsMarkdown('This is **important** text');
    expect(result).toContain('<strong>important</strong>');
  });

  it('converts italic markdown', () => {
    const result = parseNewsMarkdown('This is *italic* text');
    expect(result).toContain('<em>italic</em>');
  });

  it('escapes HTML entities to prevent XSS', () => {
    const result = parseNewsMarkdown('<script>alert("xss")</script>');
    expect(result).not.toContain('<script>');
    expect(result).toContain('&lt;script&gt;');
  });

  it('returns plain text when no markdown is present', () => {
    const result = parseNewsMarkdown('Just plain text');
    expect(result).toBe('Just plain text');
  });
});

describe('fetchRecentNews', () => {
  beforeEach(() => {
    document.body.innerHTML = `
      <div id="pmf-news-loader" class="d-none"></div>
      <div id="pmf-recent-news"></div>
    `;
  });

  afterEach(() => {
    document.body.innerHTML = '';
    vi.unstubAllGlobals();
    vi.clearAllMocks();
  });

  it('does nothing when container element is missing', async () => {
    document.body.innerHTML = '';
    const fetchMock = vi.fn();
    vi.stubGlobal('fetch', fetchMock);

    await fetchRecentNews();

    expect(fetchMock).not.toHaveBeenCalled();
  });

  it('renders news items on successful response', async () => {
    const fetchMock = vi.fn().mockResolvedValue({
      ok: true,
      json: vi.fn().mockResolvedValue({
        news: [
          { date: '2026-03-10', content: 'phpMyFAQ **4.2** released' },
          { date: '2026-03-01', content: 'Check [downloads](/download)' },
        ],
      }),
    });
    vi.stubGlobal('fetch', fetchMock);

    await fetchRecentNews();

    const container = document.getElementById('pmf-recent-news') as HTMLDivElement;
    const items = container.querySelectorAll('li');
    expect(items.length).toBe(2);
    expect(items[0].querySelector('small')?.textContent).toBe('2026-03-10');
    expect(items[0].querySelector('span')?.innerHTML).toContain('<strong>4.2</strong>');
    expect(items[1].querySelector('span')?.innerHTML).toContain('href="https://www.phpmyfaq.de/download"');
  });

  it('limits displayed news to 5 items', async () => {
    const news = Array.from({ length: 8 }, (_, i) => ({
      date: `2026-03-${String(i + 1).padStart(2, '0')}`,
      content: `News item ${i + 1}`,
    }));
    const fetchMock = vi.fn().mockResolvedValue({
      ok: true,
      json: vi.fn().mockResolvedValue({ news }),
    });
    vi.stubGlobal('fetch', fetchMock);

    await fetchRecentNews();

    const container = document.getElementById('pmf-recent-news') as HTMLDivElement;
    expect(container.querySelectorAll('li').length).toBe(5);
  });

  it('shows fallback message when news array is empty', async () => {
    const fetchMock = vi.fn().mockResolvedValue({
      ok: true,
      json: vi.fn().mockResolvedValue({ news: [] }),
    });
    vi.stubGlobal('fetch', fetchMock);

    await fetchRecentNews();

    const container = document.getElementById('pmf-recent-news') as HTMLDivElement;
    expect(container.textContent).toContain('No recent news available.');
  });

  it('shows error message when response is not ok', async () => {
    const fetchMock = vi.fn().mockResolvedValue({
      ok: false,
      json: vi.fn().mockResolvedValue({ error: 'disabled' }),
    });
    vi.stubGlobal('fetch', fetchMock);

    await fetchRecentNews();

    const container = document.getElementById('pmf-recent-news') as HTMLDivElement;
    expect(container.textContent).toContain('Could not load news.');
  });

  it('shows error message on fetch failure', async () => {
    const fetchMock = vi.fn().mockRejectedValue(new Error('Network error'));
    vi.stubGlobal('fetch', fetchMock);

    await fetchRecentNews();

    const container = document.getElementById('pmf-recent-news') as HTMLDivElement;
    expect(container.textContent).toContain('Could not load news.');
    const loader = document.getElementById('pmf-news-loader') as HTMLDivElement;
    expect(loader.classList.contains('d-none')).toBe(true);
  });

  it('hides loader after successful fetch', async () => {
    const fetchMock = vi.fn().mockResolvedValue({
      ok: true,
      json: vi.fn().mockResolvedValue({ news: [{ date: '2026-03-10', content: 'Test' }] }),
    });
    vi.stubGlobal('fetch', fetchMock);

    await fetchRecentNews();

    const loader = document.getElementById('pmf-news-loader') as HTMLDivElement;
    expect(loader.classList.contains('d-none')).toBe(true);
  });

  it('works when loader element is missing', async () => {
    document.body.innerHTML = '<div id="pmf-recent-news"></div>';
    const fetchMock = vi.fn().mockResolvedValue({
      ok: true,
      json: vi.fn().mockResolvedValue({ news: [{ date: '2026-03-10', content: 'Test' }] }),
    });
    vi.stubGlobal('fetch', fetchMock);

    await fetchRecentNews();

    const container = document.getElementById('pmf-recent-news') as HTMLDivElement;
    expect(container.querySelectorAll('li').length).toBe(1);
  });

  it('handles missing news key in response', async () => {
    const fetchMock = vi.fn().mockResolvedValue({
      ok: true,
      json: vi.fn().mockResolvedValue({}),
    });
    vi.stubGlobal('fetch', fetchMock);

    await fetchRecentNews();

    const container = document.getElementById('pmf-recent-news') as HTMLDivElement;
    expect(container.textContent).toContain('No recent news available.');
  });
});

describe('dashboard getLatestVersion additional cases', () => {
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

  it('does nothing when DOM elements are missing', async () => {
    document.body.innerHTML = '';
    const fetchMock = vi.fn();
    vi.stubGlobal('fetch', fetchMock);

    await getLatestVersion();

    expect(fetchMock).not.toHaveBeenCalled();
  });

  it('renders warning alert when API returns warning', async () => {
    const fetchMock = vi.fn().mockResolvedValue({
      ok: true,
      json: vi.fn().mockResolvedValue({ warning: 'New version available' }),
    });
    vi.stubGlobal('fetch', fetchMock);

    await getLatestVersion();

    const versionText = document.getElementById('phpmyfaq-latest-version');
    const alert = versionText?.nextElementSibling as HTMLElement | null;
    expect(alert?.classList.contains('alert-danger')).toBe(true);
  });

  it('renders error alert when API returns error', async () => {
    const fetchMock = vi.fn().mockResolvedValue({
      ok: true,
      json: vi.fn().mockResolvedValue({ error: 'Version check failed' }),
    });
    vi.stubGlobal('fetch', fetchMock);

    await getLatestVersion();

    const versionText = document.getElementById('phpmyfaq-latest-version');
    const alert = versionText?.nextElementSibling as HTMLElement | null;
    expect(alert?.classList.contains('alert-danger')).toBe(true);
  });

  it('renders error alert on network exception', async () => {
    const fetchMock = vi.fn().mockRejectedValue(new Error('Connection refused'));
    vi.stubGlobal('fetch', fetchMock);

    await getLatestVersion();

    const loader = document.getElementById('version-loader');
    expect(loader?.classList.contains('d-none')).toBe(true);

    const versionText = document.getElementById('phpmyfaq-latest-version');
    const alert = versionText?.nextElementSibling as HTMLElement | null;
    expect(alert?.classList.contains('alert-danger')).toBe(true);
  });

  it('shows loader while fetching', async () => {
    let loaderVisibleDuringFetch = false;
    const fetchMock = vi.fn().mockImplementation(() => {
      const loader = document.getElementById('version-loader');
      loaderVisibleDuringFetch = !loader?.classList.contains('d-none');
      return Promise.resolve({
        ok: true,
        json: vi.fn().mockResolvedValue({ success: 'OK' }),
      });
    });
    vi.stubGlobal('fetch', fetchMock);

    await getLatestVersion();

    expect(loaderVisibleDuringFetch).toBe(true);
  });
});

describe('renderVisitorCharts', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    getChartInstance().data = { labels: [], datasets: [{ data: [] }] };
    getChartInstance().options = { plugins: {}, scales: {} };
    getChartInstance().update.mockClear();
  });

  afterEach(() => {
    document.body.innerHTML = '';
    vi.unstubAllGlobals();
  });

  it('does nothing when canvas element is missing', async () => {
    document.body.innerHTML = '<div></div>';
    const fetchMock = vi.fn();
    vi.stubGlobal('fetch', fetchMock);

    await renderVisitorCharts();

    expect(fetchMock).not.toHaveBeenCalled();
  });

  it('fetches visit data when canvas element exists', async () => {
    document.body.innerHTML = '<canvas id="pmf-chart-visits"></canvas>';
    const fetchMock = vi.fn().mockResolvedValue({
      status: 200,
      json: vi.fn().mockResolvedValue([]),
    });
    vi.stubGlobal('fetch', fetchMock);

    await renderVisitorCharts();

    expect(fetchMock).toHaveBeenCalledWith('./api/dashboard/visits', expect.any(Object));
  });

  it('populates chart with visit data from API', async () => {
    document.body.innerHTML = '<canvas id="pmf-chart-visits"></canvas>';
    const visits = [
      { date: '2026-03-01', number: 100 },
      { date: '2026-03-02', number: 150 },
    ];
    const fetchMock = vi.fn().mockResolvedValue({
      status: 200,
      json: vi.fn().mockResolvedValue(visits),
    });
    vi.stubGlobal('fetch', fetchMock);

    await renderVisitorCharts();

    expect(getChartInstance().data.labels).toEqual(['2026-03-01', '2026-03-02']);
    expect(getChartInstance().data.datasets[0].data).toEqual([100, 150]);
    expect(getChartInstance().update).toHaveBeenCalled();
  });

  it('does not update chart when API returns non-200 status', async () => {
    document.body.innerHTML = '<canvas id="pmf-chart-visits"></canvas>';
    const fetchMock = vi.fn().mockResolvedValue({
      status: 500,
      json: vi.fn().mockResolvedValue({}),
    });
    vi.stubGlobal('fetch', fetchMock);

    await renderVisitorCharts();

    expect(getChartInstance().data.labels).toEqual([]);
    expect(getChartInstance().update).not.toHaveBeenCalled();
  });

  it('logs error on fetch failure', async () => {
    document.body.innerHTML = '<canvas id="pmf-chart-visits"></canvas>';
    const consoleSpy = vi.spyOn(console, 'error').mockImplementation(() => {});
    const fetchMock = vi.fn().mockRejectedValue(new Error('Network error'));
    vi.stubGlobal('fetch', fetchMock);

    await renderVisitorCharts();

    expect(consoleSpy).toHaveBeenCalledWith('Request failure: ', expect.any(Error));
    consoleSpy.mockRestore();
  });
});

describe('renderTopTenCharts', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    getChartInstance().data = { labels: [], datasets: [{ data: [] }] };
    getChartInstance().options = { plugins: {}, scales: {} };
    getChartInstance().update.mockClear();
  });

  afterEach(() => {
    document.body.innerHTML = '';
    vi.unstubAllGlobals();
  });

  it('does nothing when canvas element is missing', async () => {
    document.body.innerHTML = '<div></div>';
    const fetchMock = vi.fn();
    vi.stubGlobal('fetch', fetchMock);

    await renderTopTenCharts();

    expect(fetchMock).not.toHaveBeenCalled();
  });

  it('fetches top ten data when canvas element exists', async () => {
    document.body.innerHTML = '<canvas id="pmf-chart-topten"></canvas>';
    const fetchMock = vi.fn().mockResolvedValue({
      status: 200,
      json: vi.fn().mockResolvedValue([]),
    });
    vi.stubGlobal('fetch', fetchMock);

    await renderTopTenCharts();

    expect(fetchMock).toHaveBeenCalledWith('./api/dashboard/topten', expect.any(Object));
  });

  it('populates chart with top ten data from API', async () => {
    document.body.innerHTML = '<canvas id="pmf-chart-topten"></canvas>';
    const topTen = [
      { question: 'How to install?', visits: 500 },
      { question: 'How to configure?', visits: 300 },
    ];
    const fetchMock = vi.fn().mockResolvedValue({
      status: 200,
      json: vi.fn().mockResolvedValue(topTen),
    });
    vi.stubGlobal('fetch', fetchMock);

    await renderTopTenCharts();

    expect(getChartInstance().data.labels).toEqual(['How to install?', 'How to configure?']);
    expect(getChartInstance().data.datasets[0].data).toEqual([500, 300]);
    expect(getChartInstance().update).toHaveBeenCalled();
  });

  it('logs error on fetch failure', async () => {
    document.body.innerHTML = '<canvas id="pmf-chart-topten"></canvas>';
    const consoleSpy = vi.spyOn(console, 'error').mockImplementation(() => {});
    const fetchMock = vi.fn().mockRejectedValue(new Error('Network error'));
    vi.stubGlobal('fetch', fetchMock);

    await renderTopTenCharts();

    expect(consoleSpy).toHaveBeenCalledWith('Request failure: ', expect.any(Error));
    consoleSpy.mockRestore();
  });
});

describe('handleVerificationModal', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    document.documentElement.lang = 'en';
  });

  afterEach(() => {
    document.body.innerHTML = '';
    vi.unstubAllGlobals();
  });

  it('does nothing when modal element is missing', async () => {
    document.body.innerHTML = '<div></div>';

    await handleVerificationModal();

    expect(getRemoteHashes).not.toHaveBeenCalled();
  });

  it('fetches and verifies hashes when modal is shown', async () => {
    document.body.innerHTML = `
      <div id="verificationModal" data-pmf-current-version="4.2.0">
        <div id="pmf-verification-spinner" class="d-none"></div>
        <div id="pmf-verification-updates"></div>
      </div>
    `;

    vi.mocked(getRemoteHashes).mockResolvedValue({ 'index.php': 'abc123' });
    vi.mocked(verifyHashes).mockResolvedValue({ 'config.php': 'mismatch' });

    await handleVerificationModal();

    const modal = document.getElementById('verificationModal') as HTMLElement;
    modal.dispatchEvent(new Event('show.bs.modal'));

    await vi.waitFor(() => {
      expect(getRemoteHashes).toHaveBeenCalledWith('4.2.0');
      expect(verifyHashes).toHaveBeenCalledWith({ 'index.php': 'abc123' });
    });

    const updates = document.getElementById('pmf-verification-updates') as HTMLElement;
    expect(updates.querySelector('ul')).not.toBeNull();
    expect(updates.querySelector('li')?.textContent).toContain('config.php');

    const spinner = document.getElementById('pmf-verification-spinner') as HTMLElement;
    expect(spinner.classList.contains('d-none')).toBe(true);
  });

  it('shows spinner during verification', async () => {
    document.body.innerHTML = `
      <div id="verificationModal" data-pmf-current-version="4.2.0">
        <div id="pmf-verification-spinner" class="d-none"></div>
        <div id="pmf-verification-updates"></div>
      </div>
    `;

    let spinnerVisibleDuringFetch = false;
    vi.mocked(getRemoteHashes).mockImplementation(async () => {
      const spinner = document.getElementById('pmf-verification-spinner');
      spinnerVisibleDuringFetch = !spinner?.classList.contains('d-none');
      return {};
    });
    vi.mocked(verifyHashes).mockResolvedValue({});

    await handleVerificationModal();

    const modal = document.getElementById('verificationModal') as HTMLElement;
    modal.dispatchEvent(new Event('show.bs.modal'));

    await vi.waitFor(() => {
      expect(getRemoteHashes).toHaveBeenCalled();
    });

    expect(spinnerVisibleDuringFetch).toBe(true);
  });
});
