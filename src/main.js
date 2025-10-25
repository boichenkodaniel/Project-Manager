import './style.css';
import { Chart, registerables } from 'chart.js';
import ChartDataLabels from 'chartjs-plugin-datalabels';

Chart.register(...registerables, ChartDataLabels);

const darkTheme = {
    responsive: true,
    plugins: {
        legend: {
            display: false
        },
        tooltip: {
            backgroundColor: '#2d2d2d',
            titleColor: '#ffffff',
            bodyColor: '#ffffff',
            borderColor: '#444',
            borderWidth: 1
        }
    },
    scales: {
        x: {
            grid: {
                display: false
            },
            ticks: {
                color: '#ffffff',
            }
        },
        y: {
            grid: {
                display: false
            },
            ticks: {
                color: '#ffffff',
                stepSize: 1,
                beginAtZero: true,
                display: false,
            }
        }
    }
};

const taskCtx = document.querySelector('[data-task-chart]').getContext('2d');
const taskChart = new Chart(taskCtx, {
    type: 'bar',
    data: {
        labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'],
        datasets: [{
            label: 'Tasks Completed',
            data: [3, 5, 4, 1, 3, 3],
            backgroundColor: '#cccccc',
            borderRadius: 6,
        }]
    },
    options: {
        ...darkTheme,
        datasets: {
            bar: {
                barPercentage: 0.9,
                categoryPercentage: 0.9,
            },
        },
        plugins: {
            ...darkTheme.plugins,
            tooltip: {
                ...darkTheme.plugins.tooltip,
                callbacks: {
                    label: function(context) {
                        return `${context.dataset.label}: ${context.raw}`;
                    }
                }
            },
            datalabels: {
                display: false
            }

        },
        scales: {
            ...darkTheme.scales,
            y: {
                ...darkTheme.scales.y,
                max: 6
            },

        }
    }
});

const issuesCtx = document.querySelector('[data-issues-chart]').getContext('2d');
const issuesChart = new Chart(issuesCtx, {
    type: 'bar',
    data: {
        labels: ['Project 1', 'Project 2', 'Project 3', 'Project 4'],
        datasets: [{
            label: 'Open Issues',
            data: [3, 5, 4, 1],
            backgroundColor: '#cccccc',
            borderRadius: 6,
        }]
    },
    options: {
        indexAxis: 'y',
        ...darkTheme,
        datasets: {
            bar: {
                barPercentage: 0.8,
                categoryPercentage: 1,
            },
        },
        plugins: {
            ...darkTheme.plugins,
            tooltip: {
                ...darkTheme.plugins.tooltip,
                callbacks: {
                    label: function(context) {
                        return `${context.dataset.label}: ${context.raw}`;
                    }
                }
            },
            datalabels: {
                anchor: 'end',
                align: 'right',
                color: '#ffffff',
                font: {
                    weight: 'bold',
                    size: 12
                },
                offset: 2,
                formatter: function(value) {
                    return value;
                }
            }
        },
        scales: {
            ...darkTheme.scales,
            x: {
                ...darkTheme.scales.x,
                max: 6,
                ticks: {
                    display: false
                }
            },
            y: {
                ...darkTheme.scales.y,
                grid: {
                    display: false
                },
                ticks: {
                    color: '#ffffff'
                }
            }
        }
    }
});

