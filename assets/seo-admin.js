jQuery(document).ready(function($) {
    // Content Analysis.
    $('#analyze-content').on('click', function() {
        const content = $('#content-to-analyze').val();
        const focusKeyword = $('#focus-keyword').val();
        
        if (!content) {
            alert('Please enter some content to analyze');
            return;
        }
        
        $(this).prop('disabled', true).text('Analyzing...');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'analyze_content',
                content: content,
                focus_keyword: focusKeyword,
                nonce: seo_admin_nonce
            },
            success: function(response) {
                if (response.success) {
                    displayAnalysisResults(response.data);
                } else {
                    alert('Error analyzing content');
                }
            },
            complete: function() {
                $('#analyze-content').prop('disabled', false).text('Analyze Content');
            }
        });
    });
    
    function displayAnalysisResults(data) {
        let html = `
            <div class="analysis-result">
                <h4>Content Analysis Results</h4>
                <div class="analysis-metric">
                    <span>Word Count:</span>
                    <span class="${data.word_count >= 300 ? 'metric-good' : 'metric-bad'}">${data.word_count} words</span>
                </div>
                <div class="analysis-metric">
                    <span>Reading Time:</span>
                    <span>${data.reading_time} minutes</span>
                </div>
                <div class="analysis-metric">
                    <span>Readability Score:</span>
                    <span class="${data.readability_score >= 60 ? 'metric-good' : 'metric-warning'}">${data.readability_score}/100</span>
                </div>
        `;
        
        if (data.keyword_density > 0) {
            html += `
                <div class="analysis-metric">
                    <span>Keyword Density:</span>
                    <span class="${data.keyword_density >= 1 && data.keyword_density <= 3 ? 'metric-good' : 'metric-warning'}">${data.keyword_density}%</span>
                </div>
            `;
        }
        
        html += `</div>`;
        
        // Recommendations.
        if (data.recommendations.length > 0) {
            html += `<h4>Recommendations</h4>`;
            data.recommendations.forEach(rec => {
                html += `<div class="recommendation ${rec.priority}">${rec.message}</div>`;
            });
        }
        
        $('#analysis-results').html(html).show();
    }
    
    // Keyword Research.
    $('#research-keywords').on('click', function() {
        const keyword = $('#research-keyword').val();
        
        if (!keyword) {
            alert('Please enter a keyword to research');
            return;
        }
        
        $(this).prop('disabled', true).text('Researching...');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'research_keywords',
                keyword: keyword,
                nonce: seo_admin_nonce
            },
            success: function(response) {
                if (response.success) {
                    displayKeywordResearch(response.data);
                } else {
                    alert('Error researching keywords');
                }
            },
            complete: function() {
                $('#research-keywords').prop('disabled', false).text('Research Keywords');
            }
        });
    });
    
    // Content Optimization.
    $('.optimize-content').on('click', function() {
        const content = $('#content-to-analyze').val();
        const optimizationType = $(this).data('type');
        
        if (!content) {
            alert('Please enter some content to optimize');
            return;
        }
        
        $(this).prop('disabled', true).text('Optimizing...');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'optimize_content',
                content: content,
                optimization_type: optimizationType,
                nonce: seo_admin_nonce
            },
            success: function(response) {
                if (response.success) {
                    $('#content-to-analyze').val(response.data.optimized_content);
                    alert('Content optimized! Changes: ' + response.data.changes_made.join(', '));
                } else {
                    alert('Error optimizing content');
                }
            },
            complete: function() {
                $('.optimize-content').prop('disabled', false).text('Optimize');
            }
        });
    });
});

