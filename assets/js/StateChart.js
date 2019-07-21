'use strict';

import $ from "jquery";
import Chart from 'chart.js';


class StateChart {

    initialiseData() {
        this.fetchData(this.receiveData.bind(this));
        window.setInterval(this.refreshData.bind(this), 5000);
    }

    refreshData() {
        $('#loading').show();
        this.fetchData(this.receiveRefreshData.bind(this));
    }

    fetchData(successCallback) {
        fetch('/chart/data')
            .then((response) => response.json())
            .then((resp) => {
                successCallback(resp);
            })
            .catch((err) => {
                console.info(err);
            });
    }

    receiveRefreshData(data) {
        let dataSets = this.constructor.parseReceivedData(data);
        this.chart.data.datasets.forEach((dataset) => {
            dataset.data = dataSets.datasets[0].data;
        });
        this.chart.update();
       $('#loading').hide();
    }

    receiveData(data) {
        let config = this.chartConfig(data),
            ctx = document.getElementById('StateChart').getContext('2d');
        this.chart = new Chart(ctx, config);
        $('#loading').hide();
    }

    chartConfig(data) {
        return {
            type: 'line',
            data: this.constructor.parseReceivedData(data),
            options: {
                responsive: true,
                title: {
                    display: false,
                    text: 'Users by state'
                },
                tooltips: {
                    mode: 'index',
                    intersect: false,
                },
                hover: {
                    mode: 'nearest',
                    intersect: true
                },
                scales: {
                    xAxes: [{
                        display: true,
                        scaleLabel: {
                            display: true,
                            labelString: 'State'
                        }
                    }],
                    yAxes: [{
                        display: true,
                        scaleLabel: {
                            display: true,
                            labelString: 'Count'
                        }
                    }]
                },
                bezierCurve: false
            }
        }
    }

    static handleError(error) {
        console.info(error);
    }

    static parseReceivedData(data) { console.info(data);
        return {
            labels: data.labels,
            datasets: [{
                label: 'Number users',
                backgroundColor: '#ff0000',
                borderColor: '#ff0000',
                data: data.values,
                fill: false,
                lineTension: 0,
            }]
        };
    }
}

export default StateChart;