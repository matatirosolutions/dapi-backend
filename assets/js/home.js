
require('dropzone/dist/basic.css');
require('dropzone/dist/dropzone.css');

import StateChart from "./StateChart";
require('dropzone');

let chart = new StateChart();
chart.initialiseData();