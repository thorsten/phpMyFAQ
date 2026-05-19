import { beforeEach, afterEach, describe, expect, it, vi } from 'vitest';
import { handleDashboardLayout } from './dashboard-layout';

vi.mock('masonry-layout', () => ({
  default: class {
    reloadItems = vi.fn();
    layout = vi.fn();
  },
}));

const buildDashboard = (): void => {
  document.body.innerHTML = `
    <div id="pmf-dashboard-toolbar">
      <button id="pmf-dashboard-reset" class="d-none"></button>
      <button id="pmf-dashboard-edit-toggle"
              class="btn-outline-secondary"
              data-pmf-edit-label="Customize"
              data-pmf-done-label="Done">
        <span class="pmf-dashboard-edit-label">Customize</span>
      </button>
    </div>
    <section class="masonry-grid" data-pmf-csrf-token="token-123">
      <div class="col" data-pmf-widget="inactive-faqs"><div class="card"><h5>Inactive</h5></div></div>
      <div class="col" data-pmf-widget="recent-users"><div class="card"><h5>Users</h5></div></div>
      <div class="col" data-pmf-widget="content-health"><div class="card"><h5>Health</h5></div></div>
    </section>
  `;
};

describe('handleDashboardLayout', () => {
  beforeEach(() => {
    buildDashboard();
  });

  afterEach(() => {
    document.body.innerHTML = '';
    vi.unstubAllGlobals();
    vi.clearAllMocks();
  });

  it('does nothing when the dashboard grid is missing', async () => {
    document.body.innerHTML = '';
    const fetchMock = vi.fn();
    vi.stubGlobal('fetch', fetchMock);

    await handleDashboardLayout();

    expect(fetchMock).not.toHaveBeenCalled();
  });

  it('applies stored order and visibility from the layout API', async () => {
    const fetchMock = vi.fn().mockResolvedValue({
      ok: true,
      json: vi.fn().mockResolvedValue({
        config: [
          { key: 'content-health', position: 0, visible: true },
          { key: 'inactive-faqs', position: 1, visible: false },
          { key: 'recent-users', position: 2, visible: true },
        ],
      }),
    });
    vi.stubGlobal('fetch', fetchMock);

    await handleDashboardLayout();

    const order = Array.from(document.querySelectorAll('[data-pmf-widget]')).map(
      (widget) => (widget as HTMLElement).dataset.pmfWidget
    );
    expect(order).toEqual(['content-health', 'inactive-faqs', 'recent-users']);

    const hidden = document.querySelector('[data-pmf-widget="inactive-faqs"]') as HTMLElement;
    expect(hidden.classList.contains('d-none')).toBe(true);
  });

  it('orders widgets by their stored position regardless of payload order', async () => {
    const fetchMock = vi.fn().mockResolvedValue({
      ok: true,
      json: vi.fn().mockResolvedValue({
        config: [
          { key: 'recent-users', position: 2, visible: true },
          { key: 'content-health', position: 0, visible: true },
          { key: 'inactive-faqs', position: 1, visible: false },
        ],
      }),
    });
    vi.stubGlobal('fetch', fetchMock);

    await handleDashboardLayout();

    const order = Array.from(document.querySelectorAll('[data-pmf-widget]')).map(
      (widget) => (widget as HTMLElement).dataset.pmfWidget
    );
    expect(order).toEqual(['content-health', 'inactive-faqs', 'recent-users']);

    const hidden = document.querySelector('[data-pmf-widget="inactive-faqs"]') as HTMLElement;
    expect(hidden.classList.contains('d-none')).toBe(true);
  });

  it('shows per-widget controls when edit mode is enabled', async () => {
    vi.stubGlobal('fetch', vi.fn().mockResolvedValue({ ok: true, json: vi.fn().mockResolvedValue({ config: [] }) }));

    await handleDashboardLayout();
    document.getElementById('pmf-dashboard-edit-toggle')?.dispatchEvent(new Event('click'));

    expect(document.querySelectorAll('.pmf-widget-controls').length).toBe(3);
    expect(document.querySelector('.masonry-grid')?.classList.contains('pmf-dashboard-editing')).toBe(true);
    expect(document.getElementById('pmf-dashboard-reset')?.classList.contains('d-none')).toBe(false);
  });

  it('saves the layout with the CSRF token when leaving edit mode', async () => {
    const fetchMock = vi
      .fn()
      .mockResolvedValueOnce({ ok: true, json: vi.fn().mockResolvedValue({ config: [] }) })
      .mockResolvedValue({ ok: true, json: vi.fn().mockResolvedValue({}) });
    vi.stubGlobal('fetch', fetchMock);

    await handleDashboardLayout();
    const toggle = document.getElementById('pmf-dashboard-edit-toggle');
    toggle?.dispatchEvent(new Event('click')); // enter edit mode
    toggle?.dispatchEvent(new Event('click')); // leave edit mode -> save

    await vi.waitFor(() => {
      expect(fetchMock).toHaveBeenCalledWith('./api/dashboard/layout', expect.objectContaining({ method: 'POST' }));
    });

    const saveCall = fetchMock.mock.calls.find(
      (call) => call[0] === './api/dashboard/layout' && call[1]?.method === 'POST'
    );
    const body = JSON.parse(saveCall?.[1]?.body as string);
    expect(body.csrfToken).toBe('token-123');
    expect(body.config).toHaveLength(3);
  });
});
