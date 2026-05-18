/**
 * Admin dashboard
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne
 * @copyright 2020-2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2020-04-22
 */

import { Chart, registerables } from 'chart.js';
import { getRemoteHashes, verifyHashes } from './api';
import { addElement, TranslationService } from '../../../assets/src/utils';

// Read current Bootstrap theme colors from CSS variables (light/dark aware)
const getThemeColors = () => {
  const styles: CSSStyleDeclaration = getComputedStyle(document.documentElement);
  const primary: string = (styles.getPropertyValue('--bs-primary') || '#0d6efd').trim();
  const bodyColor: string = (styles.getPropertyValue('--bs-body-color') || '#212529').trim();
  const bodyColorRgb: string = (styles.getPropertyValue('--bs-body-color-rgb') || '33, 37, 41').trim();
  const borderColorVar: string = styles.getPropertyValue('--bs-border-color').trim();
  const gridColor: string = borderColorVar !== '' ? borderColorVar : `rgba(${bodyColorRgb}, 0.4)`;
  const tooltipBg: string = (styles.getPropertyValue('--bs-tertiary-bg') || `rgba(${bodyColorRgb}, 0.85)`).trim();
  return { primary, bodyColor, gridColor, tooltipBg } as const;
};

// Run the callback whenever the Bootstrap theme (light/dark) changes
const onThemeChange = (callback: () => void): void => {
  const observer = new MutationObserver((mutations) => {
    if (mutations.some((m) => m.type === 'attributes' && m.attributeName === 'data-bs-theme')) {
      callback();
    }
  });
  observer.observe(document.documentElement, { attributes: true, attributeFilter: ['data-bs-theme'] });
};

// Remove a chart's loading skeleton once the chart has rendered
const removeChartSkeleton = (id: string): void => {
  document.getElementById(id)?.remove();
};

// Theme-aware palette for chart bars — readable in both light and dark mode
const getChartPalette = (): string[] => {
  const styles: CSSStyleDeclaration = getComputedStyle(document.documentElement);
  const read = (name: string, fallback: string): string => {
    const value: string = styles.getPropertyValue(name).trim();
    return value !== '' ? value : fallback;
  };
  return [
    read('--bs-primary', '#0d6efd'),
    read('--bs-success', '#198754'),
    read('--bs-info', '#0dcaf0'),
    read('--bs-warning', '#ffc107'),
    read('--bs-danger', '#dc3545'),
    read('--bs-purple', '#6f42c1'),
    read('--bs-teal', '#20c997'),
    read('--bs-orange', '#fd7e14'),
    read('--bs-pink', '#d63384'),
    read('--bs-indigo', '#6610f2'),
  ];
};

