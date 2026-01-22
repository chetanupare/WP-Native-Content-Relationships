(function($) {
	'use strict';
	
	var WPNCR = {
		init: function() {
			this.bindEvents();
		},
		
		bindEvents: function() {
			// Search input
			$(document).on('input', '.wpncr-search-input', this.handleSearch);
			$(document).on('input', '.wpncr-product-search', this.handleProductSearch);
			$(document).on('click', '.wpncr-remove-relation', this.handleRemove);
			$(document).on('click', '.wpncr-search-result', this.handleAddRelation);
			$(document).on('click', function(e) {
				if (!$(e.target).closest('.wpncr-add-relation').length) {
					$('.wpncr-search-results').hide();
				}
			});
		},
		
		handleSearch: function(e) {
			var $input = $(this);
			var search = $input.val();
			var relationType = $input.data('relation-type');
			var currentPostId = $('#post_ID').val() || 0;
			
			if (search.length < 2) {
				$input.siblings('.wpncr-search-results').hide().empty();
				return;
			}
			
			// Debounce
			clearTimeout($input.data('timeout'));
			var timeout = setTimeout(function() {
				WPNCR.performSearch($input, search, relationType, currentPostId);
			}, 300);
			$input.data('timeout', timeout);
		},
		
		handleProductSearch: function(e) {
			var $input = $(this);
			var search = $input.val();
			var relationType = $input.data('relation-type');
			var currentPostId = $('#post_ID').val() || 0;
			
			if (search.length < 2) {
				$input.siblings('.wpncr-search-results').hide().empty();
				return;
			}
			
			// Debounce
			clearTimeout($input.data('timeout'));
			var timeout = setTimeout(function() {
				WPNCR.performProductSearch($input, search, relationType, currentPostId);
			}, 300);
			$input.data('timeout', timeout);
		},
		
		performSearch: function($input, search, relationType, currentPostId) {
			var $results = $input.siblings('.wpncr-search-results');
			$results.html('<div class="wpncr-loading">' + wpncrData.strings.searching + '</div>').show();
			
			$.ajax({
				url: wpncrData.ajaxUrl,
				type: 'POST',
				data: {
					action: 'wpncr_search_content',
					nonce: wpncrData.nonce,
					search: search,
					current_post_id: currentPostId
				},
				success: function(response) {
					if (response.success && response.data.length > 0) {
						var html = '';
						$.each(response.data, function(i, item) {
							html += '<div class="wpncr-search-result" data-id="' + item.id + '" data-type="' + item.type + '">';
							html += '<strong>' + item.title + '</strong> <small>(' + item.type + ')</small>';
							html += '</div>';
						});
						$results.html(html);
					} else {
						$results.html('<div class="wpncr-no-results">' + wpncrData.strings.noResults + '</div>');
					}
				},
				error: function() {
					$results.html('<div class="wpncr-error">Error searching content.</div>');
				}
			});
		},
		
		performProductSearch: function($input, search, relationType, currentPostId) {
			var $results = $input.siblings('.wpncr-search-results');
			$results.html('<div class="wpncr-loading">' + wpncrData.strings.searching + '</div>').show();
			
			$.ajax({
				url: wpncrData.ajaxUrl,
				type: 'POST',
				data: {
					action: 'wpncr_search_products',
					nonce: wpncrData.nonce,
					search: search,
					current_post_id: currentPostId
				},
				success: function(response) {
					if (response.success && response.data.length > 0) {
						var html = '';
						$.each(response.data, function(i, item) {
							html += '<div class="wpncr-search-result" data-id="' + item.id + '" data-type="' + item.type + '">';
							html += '<strong>' + item.title + '</strong>';
							if (item.sku) {
								html += ' <small>(SKU: ' + item.sku + ')</small>';
							}
							html += '</div>';
						});
						$results.html(html);
					} else {
						$results.html('<div class="wpncr-no-results">' + wpncrData.strings.noResults + '</div>');
					}
				},
				error: function() {
					$results.html('<div class="wpncr-error">Error searching products.</div>');
				}
			});
		},
		
		handleAddRelation: function(e) {
			var $result = $(this);
			var toId = $result.data('id');
			var $input = $result.closest('.wpncr-add-relation').find('.wpncr-search-input');
			var relationType = $input.data('relation-type');
			var fromId = $('#post_ID').val() || 0;
			
			if (!fromId) {
				alert('Please save the post first.');
				return;
			}
			
			// Add relation via AJAX
			$.ajax({
				url: wpncrData.ajaxUrl,
				type: 'POST',
				data: {
					action: 'wpncr_add_relation',
					nonce: wpncrData.nonce,
					from_id: fromId,
					to_id: toId,
					relation_type: relationType
				},
				success: function(response) {
					if (response.success) {
						// Reload page to show new relation
						location.reload();
					} else {
						alert(response.data.message || 'Failed to add relationship.');
					}
				},
				error: function() {
					alert('Error adding relationship.');
				}
			});
		},
		
		handleRemove: function(e) {
			e.preventDefault();
			
			if (!confirm('Remove this relationship?')) {
				return;
			}
			
			var $button = $(this);
			var fromId = $button.data('from-id');
			var toId = $button.data('to-id');
			var relationType = $button.data('relation-type');
			
			$.ajax({
				url: wpncrData.ajaxUrl,
				type: 'POST',
				data: {
					action: 'wpncr_remove_relation',
					nonce: wpncrData.nonce,
					from_id: fromId,
					to_id: toId,
					relation_type: relationType
				},
				success: function(response) {
					if (response.success) {
						$button.closest('.wpncr-relation-item').fadeOut(function() {
							$(this).remove();
						});
					} else {
						alert(response.data.message || 'Failed to remove relationship.');
					}
				},
				error: function() {
					alert('Error removing relationship.');
				}
			});
		}
	};
	
	$(document).ready(function() {
		WPNCR.init();
	});
	
	// Add AJAX handlers
	$(document).on('wp_ajax_wpncr_add_relation', function() {
		// Handled in handleAddRelation
	});
	
	$(document).on('wp_ajax_wpncr_remove_relation', function() {
		// Handled in handleRemove
	});
	
})(jQuery);
