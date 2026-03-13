import { beforeEach, afterEach, describe, expect, it, vi } from 'vitest';
import { getLatestVersion, fetchRecentNews, parseNewsMarkdown } from './dashboard';

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
});