export const renderVisitorCharts = async (): Promise<void> => {
  const context = document.getElementById('pmf-chart-visits') as HTMLCanvasElement | null;

  if (context) {
    Chart.register(...registerables);

    const colors = getThemeColors();

    const visitorChart = new Chart(context, {
      type: 'line',
      data: {
        labels: [] as string[],
        datasets: [
          {
            data: [] as number[],
            borderWidth: 2,
            borderColor: colors.primary,
            backgroundColor: colors.primary,
            label: ' Visitors',
            pointStyle: 'circle',
            pointRadius: 4,
            pointHoverRadius: 8,
            pointBackgroundColor: colors.primary,
            pointBorderColor: colors.primary,
            fill: false,
            cubicInterpolationMode: 'monotone',
            tension: 0.4,
          },
        ],
      },
      options: {
        responsive: true,
        interaction: {
          intersect: false,
        },
        maintainAspectRatio: false,
        plugins: {
          legend: {
            display: false,
            labels: {
              color: colors.bodyColor,
            },
          },
          tooltip: {
            backgroundColor: colors.tooltipBg,
            titleColor: colors.bodyColor,
            bodyColor: colors.bodyColor,
            borderColor: colors.gridColor,
            borderWidth: 1,
          },
        },
        scales: {
          x: {
            display: true,
            title: {
              display: false,
              color: colors.bodyColor,
            },
            ticks: {
              color: colors.bodyColor,
            },
            grid: {
              color: colors.gridColor,
            },
          },
          y: {
            display: true,
            title: {
              display: true,
              text: 'Visitors',
              color: colors.bodyColor,
            },
            ticks: {
              color: colors.bodyColor,
            },
            grid: {
              color: colors.gridColor,
            },
          },
        },
      },
    });

    // Update chart colors on theme change
    const applyVisitorTheme = (): void => {
      const c = getThemeColors();
      const ds = visitorChart.data.datasets[0];
      ds.borderColor = c.primary;
      ds.pointBackgroundColor = c.primary;
      ds.pointBorderColor = c.primary;
      visitorChart.options.plugins = visitorChart.options.plugins || {};
      visitorChart.options.plugins.legend = { display: false, labels: { color: c.bodyColor } };
      visitorChart.options.plugins.tooltip = {
        backgroundColor: c.tooltipBg,
        titleColor: c.bodyColor,
        bodyColor: c.bodyColor,
        borderColor: c.gridColor,
        borderWidth: 1,
      };
      visitorChart.options.scales = visitorChart.options.scales || {};
      visitorChart.options.scales.x = {
        display: true,
        title: { display: false, color: c.bodyColor },
        ticks: { color: c.bodyColor },
        grid: { color: c.gridColor },
      };
      visitorChart.options.scales.y = {
        display: true,
        title: { display: true, text: 'Visitors', color: c.bodyColor },
        ticks: { color: c.bodyColor },
        grid: { color: c.gridColor },
      };
      visitorChart.update('none');
    };

    onThemeChange(applyVisitorTheme);

    // Guards against out-of-order responses overwriting the chart with stale data
    let requestId = 0;

    const getData = async (days: number): Promise<void> => {
      const currentRequestId = ++requestId;
      try {
        const response = await fetch(`./api/dashboard/visits?days=${days}`, {
          method: 'GET',
          cache: 'no-cache',
          headers: {
            'Content-Type': 'application/json',
          },
          redirect: 'follow',
          referrerPolicy: 'no-referrer',
        });

        if (response.status === 200) {
          const visits: { date: string; number: number }[] = await response.json();

          // A newer range request has been started — discard this stale response
          if (currentRequestId !== requestId) {
            return;
          }

          visitorChart.data.labels = [];
          visitorChart.data.datasets[0].data = [];

          visits.forEach((visit) => {
            visitorChart.data.labels?.push(visit.date);
            (visitorChart.data.datasets[0].data as number[]).push(visit.number);
          });

          visitorChart.update();
        }
      } catch (error) {
        console.error('Request failure: ', error);
      }
    };

    // Wire the 7 / 30 / 90 day range switcher
    const rangeGroup = document.getElementById('pmf-visits-range');
    if (rangeGroup) {
      rangeGroup.querySelectorAll<HTMLButtonElement>('button[data-pmf-range]').forEach((button) => {
        button.addEventListener('click', async (): Promise<void> => {
          rangeGroup.querySelectorAll('button').forEach((other) => other.classList.remove('active'));
          button.classList.add('active');
          await getData(Number(button.dataset.pmfRange ?? '30'));
        });
      });
    }

    await getData(30);
    removeChartSkeleton('pmf-chart-visits-skeleton');
  }
};

