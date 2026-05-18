/**
 * Admin dashboard layout customization
 *
 * Lets an admin reorder and hide the dashboard sidebar widgets. The layout is
 * persisted per user via the dashboard layout API.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne
 * @copyright 2026 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2026-05-18
 */

import Masonry from 'masonry-layout';

interface WidgetConfig {
  key: string;
  position: number;
  visible: boolean;
}

const WIDGET_SELECTOR = '[data-pmf-widget]';

const getWidgets = (grid: Element): HTMLElement[] => Array.from(grid.querySelectorAll<HTMLElement>(WIDGET_SELECTOR));

const isHidden = (widget: HTMLElement): boolean => widget.dataset.pmfHidden === 'true';

// Reflects the stored hidden state into the DOM for the current (view/edit) mode
const applyVisibility = (grid: Element, editing: boolean): void => {
  getWidgets(grid).forEach((widget) => {
    const hidden = isHidden(widget);
    widget.classList.toggle('d-none', hidden && !editing);
    widget.classList.toggle('pmf-widget-hidden', hidden && editing);
  });
};

// Fetches and applies the stored layout (order + visibility) before Masonry initialises
const applyStoredLayout = async (grid: Element): Promise<void> => {
  try {
    const response = await fetch('./api/dashboard/layout', {
      method: 'GET',
      cache: 'no-cache',
      headers: { 'Content-Type': 'application/json' },
      redirect: 'follow',
      referrerPolicy: 'no-referrer',
    });

    if (!response.ok) {
      return;
    }

    const data: { config?: WidgetConfig[] } = await response.json();
    const config = data.config ?? [];

    if (config.length === 0) {
      return;
    }

    const widgetsByKey = new Map(getWidgets(grid).map((widget) => [widget.dataset.pmfWidget ?? '', widget]));

    for (const entry of config) {
      const widget = widgetsByKey.get(entry.key);
      if (!widget) {
        continue;
      }
      widget.dataset.pmfHidden = entry.visible ? 'false' : 'true';
      // Re-append in stored order; unconfigured widgets keep their template position
      grid.appendChild(widget);
    }

    applyVisibility(grid, false);
  } catch (error) {
    console.error('Could not load dashboard layout: ', error);
  }
};

// Serialises the current DOM order and visibility into a layout payload
const collectConfig = (grid: Element): WidgetConfig[] =>
  getWidgets(grid).map((widget, index) => ({
    key: widget.dataset.pmfWidget ?? '',
    position: index,
    visible: !isHidden(widget),
  }));

const postJson = async (url: string, body: Record<string, unknown>): Promise<boolean> => {
  try {
    const response = await fetch(url, {
      method: 'POST',
      cache: 'no-cache',
      headers: { 'Content-Type': 'application/json' },
      redirect: 'follow',
      referrerPolicy: 'no-referrer',
      body: JSON.stringify(body),
    });
    return response.ok;
  } catch (error) {
    console.error('Dashboard layout request failed: ', error);
    return false;
  }
};

const saveLayout = async (grid: Element, csrfToken: string): Promise<void> => {
  await postJson('./api/dashboard/layout', { csrfToken, config: collectConfig(grid) });
};

// Builds the per-widget edit toolbar (move up / move down / hide / show)
const buildControls = (widget: HTMLElement, onChange: () => void): HTMLElement => {
  const bar = document.createElement('div');
  bar.className = 'pmf-widget-controls btn-group btn-group-sm';

  const makeButton = (icon: string, title: string, handler: () => void): HTMLButtonElement => {
    const button = document.createElement('button');
    button.type = 'button';
    button.className = 'btn btn-light';
    button.title = title;
    button.innerHTML = `<i aria-hidden="true" class="bi ${icon}"></i>`;
    const label = document.createElement('span');
    label.className = 'visually-hidden';
    label.textContent = title;
    button.appendChild(label);
    button.addEventListener('click', handler);
    return button;
  };

  const moveUp = makeButton('bi-arrow-up', 'Move widget up', () => {
    const previous = widget.previousElementSibling;
    if (previous) {
      widget.parentElement?.insertBefore(widget, previous);
      onChange();
    }
  });

  const moveDown = makeButton('bi-arrow-down', 'Move widget down', () => {
    const next = widget.nextElementSibling;
    if (next) {
      widget.parentElement?.insertBefore(next, widget);
      onChange();
    }
  });

  const toggleHide = makeButton('bi-eye-slash', 'Hide or show widget', () => {
    widget.dataset.pmfHidden = isHidden(widget) ? 'false' : 'true';
    onChange();
  });

  bar.append(moveUp, moveDown, toggleHide);
  return bar;
};

export const handleDashboardLayout = async (): Promise<void> => {
  const grid = document.querySelector<HTMLElement>('.masonry-grid');
  const toggle = document.getElementById('pmf-dashboard-edit-toggle');
  const resetButton = document.getElementById('pmf-dashboard-reset');

  if (!grid || !toggle || !resetButton) {
    return;
  }

  const csrfToken = grid.dataset.pmfCsrfToken ?? '';

  await applyStoredLayout(grid);

  const masonry = new Masonry(grid, { percentPosition: true });
  const relayout = (): void => {
    masonry.reloadItems?.();
    masonry.layout?.();
  };

  // Async widget content (charts, news) changes card heights — relayout once settled
  window.addEventListener('load', relayout);

  let editing = false;
  const editLabel = toggle.querySelector('.pmf-dashboard-edit-label');
  const customizeText = toggle.dataset.pmfEditLabel ?? 'Customize';
  const doneText = toggle.dataset.pmfDoneLabel ?? 'Done';

  const refresh = (): void => {
    applyVisibility(grid, editing);
    getWidgets(grid).forEach((widget) => {
      widget.querySelector('.pmf-widget-controls')?.remove();
      if (editing) {
        const card = widget.querySelector('.card');
        card?.insertBefore(buildControls(widget, refresh), card.firstChild);
      }
    });
    relayout();
  };

  toggle.addEventListener('click', async (): Promise<void> => {
    editing = !editing;
    grid.classList.toggle('pmf-dashboard-editing', editing);
    resetButton.classList.toggle('d-none', !editing);
    toggle.classList.toggle('btn-outline-secondary', !editing);
    toggle.classList.toggle('btn-success', editing);
    if (editLabel) {
      editLabel.textContent = editing ? doneText : customizeText;
    }
    refresh();

    if (!editing) {
      await saveLayout(grid, csrfToken);
    }
  });

  resetButton.addEventListener('click', async (): Promise<void> => {
    const ok = await postJson('./api/dashboard/layout/reset', { csrfToken });
    if (ok) {
      window.location.reload();
    }
  });
};
