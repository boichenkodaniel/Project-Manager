import { Chart, registerables } from 'chart.js';
import ChartDataLabels from 'chartjs-plugin-datalabels';

class ChartModule {
    constructor() {
        Chart.register(...registerables, ChartDataLabels);
        this.initCharts();
    }

    async initCharts() {
        const taskCanvas = document.querySelector('[data-task-chart]');
        const issuesCanvas = document.querySelector('[data-issues-chart]');

        if (taskCanvas) {
            this.taskCtx = taskCanvas.getContext('2d');
            const taskStats = await this.fetchTaskStats();
            this.taskChart(taskStats);
        }

        if (issuesCanvas) {
            this.issuesCtx = issuesCanvas.getContext('2d');
            const issueStats = await this.fetchIssuesStats();
            this.issuesChart(issueStats);
        }
    }

    async fetchChartData(action, params = {}) {
        const query = new URLSearchParams(params).toString();
        const res = await fetch(`/api?action=${action}&${query}`);
        
        if (!res.ok) {
            throw new Error(`HTTP error! status: ${res.status}`);
        }
        const result = await res.json();
        if (!result.success) {
            throw new Error(result.error || 'Failed to fetch chart data');
        }
        return result.data;
    }

    async fetchTaskStats() {
        try {
            const data = await this.fetchChartData('reports.getTasksStats', { period: 'week' });
            // Преобразование данных для графика
            const labels = [];
            const counts = [];
            data.forEach(item => {
                labels.push(item.day_of_week);
                counts.push(parseInt(item.count, 10));
            });
            return { labels, counts };
        } catch (error) {
            console.error('Error fetching task stats:', error);
            return { labels: [], counts: [] };
        }
    }

    async fetchIssuesStats() {
        try {
            const data = await this.fetchChartData('reports.getIssuesStats');
            // Преобразование данных для графика
            const labels = [];
            const counts = [];
            data.forEach(item => {
                labels.push(item.project_title);
                counts.push(parseInt(item.count, 10));
            });
            return { labels, counts };
        } catch (error) {
            console.error('Error fetching issues stats:', error);
             return { labels: [], counts: [] };
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

    taskChart(stats) {
        const theme = this.darkTheme();
        const maxCount = Math.max(...stats.counts, 0) + 1; // +1 для отступа
        new Chart(this.taskCtx, {
            type: 'bar',
            data: {
                labels: stats.labels,
                datasets: [{
                    label: 'Tasks Completed',
                    data: stats.counts,
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
                        max: maxCount
                    },
                }
            }
        });
    }

    issuesChart(stats) {
        const theme = this.darkTheme();
        const maxCount = Math.max(...stats.counts, 0) + 1; // +1 для отступа
        new Chart(this.issuesCtx, {
            type: 'bar',
            data: {
                labels: stats.labels,
                datasets: [{
                    label: 'Open Issues',
                    data: stats.counts,
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
                        max: maxCount,
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