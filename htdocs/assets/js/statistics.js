import { Chart, registerables } from 'chart.js';
Chart.register(...registerables); //https://github.com/sgratzl/chartjs-chart-wordcloud/issues/4#issuecomment-827304369


let chartInstance = null;


export default function statistics() {
    const element = document.getElementById('statisticsForm');
    if (!element) {
        return null;
    }

    initializeDatePickers();

    element.addEventListener('submit', function (event) {
        event.preventDefault();


        fetch(buildUrlFromFormData(this), {
            method: 'GET',
        })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Sorry, en error occurred: ' + response.statusText);
                }
                return response.text();
            })
            .then(html => {
                document.getElementById('statistics_result').innerHTML = html;
                addEventListenersToNewRows();
                plotBarchart('herbarium-total');

            })
            .catch(error => {
                console.error('Error:', error);
                alert('Sorry, en error occurred.');
            });

    });


}

function buildUrlFromFormData(form){
    const formData = new FormData(form);

    const params = new URLSearchParams();
    formData.forEach((value, key) => {
        params.append(key, value);
    });
    return form.action + '?' + params.toString();
}

function initializeDatePickers(){
    let options = {format: "yyyy-mm-dd"}
    let elems = document.querySelectorAll('.datepicker-custom');
    M.Datepicker.init(elems, options);
}

function addEventListenersToNewRows() {
    let rows = document.querySelectorAll('tr.herbarium');
    rows.forEach(function(row) {
        let firstFeatureCell = row.querySelector('.trigger');
        if (firstFeatureCell) {
            firstFeatureCell.addEventListener('click', function() {
                plotBarchart(row.id);
            });
        }
    });
}

function getHerbariumPlotValues(rowId) {
    let row = document.getElementById(rowId);

    if (!row) {
        console.error('Row not found');
        return;
    }
    let featureCells = row.getElementsByClassName('period');

    let values = [];
    for (let i = 0; i < featureCells.length; i++) {
        values.push(featureCells[i].textContent || featureCells[i].innerText);
    }

    return values;
}

function getHerbariumAcronym(rowId) {
    let row = document.getElementById(rowId);

    if (!row) {
        console.error('Row not found');
        return;
    }
    let triggerCell = row.querySelector('.trigger');

    if (!triggerCell) {
        console.error('No trigger cell found in row:', rowId);
        return null;
    }

    return triggerCell.textContent || triggerCell.innerText;
}

function plotBarchart(rowId) {
    let featureValues = getHerbariumPlotValues(rowId);
    let labels = featureValues.map((value, index) => 'Period ' + (index + 1));
    let data = featureValues;

    let ctx = document.getElementById('barChart').getContext('2d');

    if (chartInstance) {
        chartInstance.destroy();
    }

    chartInstance = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: getHerbariumAcronym(rowId),
                data: data,
                backgroundColor: 'rgba(54, 162, 235, 0.2)',
                borderColor: 'rgba(54, 162, 235, 1)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
}
