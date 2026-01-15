/**
 * @package BusinessWest
 * @since 1.0.0
 */

(function ($) {
    'use strict';

    // Global variables  
    var currentPage = 1;
    var membersData = [];

    /**
     * Escape HTML to prevent XSS
     */
    function escapeHtml(text) {
        var div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    /**
     * Initialize when document is ready
     */
    $(document).ready(function () {       
        initMembersDirectory();       
    });

    /**
     * Initialize the members directory
     */
    function initMembersDirectory() {
        // Load initial members data from JSON
        loadMembersFromJSON();      

        // Bind event listeners
        bindEvents();
    }

    /**
     * Load members data from JSON script tag
     */
    function loadMembersFromJSON() {
        var jsonData = $('#bw-members-json').text();
        if (jsonData) {
            try {
                membersData = JSON.parse(jsonData);
            } catch (e) {
                console.error('Error parsing members JSON:', e);
                membersData = [];
            }
        }
    }   

    /**
     * Bind event listeners
     */
    function bindEvents() {
        // Search input - debounced
        var searchTimeout;
        $('#bw-member-search').on('input', function () {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(function () {
                performFilter();
            }, 500);
        });


        // Card click - highlight marker
        $(document).on('click', '.bw-member-card', function (e) {
            // Don't trigger if clicking on links or buttons
            if ($(e.target).is('a, button') || $(e.target).closest('a, button').length) {
                return;
            }         
        });

        // Pagination buttons
        $(document).on('click', '.bw-page-btn', function () {
            var action = $(this).data('page');
            if (action === 'next') {
                currentPage++;
            } else if (action === 'prev' && currentPage > 1) {
                currentPage--;
            }
            performFilter();
        });
    }

    /**
     * Perform filtering with AJAX
     */
    function performFilter() {
        var searchTerm = $('#bw-member-search').val();
        var industry = $('#bw-industry-filter').val();

        // Show loading indicators
        $('#bw-grid-loading').show();
        $('#bw-members-grid').css('opacity', '0.5');

        // AJAX request
        $.ajax({
            url: bwMembersData.ajaxurl,
            type: 'POST',
            data: {
                action: 'bw_filter_members',
                nonce: bwMembersData.nonce,
                search: searchTerm,
                industry: industry,
                paged: currentPage
            },
            success: function (response) {
                if (response.success) {
                    var data = response.data;
                    membersData = data.members;

                    // Update results count
                    $('#bw-results-number').text(data.members.length);
                    $('#bw-total-count').text(data.total);
                    // Update grid
                    updateMembersGrid(data.members);
                    // Update pagination
                    updatePagination(data.current_page, data.pages);
                } else {
                    console.error('Filter error:', response);
                }
            },
            error: function (xhr, status, error) {
                console.error('AJAX error:', error);
            },
            complete: function () {
                // Hide loading indicators
                $('#bw-grid-loading').hide();
                $('#bw-members-grid').css('opacity', '1');
            }
        });
    }

    /**
     * Update members grid with filtered results
     */
    function updateMembersGrid(members) {
        var $grid = $('#bw-members-grid');

        if (members.length === 0) {
            $grid.html('<div class="bw-no-results"><p>No business members found. Please try a different search or filter.</p></div>');
            return;
        }

        // Build HTML for member cards
        var html = '';
        members.forEach(function (member) {
            html += buildMemberCardHTML(member);
        });

        $grid.html(html);
    }

    /**
     * Build HTML for a single member card
     */
    function buildMemberCardHTML(member) {
        var bannerHTML = '<div class="bw-banner-placeholder"></div>';

        var logoHTML = member.logo_url
            ? `<div class="bw-member-logo"><img src="${escapeHtml(member.logo_url)}" alt="${escapeHtml(member.business_name)} logo" class="bw-logo-image" onerror="this.onerror=null; this.src='https://www.gravatar.com/avatar/?d=mystery&amp;s=150';" /></div>`
            : `<div class="bw-member-logo"><img src="https://www.gravatar.com/avatar/?d=mystery&amp;s=150" alt="${escapeHtml(member.business_name)} logo" class="bw-logo-image" /></div>`;

        return `
            <div class="bw-member-card" data-member-id="${member.id}" data-lat="${member.latitude}" data-lng="${member.longitude}">
                <div class="bw-member-banner">${bannerHTML}</div>
                ${logoHTML}
                <div class="bw-member-content">
                    <h4 class="bw-member-name">
                        <a href="${escapeHtml(member.profile_url)}" class="bw-member-link" target="_blank" rel="noopener">${escapeHtml(member.business_name)}</a>
                    </h4>                   
                </div>
                <div class="bw-member-actions">
                    <a href="${escapeHtml(member.profile_url)}" class="bw-view-profile-btn" target="_blank" rel="noopener">View Profile</a>                   
                </div>
            </div>
        `;
    }

    /**
     * Update pagination controls
     */
    function updatePagination(current, total) {
        if (total <= 1) {
            $('#bw-pagination').hide();
            return;
        }

        $('#bw-pagination').show();
        $('#bw-current-page').text(current);
        $('#bw-total-pages').text(total);

        // Update button states
        if (current === 1) {
            $('.bw-prev-btn').prop('disabled', true);
        } else {
            $('.bw-prev-btn').prop('disabled', false);
        }

        if (current >= total) {
            $('.bw-next-btn').prop('disabled', true);
        } else {
            $('.bw-next-btn').prop('disabled', false);
        }
    }

})(jQuery);
