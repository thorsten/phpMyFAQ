/**
 * Admin dashboard
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @package phpMyFAQ
 * @author Thorsten Rinne
 * @copyright 2020 phpMyFAQ Team
 * @license http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link https://www.phpmyfaq.de
 * @since 2020-04-22
 */

import Chart from 'chart.js';

export const renderVisitorCharts = () => {
  const ctx = document.getElementById('pmf-chart-visits');

  const visitorChart = new Chart(ctx, {
    type: 'bar',
    data: {
      labels: [],
      datasets: [
        {
          data: [],
          borderWidth: 1,
          borderColor: 'orange',
          label: 'Visitors',
        },
      ],
    },
    options: {
      responsive: true,
      legend: {
        display: false,
      },
      scales: {
        yAxes: [
          {
            ticks: {
              beginAtZero: true,
            },
          },
        ],
      },
    },
  });

  // this post id drives the example data
  let postId = 1;

  // logic to get new data
  const getData = function () {
    $.ajax({
      url: 'https://jsonplaceholder.typicode.com/posts/' + postId + '/comments',
      success: function (data) {
        // process your data to pull out what you plan to use to update the chart
        // e.g. new label and a new data point

        // add new label and data point to chart's underlying data structures
        visitorChart.data.labels.push('Post ' + postId++);
        visitorChart.data.datasets[0].data.push(42);

        // re-render the chart
        visitorChart.update();
      },
    });
  };

  // get new data every 3 seconds
  setInterval(getData, 3000);
};
