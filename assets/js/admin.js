/**
 * Admin JavaScript for Native Content Relationships
 *
 * @fileoverview Handles admin interface functionality for the Native Content Relationships plugin
 * @package Native Content Relationships
 * @since 1.0.0
 */

(function ($) {
	'use strict';

	var NCR = {
		init: function () {
			this.bindEvents();
			if ( typeof naticoreData !== 'undefined' && naticoreData.manualOrderEnabled ) {
				this.initSortable();
			}
		},

		syncRelationOrder: function ( $list ) {
			var type = $list.data( 'relation-type' );
			var ids  = [];
			$list.find( '.naticore-relation-item[data-relation-id]' ).each( function () {
				ids.push( $( this ).data( 'relation-id' ) );
			} );
			$list.siblings( '.naticore-order-input' ).val( ids.join( ',' ) );
		},

		initSortable: function () {
			$( '.naticore-sortable' ).each( function () {
				var $list = $( this );
				NCR.syncRelationOrder( $list );
				$list.sortable( {
					axis: 'y',
					handle: '.naticore-relation-title',
					placeholder: 'naticore-relation-item naticore-sortable-placeholder',
					update: function () {
						NCR.syncRelationOrder( $list );
					}
				} );
			} );
		},

		bindEvents: function () {
			// Search input
			$( document ).on( 'input', '.naticore-search-input', this.handleSearch );
			$( document ).on( 'input', '.naticore-product-search', this.handleProductSearch );
			$( document ).on( 'click', '.naticore-suggest-btn', this.handleSuggestRelated );
			$( document ).on( 'click', '.naticore-remove-relation', this.handleRemove );
			$( document ).on( 'click', '.naticore-search-result', this.handleAddRelation );
			$( document ).on(
				'click',
				function (e) {
					if ( ! $( e.target ).closest( '.naticore-add-relation' ).length) {
						$( '.naticore-search-results' ).hide();
					}
				}
			);
		},

		handleSearch: function (e) {
			var $input        = $( this );
			var search        = $input.val();
			var relationType  = $input.data( 'relation-type' );
			var currentPostId = $( '#post_ID' ).val() || 0;

			if (search.length < 2) {
				$input.siblings( '.naticore-search-results' ).hide().empty();
				return;
			}

			// Debounce
			clearTimeout( $input.data( 'timeout' ) );
			var timeout = setTimeout(
				function () {
					NCR.performSearch( $input, search, relationType, currentPostId );
				},
				300
			);
			$input.data( 'timeout', timeout );
		},

		handleProductSearch: function (e) {
			var $input        = $( this );
			var search        = $input.val();
			var relationType  = $input.data( 'relation-type' );
			var currentPostId = $( '#post_ID' ).val() || 0;

			if (search.length < 2) {
				$input.siblings( '.naticore-search-results' ).hide().empty();
				return;
			}

			// Debounce
			clearTimeout( $input.data( 'timeout' ) );
			var timeout = setTimeout(
				function () {
					NCR.performProductSearch( $input, search, relationType, currentPostId );
				},
				300
			);
			$input.data( 'timeout', timeout );
		},

		handleSuggestRelated: function () {
			var $btn          = $( this );
			var relationType  = $btn.data( 'relation-type' );
			var currentPostId = $( '#post_ID' ).val() || 0;
			var $addRelation  = $btn.closest( '.naticore-add-relation' );
			var $results     = $addRelation.find( '.naticore-suggest-results' );

			if ( ! currentPostId ) {
				alert( 'Please save the post first.' );
				return;
			}

			$results.html( '<div class="naticore-loading">' + ( naticoreData.strings.suggesting || 'Suggesting...' ) + '</div>' ).show();

			$.ajax( {
				url: naticoreData.ajaxUrl,
				type: 'POST',
				data: {
					action: 'naticore_suggest_related',
					nonce: naticoreData.nonce,
					current_post_id: currentPostId
				},
				success: function ( response ) {
					if ( response.success && response.data.length > 0 ) {
						var html = '';
						$.each( response.data, function ( i, item ) {
							html += '<div class="naticore-search-result" data-id="' + item.id + '" data-type="' + item.type + '">';
							html += '<strong>' + item.title + '</strong> <small>(' + item.type + ')</small>';
							html += '</div>';
						} );
						$results.html( html );
					} else {
						$results.html( '<div class="naticore-no-results">' + ( naticoreData.strings.noSuggestions || 'No suggestions.' ) + '</div>' );
					}
				},
				error: function () {
					$results.html( '<div class="naticore-error">Error loading suggestions.</div>' );
				}
			} );
		},

		performSearch: function ($input, search, relationType, currentPostId) {
			var $results = $input.siblings( '.naticore-search-results' );
			$results.html( '<div class="naticore-loading">' + naticoreData.strings.searching + '</div>' ).show();

			$.ajax(
				{
					url: naticoreData.ajaxUrl,
					type: 'POST',
					data: {
						action: 'naticore_search_content',
						nonce: naticoreData.nonce,
						search: search,
						current_post_id: currentPostId
					},
					success: function (response) {
						if (response.success && response.data.length > 0) {
							var html = '';
							$.each(
								response.data,
								function (i, item) {
									html += '<div class="naticore-search-result" data-id="' + item.id + '" data-type="' + item.type + '">';
									html += '<strong>' + item.title + '</strong> <small>(' + item.type + ')</small>';
									html += '</div>';
								}
							);
							$results.html( html );
						} else {
							$results.html( '<div class="naticore-no-results">' + naticoreData.strings.noResults + '</div>' );
						}
					},
					error: function () {
						$results.html( '<div class="naticore-error">Error searching content.</div>' );
					}
				}
			);
		},

		performProductSearch: function ($input, search, relationType, currentPostId) {
			var $results = $input.siblings( '.naticore-search-results' );
			$results.html( '<div class="naticore-loading">' + naticoreData.strings.searching + '</div>' ).show();

			$.ajax(
				{
					url: naticoreData.ajaxUrl,
					type: 'POST',
					data: {
						action: 'naticore_search_products',
						nonce: naticoreData.nonce,
						search: search,
						current_post_id: currentPostId
					},
					success: function (response) {
						if (response.success && response.data.length > 0) {
							var html = '';
							$.each(
								response.data,
								function (i, item) {
									html += '<div class="naticore-search-result" data-id="' + item.id + '" data-type="' + item.type + '">';
									html += '<strong>' + item.title + '</strong>';
									if (item.sku) {
										html += ' <small>(SKU: ' + item.sku + ')</small>';
									}
									html += '</div>';
								}
							);
							$results.html( html );
						} else {
							$results.html( '<div class="naticore-no-results">' + naticoreData.strings.noResults + '</div>' );
						}
					},
					error: function () {
						$results.html( '<div class="naticore-error">Error searching products.</div>' );
					}
				}
			);
		},

		handleAddRelation: function (e) {
			var $result      = $( this );
			var toId         = $result.data( 'id' );
			var $input       = $result.closest( '.naticore-add-relation' ).find( '.naticore-search-input' );
			var relationType = $input.data( 'relation-type' );
			var fromId       = $( '#post_ID' ).val() || 0;

			if ( ! fromId) {
				alert( 'Please save the post first.' );
				return;
			}

			// Add relation via AJAX
			$.ajax(
				{
					url: naticoreData.ajaxUrl,
					type: 'POST',
					data: {
						action: 'naticore_add_relation',
						nonce: naticoreData.nonce,
						from_id: fromId,
						to_id: toId,
						relation_type: relationType
					},
					success: function (response) {
						if (response.success) {
							// Reload page to show new relation
							location.reload();
						} else {
							alert( response.data.message || 'Failed to add relationship.' );
						}
					},
					error: function () {
						alert( 'Error adding relationship.' );
					}
				}
			);
		},

		handleRemove: function (e) {
			e.preventDefault();

			if ( ! confirm( 'Remove this relationship?' )) {
				return;
			}

			var $button      = $( this );
			var fromId       = $button.data( 'from-id' );
			var toId         = $button.data( 'to-id' );
			var relationType = $button.data( 'relation-type' );

			$.ajax(
				{
					url: naticoreData.ajaxUrl,
					type: 'POST',
					data: {
						action: 'naticore_remove_relation',
						nonce: naticoreData.nonce,
						from_id: fromId,
						to_id: toId,
						relation_type: relationType
					},
					success: function (response) {
						if (response.success) {
							var $item = $button.closest( '.naticore-relation-item' );
							var $list = $item.closest( '.naticore-sortable' );
							$item.fadeOut(
								function () {
									$( this ).remove();
									if ( $list.length && typeof NCR.syncRelationOrder === 'function' ) {
										NCR.syncRelationOrder( $list );
									}
								}
							);
						} else {
							alert( response.data.message || 'Failed to remove relationship.' );
						}
					},
					error: function () {
						alert( 'Error removing relationship.' );
					}
				}
			);
		}
	};

	$( document ).ready(
		function () {
			NCR.init();
		}
	);

	// Add AJAX handlers
	$( document ).on(
		'wp_ajax_naticore_add_relation',
		function () {
			// Handled in handleAddRelation
		}
	);

	$( document ).on(
		'wp_ajax_naticore_remove_relation',
		function () {
			// Handled in handleRemove
		}
	);

})( jQuery );
