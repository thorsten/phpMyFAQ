/**
 * Admin dashboard
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @package phpMyFAQ
 * @author Thorsten Rinne
 * @copyright 2020-2021 phpMyFAQ Team
 * @license http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link https://www.phpmyfaq.de
 * @since 2020-04-22
 */

import { BarController, BarElement, Chart, LinearScale, CategoryScale, Title } from 'chart.js';

export const renderVisitorCharts = () => {
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

    const getData = () => {
      fetch('index.php?action=ajax&ajax=dashboard&ajaxaction=user-visits-last-30-days', {
        method: 'GET',
        cache: 'no-cache',
        headers: {
          'Content-Type': 'application/json',
        },
        redirect: 'follow',
        referrerPolicy: 'no-referrer',
      })
        .then(async (response) => {
          if (response.status === 200) {
            const visits = await response.json();

            visits.map((visit) => {
              visitorChart.data.labels.push(visit.date);
              visitorChart.data.datasets[0].data.push(visit.number);
            });

            visitorChart.update();
          }
        })
        .catch((error) => {
          console.log('Request failure: ', error);
        });
    };

    getData();
  }
};
