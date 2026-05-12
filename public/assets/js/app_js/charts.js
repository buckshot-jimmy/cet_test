// Set new default font family and font color to mimic Bootstrap's default styling
Chart.defaults.global.defaultFontFamily = 'Nunito', '-apple-system,system-ui,BlinkMacSystemFont,"Segoe UI",Roboto,"Helvetica Neue",Arial,sans-serif';
Chart.defaults.global.defaultFontColor = '#858796';

function number_format(number, decimals, dec_point, thousands_sep) {
    // *     example: number_format(1234.56, 2, ',', ' ');
    // *     return: '1 234,56'
    number = (number + '').replace(',', '').replace(' ', '');
    var n = !isFinite(+number) ? 0 : +number,
        prec = !isFinite(+decimals) ? 0 : Math.abs(decimals),
        sep = (typeof thousands_sep === 'undefined') ? ',' : thousands_sep,
        dec = (typeof dec_point === 'undefined') ? '.' : dec_point,
        s = '',
        toFixedFix = function(n, prec) {
            var k = Math.pow(10, prec);
            return '' + Math.round(n * k) / k;
        };
    // Fix for IE parseFloat(0.55).toFixed(0) = 0;
    s = (prec ? toFixedFix(n, prec) : '' + Math.round(n)).split('.');
    if (s[0].length > 3) {
        s[0] = s[0].replace(/\B(?=(?:\d{3})+(?!\d))/g, sep);
    }
    if ((s[1] || '').length < prec) {
        s[1] = s[1] || '';
        s[1] += new Array(prec - s[1].length + 1).join('0');
    }
    return s.join(dec);
}

const SHOW_CHART_MEDIC = "1";

let dataSetFirst = [
    {
        label: "Numar consultatii cabinet",
        lineTension: 0,
        backgroundColor: "rgba(78, 115, 223, 0.05)",
        borderColor: "rgba(78, 115, 223, 1)",
        pointRadius: 3,
        pointBackgroundColor: "rgba(78, 115, 223, 1)",
        pointBorderColor: "rgba(78, 115, 223, 1)",
        pointHoverRadius: 3,
        pointHoverBackgroundColor: "rgba(78, 115, 223, 1)",
        pointHoverBorderColor: "rgba(78, 115, 223, 1)",
        pointHitRadius: 10,
        pointBorderWidth: 2,
        data: []
    }
];

if (document.getElementById("show_chart_medic").getAttribute('value') === SHOW_CHART_MEDIC) {
    dataSetFirst.push(
        {
            label: "Numar consultatii",
            lineTension: 0,
            backgroundColor: "rgba(78, 115, 223, 0.05)",
            borderColor: "red",
            pointRadius: 3,
            pointBackgroundColor: "red",
            pointBorderColor: "red",
            pointHoverRadius: 3,
            pointHoverBackgroundColor: "red",
            pointHoverBorderColor: "red",
            pointHitRadius: 10,
            pointBorderWidth: 2,
            data: []
        }
    );
}

let ctx = document.getElementById("incasariPeLuni");
let myLineChart = new Chart(ctx, {
    type: 'line',
    data: {
        labels: ["Ianuarie", "Februarie", "Martie", "Aprilie", "Mai", "Iunie", "Iulie", "August", "Septembrie", "Octombrie", "Noiembrie", "Decembrie"],
        datasets: dataSetFirst
    },
    options: {
        maintainAspectRatio: false,
        layout: {
            padding: {
                left: 10,
                right: 25,
                top: 25,
                bottom: 0
            }
        },
        scales: {
            xAxes: [{
                time: {
                    unit: 'date'
                },
                gridLines: {
                    display: false,
                    drawBorder: false
                },
                ticks: {
                    maxTicksLimit: 7
                }
            }],
            yAxes: [{
                ticks: {
                    maxTicksLimit: 5,
                    padding: 10,
                    // Include a dollar sign in the ticks
                    callback: function(value, index, values) {
                        return number_format(value);
                    },
                    suggestedMin: 0
                },
                gridLines: {
                    color: "rgb(234, 236, 244)",
                    zeroLineColor: "rgb(234, 236, 244)",
                    drawBorder: false,
                    borderDash: [2],
                    zeroLineBorderDash: [2]
                }
            }],
        },
        legend: {
            display: true
        },
        tooltips: {
            backgroundColor: "rgb(255,255,255)",
            bodyFontColor: "#858796",
            titleMarginBottom: 10,
            titleFontColor: '#6e707e',
            titleFontSize: 14,
            borderColor: '#dddfeb',
            borderWidth: 1,
            xPadding: 15,
            yPadding: 15,
            displayColors: false,
            intersect: false,
            mode: 'index',
            caretPadding: 10,
            callbacks: {
                label: function(tooltipItem, chart) {
                    var datasetLabel = chart.datasets[tooltipItem.datasetIndex].label || '';
                    return datasetLabel + ':  ' + number_format(tooltipItem.yLabel);
                }
            }
        }
    }
});

