/**
 * Admin dashboard
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne
 * @copyright 2020-2025 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2020-04-22
 */

import Masonry from 'masonry-layout';
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

    const themeObserver = new MutationObserver((mutations) => {
      for (const m of mutations) {
        if (m.type === 'attributes' && m.attributeName === 'data-bs-theme') {
          applyVisitorTheme();
        }
      }
    });
    themeObserver.observe(document.documentElement, { attributes: true, attributeFilter: ['data-bs-theme'] });

    const getData = async (): Promise<void> => {
      try {
        const response = await fetch('./api/dashboard/visits', {
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

          visits.forEach((visit) => {
            visitorChart.data.labels!.push(visit.date);
            (visitorChart.data.datasets[0].data as number[]).push(visit.number);
          });

          visitorChart.update();
        }
      } catch (error) {
        console.error('Request failure: ', error);
      }
    };

    await getData();
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
      doughnutChart.options.scales!.x = {
        display: true,
        title: { display: false, color: c.bodyColor },
        ticks: { display: false, color: c.bodyColor },
        grid: { color: c.gridColor },
      };
      doughnutChart.options.scales!.y = {
        display: true,
        title: { display: false, color: c.bodyColor },
        ticks: { color: c.bodyColor },
        grid: { color: c.gridColor },
      };
      doughnutChart.update('none');
    };

    const themeObserver = new MutationObserver((mutations) => {
      for (const m of mutations) {
        if (m.type === 'attributes' && m.attributeName === 'data-bs-theme') {
          applyBarTheme();
        }
      }
    });
    themeObserver.observe(document.documentElement, { attributes: true, attributeFilter: ['data-bs-theme'] });

    const dynamicColors = (): string => {
      const r: number = Math.floor(Math.random() * 255);
      const g: number = Math.floor(Math.random() * 255);
      const b: number = Math.floor(Math.random() * 255);
      return 'rgb(' + r + ',' + g + ',' + b + ')';
    };

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

          topTen.forEach((faq: { question: string; visits: number }): void => {
            doughnutChart.data.labels!.push(faq.question);
            (doughnutChart.data.datasets[0].data as number[]).push(faq.visits);
            colors.push(dynamicColors());
          });

          doughnutChart.update();
        }
      } catch (error) {
        console.error('Request failure: ', error);
      }
    };

    await getData();
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

window.onload = (): void => {
  const masonryElement = document.querySelector('.masonry-grid') as HTMLElement;
  if (masonryElement) {
    new Masonry(masonryElement, { percentPosition: true });
  }
};
