const ProductScraperCharts = {
    charts: {},
    config: {},
    initialData: {},
    
    init(config) {
        this.config = config;
        this.initialData = window.productScraperChartData?.initialData || {};
        this.initTrafficChart();
        this.initKeywordChart();
        this.initCompetitorChart();
        this.initSeoHealthChart();
        this.bindEvents();
    },
    
    initTrafficChart() {
        const ctx = document.getElementById('trafficTrendChart');
        if (!ctx) return;
        
        // Use initial data while loading real data
        const initialData = this.initialData.traffic_trend;
        
        this.charts.traffic = new Chart(ctx.getContext('2d'), {
            type: 'line',
            data: initialData,
            options: {
                responsive: true,
                maintainAspectRatio: false,
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

        // Load real data and update chart
        this.loadChartData('traffic_trend', '30d').then(data => {
            this.charts.traffic.data = data;
            this.charts.traffic.update('none'); // Update without animation
        }).catch(error => {
            console.error('Failed to load traffic data:', error);
        });
    },
    
    initKeywordChart() {
        const ctx = document.getElementById('keywordPerformanceChart');
        if (!ctx) return;
        
        const initialData = this.initialData.keyword_performance;
        
        this.charts.keywords = new Chart(ctx.getContext('2d'), {
            type: 'bar',
            data: initialData,
            options: {
                responsive: true,
                maintainAspectRatio: false,
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

        this.loadChartData('keyword_performance').then(data => {
            this.charts.keywords.data = data;
            this.charts.keywords.update('none');
        }).catch(error => {
            console.error('Failed to load keyword data:', error);
        });
    },
    
    initCompetitorChart() {
        const ctx = document.getElementById('competitorRadarChart');
        if (!ctx) return;
        
        const initialData = this.initialData.competitor_analysis;
        
        this.charts.competitors = new Chart(ctx.getContext('2d'), {
            type: 'radar',
            data: initialData,
            options: {
                responsive: true,
                maintainAspectRatio: false,
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

        this.loadChartData('competitor_analysis').then(data => {
            this.charts.competitors.data = data;
            this.charts.competitors.update('none');
        }).catch(error => {
            console.error('Failed to load competitor data:', error);
        });
    },
    
    initSeoHealthChart() {
        const ctx = document.getElementById('seoHealthGauge');
        if (!ctx) return;
        
        const initialData = this.initialData.seo_health;
        
        this.charts.seoHealth = new Chart(ctx.getContext('2d'), {
            type: 'bar',
            data: initialData,
            options: {
                responsive: true,
                maintainAspectRatio: false,
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

        this.loadChartData('seo_health').then(data => {
            this.charts.seoHealth.data = data;
            this.charts.seoHealth.update('none');
        }).catch(error => {
            console.error('Failed to load SEO health data:', error);
        });
    },
    
    loadChartData(chartType, period = '30d') {
        return new Promise((resolve, reject) => {
            // Use the localized config
            jQuery.ajax({
                url: this.config.ajaxurl || window.productScraperChartData?.ajaxurl,
                type: 'POST',
                data: {
                    action: 'get_chart_data',
                    chart_type: chartType,
                    period: period,
                    nonce: this.config.nonce || window.productScraperChartData?.nonce
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