export const renderTopTenCharts = async (): Promise<void> => {
  const context = document.getElementById('pmf-chart-topten') as HTMLCanvasElement | null;

  if (context) {
    Chart.register(...registerables);

    const colorsForBars = getThemeColors();

    const colors: string[] = [];

    const doughnutChart = new Chart(context, {
      type: 'bar',
      data: {
        labels: [] as string[],
        datasets: [
          {
            data: [] as number[],
            borderWidth: 1,
            borderColor: 'white',
            backgroundColor: colors,
            label: ' Visitors',
          },
        ],
      },
      options: {
        responsive: true,
        interaction: {
          intersect: false,
        },
        plugins: {
          legend: {
            display: false,
            labels: {
              color: colorsForBars.bodyColor,
            },
          },
        },
        maintainAspectRatio: false,
        scales: {
          x: {
            display: true,
            title: {
              display: false,
              color: colorsForBars.bodyColor,
            },
            ticks: {
              display: false,
              color: colorsForBars.bodyColor,
            },
            grid: {
              color: colorsForBars.gridColor,
            },
          },
          y: {
            display: true,
            title: {
              display: false,
              color: colorsForBars.bodyColor,
            },
            ticks: {
              color: colorsForBars.bodyColor,
            },
            grid: {
              color: colorsForBars.gridColor,
            },
          },
        },
      },
    });

    const applyBarTheme = (): void => {
      const c = getThemeColors();
      if (!doughnutChart.options.plugins) doughnutChart.options.plugins = {};
      doughnutChart.options.plugins.legend = { display: false, labels: { color: c.bodyColor } };
      if (!doughnutChart.options.scales) doughnutChart.options.scales = {};
      doughnutChart.options.scales.x = {
        display: true,
        title: { display: false, color: c.bodyColor },
        ticks: { display: false, color: c.bodyColor },
        grid: { color: c.gridColor },
      };
      doughnutChart.options.scales.y = {
        display: true,
        title: { display: false, color: c.bodyColor },
        ticks: { color: c.bodyColor },
        grid: { color: c.gridColor },
      };
      doughnutChart.update('none');
    };

    onThemeChange(applyBarTheme);

    const palette: string[] = getChartPalette();

    const getData = async (): Promise<void> => {
      try {
        const response = await fetch('./api/dashboard/topten', {
          method: 'GET',
          cache: 'no-cache',
          headers: {
            'Content-Type': 'application/json',
          },
          redirect: 'follow',
          referrerPolicy: 'no-referrer',
        });

        if (response.status === 200) {
          const topTen: { question: string; visits: number }[] = await response.json();

          topTen.forEach((faq: { question: string; visits: number }, index: number): void => {
            if (doughnutChart.data.labels) {
              doughnutChart.data.labels.push(faq.question);
            }
            (doughnutChart.data.datasets[0].data as number[]).push(faq.visits);
            colors.push(palette[index % palette.length]);
          });

          doughnutChart.update();
        }
      } catch (error) {
        console.error('Request failure: ', error);
      }
    };

    await getData();
    removeChartSkeleton('pmf-chart-topten-skeleton');
  }
};

export const getLatestVersion = async (): Promise<void> => {
  const loader = document.getElementById('version-loader') as HTMLDivElement;
  const versionText = document.getElementById('phpmyfaq-latest-version') as HTMLDivElement;

  if (loader && versionText) {
    loader.classList.remove('d-none');

    try {
      const response = await fetch('./api/dashboard/versions', {
        method: 'GET',
        cache: 'no-cache',
        headers: {
          'Content-Type': 'application/json',
        },
        redirect: 'follow',
        referrerPolicy: 'no-referrer',
      });

      if (response.ok) {
        const version = await response.json();
        loader.classList.add('d-none');

        if (version.success) {
          versionText.insertAdjacentElement(
            'afterend',
            addElement('div', {
              classList: 'alert alert-success',
              innerText: version.success,
            })
          );
        }
        if (version.warning) {
          versionText.insertAdjacentElement(
            'afterend',
            addElement('div', {
              classList: 'alert alert-danger',
              innerText: version.warning,
            })
          );
        }
        if (version.error) {
          versionText.insertAdjacentElement(
            'afterend',
            addElement('div', {
              classList: 'alert alert-danger',
              innerText: version.error,
            })
          );
        }
      } else {
        loader.classList.add('d-none');
        versionText.insertAdjacentElement(
          'afterend',
          addElement('div', {
            classList: 'alert alert-danger',
            innerText: 'Network response was not ok',
          })
        );
      }
    } catch (error) {
      const errorMessage = (error as Error).message || 'Unknown error';
      loader.classList.add('d-none');
      versionText.insertAdjacentElement(
        'afterend',
        addElement('div', {
          classList: 'alert alert-danger',
          innerText: errorMessage,
        })
      );
    }
  }
};

