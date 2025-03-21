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

export const renderVisitorCharts = async (): Promise<void> => {
  const context = document.getElementById('pmf-chart-visits') as HTMLCanvasElement | null;

  if (context) {
    Chart.register(...registerables);

    const visitorChart = new Chart(context, {
      type: 'line',
      data: {
        labels: [] as string[],
        datasets: [
          {
            data: [] as number[],
            borderWidth: 1,
            borderColor: '#212529',
            backgroundColor: '#02b875',
            label: ' Visitors',
            pointStyle: 'circle',
            pointRadius: 4,
            pointHoverRadius: 8,
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
        scales: {
          x: {
            display: true,
            title: {
              display: false,
            },
          },
          y: {
            display: true,
            title: {
              display: true,
              text: 'Visitors',
            },
          },
        },
      },
    });

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
            visitorChart.data.datasets[0].data.push(visit.number);
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

    let colors: string[] = [];

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
          },
        },
        maintainAspectRatio: false,
        scales: {
          x: {
            display: true,
            title: {
              display: false,
            },
            ticks: {
              display: false,
            },
          },
          y: {
            display: true,
            title: {
              display: false,
            },
          },
        },
      },
    });

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
            doughnutChart.data.datasets[0].data.push(faq.visits);
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
        throw new Error('Network response was not ok');
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
    new Masonry(masonryElement, { columnWidth: 0 });
  }
};
