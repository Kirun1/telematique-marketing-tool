const ProductScraperCharts = {
    charts: {},
    
    init(config) {
        this.config = config;
        this.initTrafficChart();
        this.initKeywordChart();
        this.initCompetitorChart();
        this.initSeoHealthChart();
        this.bindEvents();
    },
    
    initTrafficChart() {
        const ctx = document.getElementById('trafficTrendChart').getContext('2d');
        this.loadChartData('traffic_trend', '30d').then(data => {
            this.charts.traffic = new Chart(ctx, {
                type: 'line',
                data: data,
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'top',
                        },
                        tooltip: {
                            mode: 'index',
                            intersect: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Sessions'
                            }
                        }
                    },
                    interaction: {
                        mode: 'nearest',
                        axis: 'x',
                        intersect: false
                    }
                }
            });
        });
    },
    
    initKeywordChart() {
        const ctx = document.getElementById('keywordPerformanceChart').getContext('2d');
        this.loadChartData('keyword_performance').then(data => {
            this.charts.keywords = new Chart(ctx, {
                type: 'bar',
                data: data,
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'top',
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Search Volume'
                            }
                        },
                        y1: {
                            position: 'right',
                            beginAtZero: true,
                            max: 100,
                            title: {
                                display: true,
                                text: 'Traffic Share %'
                            },
                            grid: {
                                drawOnChartArea: false
                            }
                        }
                    }
                }
            });
        });
    },
    
    initCompetitorChart() {
        const ctx = document.getElementById('competitorRadarChart').getContext('2d');
        this.loadChartData('competitor_analysis').then(data => {
            this.charts.competitors = new Chart(ctx, {
                type: 'radar',
                data: data,
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'top',
                        }
                    },
                    scales: {
                        r: {
                            beginAtZero: true,
                            max: 100,
                            ticks: {
                                stepSize: 20
                            }
                        }
                    }
                }
            });
        });
    },
    
    initSeoHealthChart() {
        const ctx = document.getElementById('seoHealthGauge').getContext('2d');
        this.loadChartData('seo_health').then(data => {
            this.charts.seoHealth = new Chart(ctx, {
                type: 'bar',
                data: data,
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            max: 100,
                            title: {
                                display: true,
                                text: 'Score'
                            }
                        }
                    }
                }
            });
        });
    },
    
    loadChartData(chartType, period = '30d') {
        return new Promise((resolve, reject) => {
            jQuery.ajax({
                url: this.config.ajaxurl,
                type: 'POST',
                data: {
                    action: 'get_chart_data',
                    chart_type: chartType,
                    period: period,
                    nonce: this.config.nonce
                },
                success: (response) => {
                    if (response.success) {
                        resolve(response.data);
                    } else {
                        reject(response.data);
                    }
                },
                error: (xhr, status, error) => {
                    reject(error);
                }
            });
        });
    },
    
    bindEvents() {
        // Period selector changes
        jQuery('#traffic-period').on('change', (e) => {
            const period = jQuery(e.target).val();
            this.updateTrafficChart(period);
        });
        
        // Refresh charts button
        jQuery('#refresh-charts').on('click', () => {
            this.refreshAllCharts();
        });
    },
    
    updateTrafficChart(period) {
        this.loadChartData('traffic_trend', period).then(data => {
            this.charts.traffic.data = data;
            this.charts.traffic.update();
        });
    },
    
    refreshAllCharts() {
        Object.values(this.charts).forEach(chart => {
            chart.destroy();
        });
        this.init(this.config);
    }
};