export const parseNewsMarkdown = (markdown: string): string => {
  const baseUrl = 'https://www.phpmyfaq.de';

  let html = markdown
    // Escape HTML entities first to prevent XSS
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;');

  // Parse markdown links [text](url) — resolve relative URLs against base
  html = html.replace(/\[([^\]]+)\]\(([^)]+)\)/g, (_match: string, text: string, url: string) => {
    const resolvedUrl =
      url.startsWith('http://') || url.startsWith('https://')
        ? url
        : `${baseUrl}${url.startsWith('/') ? '' : '/'}${url}`;
    return `<a href="${resolvedUrl}" target="_blank" rel="noopener noreferrer">${text}</a>`;
  });

  // Bold **text**
  html = html.replace(/\*\*([^*]+)\*\*/g, '<strong>$1</strong>');

  // Italic *text*
  html = html.replace(/\*([^*]+)\*/g, '<em>$1</em>');

  return html;
};

export const fetchRecentNews = async (): Promise<void> => {
  const container = document.getElementById('pmf-recent-news') as HTMLDivElement | null;
  const loader = document.getElementById('pmf-news-loader') as HTMLDivElement | null;

  if (!container) {
    return;
  }

  if (loader) {
    loader.classList.remove('d-none');
  }

  try {
    const response = await fetch('./api/dashboard/news', {
      method: 'GET',
      cache: 'no-cache',
      headers: {
        'Content-Type': 'application/json',
      },
      redirect: 'follow',
      referrerPolicy: 'no-referrer',
    });

    if (loader) {
      loader.classList.add('d-none');
    }

    if (response.ok) {
      const data: { news?: { date: string; content: string }[] } = await response.json();
      const news = (data.news ?? []).slice(0, 5);

      if (news.length === 0) {
        container.innerHTML = '<p class="text-muted mb-0">No recent news available.</p>';
        return;
      }

      const list = document.createElement('ul');
      list.className = 'list-unstyled';

      for (const item of news) {
        const li = document.createElement('li');
        li.className = 'mb-2';

        const dateSpan = document.createElement('small');
        dateSpan.className = 'text-muted d-block border-bottom mb-2';
        dateSpan.textContent = item.date;

        const contentSpan = document.createElement('span');
        contentSpan.innerHTML = parseNewsMarkdown(item.content);

        li.appendChild(dateSpan);
        li.appendChild(contentSpan);
        list.appendChild(li);
      }

      container.appendChild(list);
    } else {
      container.innerHTML = '<p class="text-muted mb-0">Could not load news.</p>';
    }
  } catch {
    if (loader) {
      loader.classList.add('d-none');
    }
    container.innerHTML = '<p class="text-muted mb-0">Could not load news.</p>';
  }
};

export const handleVerificationModal = async (): Promise<void> => {
  const verificationModal = document.getElementById('verificationModal') as HTMLDivElement;
  const Translator = new TranslationService();
  if (verificationModal) {
    verificationModal.addEventListener('show.bs.modal', async (): Promise<void> => {
      const spinner = document.getElementById('pmf-verification-spinner') as HTMLDivElement;
      const version = verificationModal.getAttribute('data-pmf-current-version') as string;
      const updates = document.getElementById('pmf-verification-updates') as HTMLDivElement;
      const language: string = document.documentElement.lang;
      await Translator.loadTranslations(language);
      if (spinner && updates && version) {
        spinner.classList.remove('d-none');
        updates.innerText = Translator.translate('msgFetchingHashes');
        const remoteHashes = (await getRemoteHashes(version)) as Record<string, string>;
        updates.innerText = Translator.translate('msgCheckHashes');
        const issues = await verifyHashes(remoteHashes);

        if (typeof issues !== 'object') {
          console.error('Invalid JSON data provided.');
        }

        const ul = document.createElement('ul') as HTMLUListElement;
        for (const [filename, hashValue] of Object.entries(issues)) {
          const li = document.createElement('li') as HTMLLIElement;
          li.textContent = `${Translator.translate('msgAttachmentsFilename')}: ${filename}, Hash: ${hashValue}`;
          ul.appendChild(li);
        }

        updates.appendChild(ul);
        spinner.classList.add('d-none');
      }
    });
  }
};

