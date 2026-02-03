/**
 * User Relationships Admin JavaScript
 * Handles AJAX interactions for user relationships in admin
 */

jQuery( document ).ready(
	function ($) {
		// User profile: Add post relation
		$( '#naticore-add-post-relation' ).on(
			'click',
			function () {
				var $searchInput = $( '#naticore-post-search' );
				var $typeSelect  = $( '#naticore-relation-type' );
				var $results     = $( '#naticore-post-search-results' );

				var search = $searchInput.val().trim();
				var type   = $typeSelect.val();

				if ( ! search) {
					$searchInput.focus();
					return;
				}

				// Search posts
				$.ajax(
					{
						url: naticoreUserRelations.ajaxUrl,
						type: 'POST',
						data: {
							action: 'naticore_search_posts_for_user',
							search: search,
							nonce: naticoreUserRelations.nonce
						},
						beforeSend: function () {
							$results.html( '<div class="naticore-search-loading">Searching...</div>' ).show();
						},
						success: function (response) {
							if (response.success && response.data.length > 0) {
								renderPostSearchResults( response.data, type );
							} else {
								$results.html( '<div class="naticore-search-no-results">' + naticoreUserRelations.strings.noResults + '</div>' );
							}
						},
						error: function () {
							$results.html( '<div class="naticore-search-error">Error searching posts</div>' );
						}
					}
				);
			}
		);

		// Post editor: Add user relation
		$( '#naticore-add-user-relation' ).on(
			'click',
			function () {
				var $searchInput = $( '#naticore-user-search' );
				var $typeSelect  = $( '#naticore-user-relation-type' );
				var $results     = $( '#naticore-user-search-results' );

				var search = $searchInput.val().trim();
				var type   = $typeSelect.val();

				if ( ! search) {
					$searchInput.focus();
					return;
				}

				// Search users
				$.ajax(
					{
						url: naticoreUserRelations.ajaxUrl,
						type: 'POST',
						data: {
							action: 'naticore_search_users',
							search: search,
							nonce: naticoreUserRelations.nonce
						},
						beforeSend: function () {
							$results.html( '<div class="naticore-search-loading">Searching...</div>' ).show();
						},
						success: function (response) {
							if (response.success && response.data.length > 0) {
								renderUserSearchResults( response.data, type );
							} else {
								$results.html( '<div class="naticore-search-no-results">' + naticoreUserRelations.strings.noResults + '</div>' );
							}
						},
						error: function () {
							$results.html( '<div class="naticore-search-error">Error searching users</div>' );
						}
					}
				);
			}
		);

		// Render post search results
		function renderPostSearchResults(posts, type) {
			var $results = $( '#naticore-post-search-results' );
			var html     = '';

			posts.forEach(
				function (post) {
					html += '<div class="naticore-search-result-item" data-id="' + post.id + '" data-type="' + type + '">';
					html += '<strong>' + post.post_title + '</strong>';
					html += ' <span class="naticore-search-post-type">(' + post.post_type + ')</span>';
					html += '</div>';
				}
			);

			$results.html( html ).show();
		}

		// Render user search results
		function renderUserSearchResults(users, type) {
			var $results = $( '#naticore-user-search-results' );
			var html     = '';

			users.forEach(
				function (user) {
					html += '<div class="naticore-search-result-item" data-id="' + user.id + '" data-type="' + type + '">';
					html += '<strong>' + user.display_name + '</strong>';
					html += ' <span class="naticore-search-user-email">(' + user.user_email + ')</span>';
					html += '</div>';
				}
			);

			$results.html( html ).show();
		}

		// Handle search result clicks
		$( document ).on(
			'click',
			'.naticore-search-result-item',
			function () {
				var $item = $( this );
				var id    = $item.data( 'id' );
				var type  = $item.data( 'type' );
				var title = $item.find( 'strong' ).text();

				// Check if already added
				if ($( '.naticore-related-item[data-id="' + id + '"][data-type="' + type + '"]' ).length > 0) {
					alert( 'This relationship already exists.' );
					return;
				}

				// Add to related items
				var $container = $item.closest( '.naticore-add-relation' ).prev( '.naticore-related-items, p.description' );

				// Create new related item
				var $newItem = $( '<div class="naticore-related-item" data-id="' + id + '" data-type="' + type + '">' );

				// Determine if this is user profile or post editor
				var isUserProfile = $( '#naticore-post-search' ).length > 0;

				if (isUserProfile) {
					// User profile - adding post
					$newItem.append( '<span class="naticore-item-title"><a href="#" target="_blank">' + title + '</a></span>' );
				} else {
					// Post editor - adding user
					var email = $item.find( '.naticore-search-user-email' ).text();
					$newItem.append( '<span class="naticore-item-title"><a href="#" target="_blank">' + title + '</a> ' + email + '</span>' );
				}

				$newItem.append( '<span class="naticore-item-type">' + type + '</span>' );
				$newItem.append( '<button type="button" class="button naticore-remove-relation"><span class="dashicons dashicons-no-alt"></span> ' + naticoreUserRelations.strings.removeRelation + '</button>' );

				// Add to container (create container if it doesn't exist)
				if ($container.hasClass( 'description' )) {
					// Replace "No related items found" message with items container
					var $itemsContainer = $( '<div class="naticore-related-items"></div>' );
					$container.replaceWith( $itemsContainer );
					$itemsContainer.append( $newItem );
				} else {
					$container.append( $newItem );
				}

				// Clear search and hide results
				$item.closest( '.naticore-search-results' ).hide().prev( 'input' ).val( '' );

				// Save relationship via AJAX
				saveUserRelation( id, type, isUserProfile );
			}
		);

		// Remove relation
		$( document ).on(
			'click',
			'.naticore-remove-relation',
			function (e) {
				e.preventDefault();

				if ( ! confirm( naticoreUserRelations.strings.confirmRemove )) {
					return;
				}

				var $item         = $( this ).closest( '.naticore-related-item' );
				var id            = $item.data( 'id' );
				var type          = $item.data( 'type' );
				var isUserProfile = $( '#naticore-post-search' ).length > 0;

				// Remove from DOM
				$item.remove();

				// Check if no items left
				var $container = $( '.naticore-related-items' );
				if ($container.length && $container.children().length === 0) {
					$container.replaceWith( '<p class="description">No related items found.</p>' );
				}

				// Remove relationship via AJAX
				removeUserRelation( id, type, isUserProfile );
			}
		);

		// Save user relation
		function saveUserRelation(id, type, isUserProfile) {
			var postId, userId;

			if (isUserProfile) {
				// User profile: from user to post
				userId = $( '#user_id' ).val() || $( 'input[name="user_id"]' ).val();
				postId = id;
			} else {
				// Post editor: from post to user
				postId = $( '#post_ID' ).val();
				userId = id;
			}

			$.ajax(
				{
					url: naticoreUserRelations.ajaxUrl,
					type: 'POST',
					data: {
						action: 'naticore_add_user_relation',
						from_id: isUserProfile ? userId : postId,
						to_id: isUserProfile ? postId : userId,
						type: type,
						to_type: isUserProfile ? 'post' : 'user',
						nonce: naticoreUserRelations.nonce
					},
					success: function (response) {
						if ( ! response.success) {
							alert( 'Error adding relationship: ' + (response.data || 'Unknown error') );
						}
					},
					error: function () {
						alert( 'Error adding relationship' );
					}
				}
			);
		}

		// Remove user relation
		function removeUserRelation(id, type, isUserProfile) {
			var postId, userId;

			if (isUserProfile) {
				// User profile: from user to post
				userId = $( '#user_id' ).val() || $( 'input[name="user_id"]' ).val();
				postId = id;
			} else {
				// Post editor: from post to user
				postId = $( '#post_ID' ).val();
				userId = id;
			}

			$.ajax(
				{
					url: naticoreUserRelations.ajaxUrl,
					type: 'POST',
					data: {
						action: 'naticore_remove_user_relation',
						from_id: isUserProfile ? userId : postId,
						to_id: isUserProfile ? postId : userId,
						type: type,
						to_type: isUserProfile ? 'post' : 'user',
						nonce: naticoreUserRelations.nonce
					},
					success: function (response) {
						if ( ! response.success) {
							alert( 'Error removing relationship: ' + (response.data || 'Unknown error') );
						}
					},
					error: function () {
						alert( 'Error removing relationship' );
					}
				}
			);
		}

		// Handle enter key in search inputs
		$( '#naticore-post-search, #naticore-user-search' ).on(
			'keypress',
			function (e) {
				if (e.which === 13) {
					e.preventDefault();
					if ($( this ).attr( 'id' ) === 'naticore-post-search') {
						$( '#naticore-add-post-relation' ).click();
					} else {
						$( '#naticore-add-user-relation' ).click();
					}
				}
			}
		);

		// Hide search results when clicking outside
		$( document ).on(
			'click',
			function (e) {
				if ( ! $( e.target ).closest( '.naticore-add-relation, .naticore-search-results' ).length) {
					$( '.naticore-search-results' ).hide();
				}
			}
		);
	}
);