populateChart(myLineChart, 'consultatii/get_consultatii_luni');

function populateChart(chart, url, data) {
    $.getJSON(url, data).done(function(response) {
        chart.data.labels = response.consultatii.luni;
        chart.data.datasets[0].data = response.consultatii.consultatii;
        if (document.getElementById("show_chart_medic").getAttribute('value') === SHOW_CHART_MEDIC) {
            chart.data.datasets[1].data = response.consultatiiMedic.consultatii;
            chart.data.datasets[1].label = "Numar consultatii - " + response.medic;
        }
        chart.update();
    });
}

if (document.getElementById("show_chart_medic").getAttribute('value') === SHOW_CHART_MEDIC) {
    let ctxM = document.getElementById("incasariMediciPeLuni");
    let myLineChartM = new Chart(ctxM, {
        type: 'line',
        data: {
            labels: ["Ianuarie", "Februarie", "Martie", "Aprilie", "Mai", "Iunie", "Iulie", "August", "Septembrie", "Octombrie", "Noiembrie", "Decembrie"],
            datasets: [
                {
                    label: "Valoare servicii",
                    lineTension: 0,
                    backgroundColor: "rgba(78, 115, 223, 0.05)",
                    borderColor: "rgba(78, 115, 223, 1)",
                    pointRadius: 3,
                    pointBackgroundColor: "rgba(78, 115, 223, 1)",
                    pointBorderColor: "rgba(78, 115, 223, 1)",
                    pointHoverRadius: 3,
                    pointHoverBackgroundColor: "rgba(78, 115, 223, 1)",
                    pointHoverBorderColor: "rgba(78, 115, 223, 1)",
                    pointHitRadius: 10,
                    pointBorderWidth: 2,
                    data: []
                },
                {
                    label: "Incasari",
                    lineTension: 0,
                    backgroundColor: "rgba(78, 115, 223, 0.05)",
                    borderColor: "red",
                    pointRadius: 3,
                    pointBackgroundColor: "red",
                    pointBorderColor: "red",
                    pointHoverRadius: 3,
                    pointHoverBackgroundColor: "red",
                    pointHoverBorderColor: "red",
                    pointHitRadius: 10,
                    pointBorderWidth: 2,
                    data: []
                }
            ]
        },
        options: {
            maintainAspectRatio: false,
            layout: {
                padding: {
                    left: 10,
                    right: 25,
                    top: 25,
                    bottom: 0
                }
            },
            scales: {
                xAxes: [{
                    time: {
                        unit: 'date'
                    },
                    gridLines: {
                        display: false,
                        drawBorder: false
                    },
                    ticks: {
                        maxTicksLimit: 7
                    }
                }],
                yAxes: [{
                    ticks: {
                        maxTicksLimit: 5,
                        padding: 10,
                        // Include a dollar sign in the ticks
                        callback: function (value, index, values) {
                            return 'RON ' + number_format(value);
                        },
                        suggestedMin: 0
                    },
                    gridLines: {
                        color: "rgb(234, 236, 244)",
                        zeroLineColor: "rgb(234, 236, 244)",
                        drawBorder: false,
                        borderDash: [2],
                        zeroLineBorderDash: [2]
                    }
                }],
            },
            legend: {
                display: true
            },
            tooltips: {
                backgroundColor: "rgb(255,255,255)",
                bodyFontColor: "#858796",
                titleMarginBottom: 10,
                titleFontColor: '#6e707e',
                titleFontSize: 14,
                borderColor: '#dddfeb',
                borderWidth: 1,
                xPadding: 15,
                yPadding: 15,
                displayColors: false,
                intersect: false,
                mode: 'index',
                caretPadding: 10,
                callbacks: {
                    label: function (tooltipItem, chart) {
                        var datasetLabel = chart.datasets[tooltipItem.datasetIndex].label || '';
                        return datasetLabel + ': RON ' + number_format(tooltipItem.yLabel);
                    }
                }
            }
        }
    });

    populateChartMedic(myLineChartM, 'consultatii/get_incasari_medici_luni');

    function populateChartMedic(chart, url, data) {
        $.getJSON(url, data).done(function (response) {
            chart.data.labels = response.incasariMedic.luni;
            chart.data.datasets[0].data = response.incasariMedic.incasari;
            chart.data.datasets[0].label = "Valoare servicii - " + response.medic;
            chart.data.datasets[1].data = response.incasariMedic.comision;
            chart.data.datasets[1].label = "Incasari - " + response.medic;
            chart.update();
        });
    }
}