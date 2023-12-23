/**
 * Admin dashboard
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne
 * @copyright 2020-2023 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2020-04-22
 */

import { BarController, BarElement, Chart, LinearScale, CategoryScale, Title, registerables } from 'chart.js';
import Masonry from 'masonry-layout';
import { addElement } from '../../../assets/src/utils';

window.onload = () => {
  const masonryElement = document.querySelector('.masonry-grid');

  if (masonryElement) {
    new Masonry(masonryElement, { columnWidth: 0 });
  }
};

export const renderVisitorCharts = async () => {
  const context = document.getElementById('pmf-chart-visits');

  if (context) {
    Chart.register(BarController, BarElement, LinearScale, Title, CategoryScale);

    const visitorChart = new Chart(context, {
      type: 'bar',
      data: {
        labels: [],
        datasets: [
          {
            data: [],
            borderWidth: 1,
            borderColor: 'black',
            backgroundColor: '#1cc88a',
            label: 'Visitors',
          },
        ],
      },
      options: {
        responsive: true,
        scales: {
          x: {
            title: {
              display: false,
            },
          },
          y: {
            title: {
              display: true,
              text: 'Visitors',
            },
            ticks: {
              beginAtZero: true,
            },
          },
        },
      },
    });

    const getData = async () => {
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
          const visits = await response.json();

          visits.forEach((visit) => {
            visitorChart.data.labels.push(visit.date);
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

export const renderTopTenCharts = async () => {
  const context = document.getElementById('pmf-chart-topten');

  if (context) {
    Chart.register(...registerables);

    let colors = [];

    const doughnutChart = new Chart(context, {
      type: 'doughnut',
      data: {
        labels: [],
        datasets: [
          {
            data: [],
            borderWidth: 1,
            borderColor: 'white',
            backgroundColor: colors,
            label: 'Visitors',
          },
        ],
      },
    });

    const dynamicColors = () => {
      const r = Math.floor(Math.random() * 255);
      const g = Math.floor(Math.random() * 255);
      const b = Math.floor(Math.random() * 255);
      return 'rgb(' + r + ',' + g + ',' + b + ')';
    };

    const getData = async () => {
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
          const topTen = await response.json();

          topTen.forEach((faq) => {
            doughnutChart.data.labels.push(faq.question);
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

export const getLatestVersion = () => {
  const loader = document.getElementById('version-loader');
  const versionText = document.getElementById('phpmyfaq-latest-version');

  if (loader) {
    loader.classList.remove('d-none');
    fetch('./api/dashboard/versions', {
      method: 'GET',
      cache: 'no-cache',
      headers: {
        'Content-Type': 'application/json',
      },
      redirect: 'follow',
      referrerPolicy: 'no-referrer',
    })
      .then(async (response) => {
        if (response.ok) {
          const version = await response.json();
          loader.classList.add('d-none');
          versionText.insertAdjacentElement(
            'afterend',
            addElement('div', {
              classList: 'alert alert-success',
              innerText: version.success,
            })
          );
        } else {
          throw new Error('Network response was not ok: ', { cause: { response } });
        }
      })
      .catch(async (error) => {
        const errorMessage = await error.cause.response;
        console.log(errorMessage);
        loader.classList.add('d-none');
        versionText.insertAdjacentElement(
          'afterend',
          addElement('div', {
            classList: 'alert alert-danger',
            innerText: errorMessage,
          })
        );
      });
  }
};