export const fetchContentHealth = async (): Promise<void> => {
  const container = document.getElementById('pmf-content-health') as HTMLDivElement | null;

  if (!container) {
    return;
  }

  try {
    const response = await fetch('./api/dashboard/content-health', {
      method: 'GET',
      cache: 'no-cache',
      headers: {
        'Content-Type': 'application/json',
      },
      redirect: 'follow',
      referrerPolicy: 'no-referrer',
    });

    if (!response.ok) {
      container.innerHTML = '<p class="text-muted mb-0">Could not load content health.</p>';
      return;
    }

    const data: { orphaned: number; stale: number } = await response.json();
    const items: { icon: string; label: string; count: number }[] = [
      { icon: 'bi-folder-x', label: 'FAQs without a category', count: data.orphaned },
      { icon: 'bi-clock-history', label: 'FAQs not updated in 6 months', count: data.stale },
    ];

    const list = document.createElement('ul');
    list.className = 'list-unstyled mb-0';

    for (const item of items) {
      const li = document.createElement('li');
      li.className = 'd-flex justify-content-between align-items-center mb-2';

      const label = document.createElement('span');
      const icon = document.createElement('i');
      icon.className = `bi ${item.icon} me-1`;
      label.appendChild(icon);
      label.appendChild(document.createTextNode(item.label));

      const badge = document.createElement('span');
      badge.className = item.count > 0 ? 'badge bg-warning text-dark' : 'badge bg-success';
      badge.textContent = String(item.count);

      li.appendChild(label);
      li.appendChild(badge);
      list.appendChild(li);
    }

    container.replaceChildren(list);
  } catch {
    container.innerHTML = '<p class="text-muted mb-0">Could not load content health.</p>';
  }
};

export const fetchPopularSearches = async (): Promise<void> => {
  const container = document.getElementById('pmf-popular-searches') as HTMLDivElement | null;

  if (!container) {
    return;
  }

  try {
    const response = await fetch('./api/dashboard/searches', {
      method: 'GET',
      cache: 'no-cache',
      headers: {
        'Content-Type': 'application/json',
      },
      redirect: 'follow',
      referrerPolicy: 'no-referrer',
    });

    if (!response.ok) {
      container.innerHTML = '<p class="text-muted mb-0">Could not load searches.</p>';
      return;
    }

    const searches: { searchterm: string; number: number }[] = await response.json();

    if (searches.length === 0) {
      container.innerHTML = '<p class="text-muted mb-0">No searches recorded yet.</p>';
      return;
    }

    const list = document.createElement('ul');
    list.className = 'list-unstyled mb-0';

    for (const search of searches) {
      const li = document.createElement('li');
      li.className = 'd-flex justify-content-between align-items-center mb-2';

      const term = document.createElement('span');
      term.className = 'text-truncate me-2';
      // textContent — search terms are user input and must not be rendered as HTML
      term.textContent = search.searchterm;

      const badge = document.createElement('span');
      badge.className = 'badge bg-secondary';
      badge.textContent = String(search.number);

      li.appendChild(term);
      li.appendChild(badge);
      list.appendChild(li);
    }

    container.replaceChildren(list);
  } catch {
    container.innerHTML = '<p class="text-muted mb-0">Could not load searches.</p>';
  }
};
