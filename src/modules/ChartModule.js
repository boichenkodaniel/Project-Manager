import { Chart, registerables } from 'chart.js';
import ChartDataLabels from 'chartjs-plugin-datalabels';

class ChartModule {
    constructor() {
        Chart.register(...registerables, ChartDataLabels);
        this.initCharts();
    }

    initCharts() {
        const taskCanvas = document.querySelector('[data-task-chart]');
        const issuesCanvas = document.querySelector('[data-issues-chart]');

        if (taskCanvas) {
            this.taskCtx = taskCanvas.getContext('2d');
            this.taskChart();
        }

        if (issuesCanvas) {
            this.issuesCtx = issuesCanvas.getContext('2d');
            this.issuesChart();
        }
    }

    darkTheme() {
        return {
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
    }

    taskChart() {
        const theme = this.darkTheme();
        new Chart(this.taskCtx, {
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
                ...theme,
                datasets: {
                    bar: {
                        barPercentage: 0.9,
                        categoryPercentage: 0.9,
                    },
                },
                plugins: {
                    ...theme.plugins,
                    tooltip: {
                        ...theme.plugins.tooltip,
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
                    ...theme.scales,
                    y: {
                        ...theme.scales.y,
                        max: 6
                    },
                }
            }
        });
    }

    issuesChart() {
        const theme = this.darkTheme();
        new Chart(this.issuesCtx, {
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
                ...theme,
                datasets: {
                    bar: {
                        barPercentage: 0.8,
                        categoryPercentage: 1,
                    },
                },
                plugins: {
                    ...theme.plugins,
                    tooltip: {
                        ...theme.plugins.tooltip,
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
                    ...theme.scales,
                    x: {
                        ...theme.scales.x,
                        max: 6,
                        ticks: {
                            display: false
                        }
                    },
                    y: {
                        ...theme.scales.y,
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
    }
}

export default ChartModule;