jQuery(document).ready(function($) {
    let scrapedProducts = [];
    let storedProducts = [];
    let currentStats = {
        products: 0,
        pages: 0,
        errors: 0
    };

    // Load stored products on page load.
    loadStoredProducts();

    $('#start-scraping').on('click', function() {
        const targetUrl = $('input[name="product_scraper_options[target_url]"]').val();
        const maxPages = $('input[name="product_scraper_options[max_pages]"]').val();
        const scrapeDetails = $('input[name="product_scraper_options[scrape_details]"]').is(':checked');
        const delay = $('input[name="product_scraper_options[request_delay]"]').val();

        if (!targetUrl) {
            alert('Please enter a target URL');
            return;
        }

        $('#scraping-progress').show();
        $('#start-scraping').prop('disabled', true);
        scrapedProducts = [];
        currentStats = {
            products: 0,
            pages: 0,
            errors: 0
        };

        scrapePage(1, parseInt(maxPages), targetUrl, scrapeDetails, parseFloat(delay));
    });

    $('#test-selectors').on('click', function() {
        const targetUrl = $('input[name="product_scraper_options[target_url]"]').val();

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'test_selectors',
                target_url: targetUrl,
                nonce: '<?php echo wp_create_nonce("test_selectors_nonce"); ?>'
            },
            success: function(response) {
                if (response.success) {
                    $('#scraping-results').html('<div class="notice notice-success"><pre>' +
                        JSON.stringify(response.data, null, 2) + '</pre></div>');
                } else {
                    alert('Error: ' + response.data);
                }
            }
        });
    });

    $('#refresh-stored').on('click', function() {
        loadStoredProducts();
    });

    $('#save-to-db').on('click', function() {
        if (scrapedProducts.length === 0) {
            alert('No products to save');
            return;
        }

        const targetUrl = $('input[name="product_scraper_options[target_url]"]').val();

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'scrape_products',
                page: 1,
                max_pages: 1,
                target_url: targetUrl,
                save_only: true,
                products_data: JSON.stringify(scrapedProducts),
                nonce: '<?php echo wp_create_nonce("scrape_products_nonce"); ?>'
            },
            success: function(response) {
                if (response.success) {
                    alert('Products saved to database successfully!');
                    loadStoredProducts();
                } else {
                    alert('Error saving products: ' + response.data);
                }
            }
        });
    });

    $('#export-csv').on('click', function() {
        exportProducts('csv', scrapedProducts);
    });

    $('#export-excel').on('click', function() {
        exportProducts('excel', scrapedProducts);
    });

    $('#export-all-csv').on('click', function() {
        exportProducts('csv', storedProducts, true);
    });

    $('#export-all-excel').on('click', function() {
        exportProducts('excel', storedProducts, true);
    });

    $('#select-all').on('change', function() {
        $('.product-checkbox').prop('checked', this.checked);
    });

    $('#do-bulk-action').on('click', function() {
        const action = $('#bulk-action-selector').val();
        const selectedProducts = $('.product-checkbox:checked').map(function() {
            return $(this).val();
        }).get();

        if (selectedProducts.length === 0) {
            alert('Please select products to perform bulk action');
            return;
        }

        switch (action) {
            case 'export_csv':
                exportProducts('csv', storedProducts.filter(p => selectedProducts.includes(p.id.toString())));
                break;
            case 'export_excel':
                exportProducts('excel', storedProducts.filter(p => selectedProducts.includes(p.id.toString())));
                break;
            case 'delete':
                if (confirm(`Are you sure you want to delete ${selectedProducts.length} products?`)) {
                    deleteProducts(selectedProducts);
                }
                break;
            default:
                alert('Please select a bulk action');
        }
    });

    $('#delete-all-products').on('click', function() {
        if (confirm('Are you sure you want to delete ALL stored products? This action cannot be undone.')) {
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'delete_stored_products',
                    delete_all: true,
                    nonce: '<?php echo wp_create_nonce("delete_products_nonce"); ?>'
                },
                success: function(response) {
                    if (response.success) {
                        alert('All products deleted successfully!');
                        loadStoredProducts();
                    } else {
                        alert('Error deleting products: ' + response.data);
                    }
                }
            });
        }
    });

    function loadStoredProducts() {
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'get_stored_products',
                nonce: '<?php echo wp_create_nonce("get_products_nonce"); ?>'
            },
            success: function(response) {
                if (response.success) {
                    storedProducts = response.data.products;
                    displayStoredProducts(storedProducts, response.data.stats);
                }
            }
        });
    }

    function displayStoredProducts(products, stats) {
        let html = '';

        if (products.length === 0) {
            html = '<tr><td colspan="9">No products stored yet.</td></tr>';
        } else {
            products.forEach(product => {
                html += `
                <tr>
                    <td><input type="checkbox" class="product-checkbox" value="${product.id}"></td>
                    <td>${product.id}</td>
                    <td>${product.product_name}</td>
                    <td>${product.price_display} (${product.price} CHF)</td>
                    <td>${product.rating_stars} ★</td>
                    <td>${product.review_count}</td>
                    <td>${new Date(product.scraped_at).toLocaleDateString()}</td>
                    <td>${product.imported ? 'Imported' : 'Not Imported'}</td>
                    <td>
                        <button class="button button-small export-single" data-id="${product.id}" data-format="csv">CSV</button>
                        <button class="button button-small export-single" data-id="${product.id}" data-format="excel">Excel</button>
                        <button class="button button-small button-danger delete-single" data-id="${product.id}">Delete</button>
                    </td>
                </tr>
            `;
            });
        }

        $('#stored-products-list').html(html);
        $('#storage-info').text(`Showing ${products.length} products`);

        // Add event listeners for single actions.
        $('.export-single').on('click', function() {
            const productId = $(this).data('id');
            const format = $(this).data('format');
            const product = storedProducts.find(p => p.id == productId);
            if (product) {
                exportProducts(format, [product]);
            }
        });

        $('.delete-single').on('click', function() {
            const productId = $(this).data('id');
            if (confirm('Are you sure you want to delete this product?')) {
                deleteProducts([productId]);
            }
        });
    }

    function deleteProducts(productIds) {
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'delete_stored_products',
                product_ids: productIds,
                nonce: '<?php echo wp_create_nonce("delete_products_nonce"); ?>'
            },
            success: function(response) {
                if (response.success) {
                    alert('Products deleted successfully!');
                    loadStoredProducts();
                } else {
                    alert('Error deleting products: ' + response.data);
                }
            }
        });
    }

    function exportProducts(format, products, isAll = false) {
        if (products.length === 0) {
            alert('No products to export');
            return;
        }

        const form = $('<form>', {
            method: 'post',
            action: ajaxurl,
            style: 'display: none;'
        });

        form.append($('<input>', {
            type: 'hidden',
            name: 'action',
            value: format === 'csv' ? 'export_products_csv' : 'export_products_excel'
        }));

        form.append($('<input>', {
            type: 'hidden',
            name: 'products_data',
            value: JSON.stringify(products)
        }));

        form.append($('<input>', {
            type: 'hidden',
            name: 'nonce',
            value: '<?php echo wp_create_nonce("export_products_nonce"); ?>'
        }));

        if (isAll) {
            form.append($('<input>', {
                type: 'hidden',
                name: 'export_all',
                value: '1'
            }));
        }

        $('body').append(form);
        form.submit();
        form.remove();
    }

    function scrapePage(page, maxPages, targetUrl, scrapeDetails, delay) {
        currentStats.pages = page;
        updateStats();

        $('#progress-text').text(`Scraping page ${page} of ${maxPages}...`);
        $('#progress-bar-inner').css('width', ((page - 1) / maxPages) * 100 + '%');

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'scrape_products',
                page: page,
                max_pages: maxPages,
                target_url: targetUrl,
                scrape_details: scrapeDetails,
                nonce: '<?php echo wp_create_nonce("scrape_products_nonce"); ?>'
            },
            success: function(response) {
                if (response.success) {
                    scrapedProducts = scrapedProducts.concat(response.data.products);
                    currentStats.products = scrapedProducts.length;

                    if (response.data.errors) {
                        currentStats.errors += response.data.errors;
                    }

                    // Show debug info.
                    if (response.data.debug) {
                        console.log('Debug Info:', response.data.debug);
                        $('#scraping-results').append(
                            '<div class="notice notice-info"><strong>Debug:</strong><pre>' +
                            JSON.stringify(response.data.debug, null, 2) + '</pre></div>'
                        );
                    }

                    updateStats();

                    if (page < maxPages && response.data.has_more) {
                        setTimeout(function() {
                            scrapePage(page + 1, maxPages, targetUrl, scrapeDetails, delay);
                        }, delay * 1000);
                    } else {
                        scrapingComplete();
                    }
                } else {
                    currentStats.errors++;
                    updateStats();
                    $('#scraping-results').append('<div class="notice notice-error">Error scraping page ' + page + ': ' + response.data + '</div>');

                    if (page < maxPages) {
                        setTimeout(function() {
                            scrapePage(page + 1, maxPages, targetUrl, scrapeDetails, delay);
                        }, delay * 1000);
                    } else {
                        scrapingComplete();
                    }
                }
            },
            error: function(xhr, status, error) {
                currentStats.errors++;
                updateStats();
                $('#scraping-results').append('<div class="notice notice-error">AJAX error on page ' + page + ': ' + error + '</div>');

                if (page < maxPages) {
                    setTimeout(function() {
                        scrapePage(page + 1, maxPages, targetUrl, scrapeDetails, delay);
                    }, delay * 1000);
                } else {
                    scrapingComplete();
                }
            }
        });
    }

    function updateStats() {
        $('#scraping-stats').html(`
        <div class="product-stats">
            <span>Pages: ${currentStats.pages}</span>
            <span>Products: ${currentStats.products}</span>
            <span>Errors: ${currentStats.errors}</span>
        </div>
    `);
    }

    function scrapingComplete() {
        $('#progress-text').text(`Scraping completed! Found ${scrapedProducts.length} products.`);
        $('#progress-bar-inner').css('width', '100%');
        $('#start-scraping').prop('disabled', false);
        $('#import-woocommerce').prop('disabled', false);

        displayProducts(scrapedProducts);
    }

    function displayProducts(products) {
        $('#scraped-products').show();
        $('#products-count').text(`(${products.length} products)`);

        let html = '';

        products.forEach((product, index) => {
            html += `
            <div class="product-item">
                <h4>${product.name || 'No Name'}</h4>
                ${product.badges ? `<div class="product-badges">${
                    product.badges.map(badge => `<span class="badge">${badge}</span>`).join('')
                }</div>` : ''}
                <p><strong>Price:</strong> ${product.price || 'N/A'} ${product.price_amount ? `(${product.price_amount} CHF)` : ''}</p>
                <p><strong>Rating:</strong> ${product.rating_stars || 0} stars (${product.review_count || 0} reviews)</p>
                ${product.url ? `<p><strong>URL:</strong> <a href="${product.url}" target="_blank">${product.url}</a></p>` : ''}
                ${product.image ? `<img src="${product.image}" style="max-width: 100px; height: auto;" loading="lazy">` : ''}
            </div>
        `;
        });

        $('#products-list').html(html);

        // Store products in global variable for export.
        window.scrapedProductsData = products;
    }
});