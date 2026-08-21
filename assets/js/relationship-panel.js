/**
 * Gutenberg Relationship Sidebar Panel
 *
 * PluginDocumentSettingPanel for managing post-to-X relationships in the
 * Gutenberg editor. Uses PHP bootstrap data for initial state and delegates
 * all mutations to existing REST API / AJAX endpoints.
 *
 * Phase 3: Server-side pagination, relationship search, edit links, cardinality.
 *
 * @fileoverview No build step — plain IIFE using window.wp globals.
 * @package NativeContentRelationships
 * @since 1.4.0
 */

(function (plugins, editPost, element, components, apiFetch) {
	'use strict';

	if ( ! plugins || ! editPost || ! element ) {
		return;
	}

	var el               = element.createElement;
	var useState         = element.useState;
	var useEffect        = element.useEffect;
	var useRef           = element.useRef;
	var Fragment         = element.Fragment;
	var PanelBody        = components.PanelBody;
	var SelectControl    = components.SelectControl;
	var TextControl      = components.TextControl;
	var Button           = components.Button;
	var Spinner          = components.Spinner;
	var Tooltip          = components.Tooltip;
	var Notice           = components.Notice;

	var data = ( typeof naticorePanelData !== 'undefined' ) ? naticorePanelData : null;
	if ( ! data || ! data.canEdit ) {
		return;
	}

	var PANEL_NAME = 'naticore-relationship-panel';
	var PER_PAGE   = 5;
	var SEARCH_THRESHOLD = 10;

	// -------------------------------------------------------------------------
	// Search service — Object Search (AJAX)
	// -------------------------------------------------------------------------

	function getSearchAction( toType ) {
		switch ( toType ) {
			case 'user':    return 'naticore_search_users';
			case 'product': return 'naticore_search_products';
			default:        return 'naticore_search_content';
		}
	}

	function ajaxSearch( searchTerm, ajaxAction, ajaxNonce, ajaxUrl, currentPostId ) {
		return new Promise( function ( resolve, reject ) {
			if ( typeof jQuery === 'undefined' ) {
				reject( new Error( 'jQuery not available' ) );
				return;
			}
			jQuery.ajax( {
				url: ajaxUrl,
				type: 'POST',
				data: {
					action: ajaxAction,
					nonce: ajaxNonce,
					search: searchTerm,
					current_post_id: currentPostId,
				},
				success: function ( response ) {
					resolve( ( response.success && response.data ) ? response.data : [] );
				},
				error: function () { reject( new Error( 'AJAX error' ) ); },
			} );
		} );
	}

	function normalizeSearchResults( items, toType ) {
		if ( ! items || ! items.length ) { return []; }
		return items.map( function ( item ) {
			if ( 'user' === toType ) {
				return {
					id: item.id, title: item.display_name || item.user_login || '',
					secondaryLabel: item.user_login || '', type: 'user', editLink: '', thumbnail: '',
				};
			}
			return {
				id: item.id, title: item.title || '',
				secondaryLabel: ( 'product' === toType && item.sku ) ? 'SKU: ' + item.sku : ( item.type || '' ),
				type: item.type || toType, editLink: item.url || '', thumbnail: item.thumbnail || '',
			};
		} );
	}

	// -------------------------------------------------------------------------
	// RelationshipItem — single relationship row
	// -------------------------------------------------------------------------

	function RelationshipItem( props ) {
		var item       = props.item;
		var typeInfo   = props.typeInfo;
		var onRemove   = props.onRemove;
		var removing   = props.removing;
		var i18n       = props.i18n;

		var direction = typeInfo.bidirectional ? '\u2194' : '\u2192';

		var children = [
			el( 'span', {
				className: 'ncr-panel-item-direction',
				title: typeInfo.bidirectional ? i18n.bidirectionalHint : '',
			}, direction ),
			el( 'span', { className: 'ncr-panel-item-title' },
				item.editLink
					? el( 'a', { href: item.editLink, target: '_blank', rel: 'noopener noreferrer' }, item.title )
					: item.title
			),
		];

		if ( item.postType ) {
			children.push( el( 'span', { className: 'ncr-panel-item-type' }, item.postType ) );
		}

		// Edit/open link.
		if ( item.editLink ) {
			children.push(
				el( Tooltip, { text: i18n.editTarget },
					el( 'a', {
						className: 'ncr-panel-item-edit',
						href: item.editLink,
						target: '_blank',
						rel: 'noopener noreferrer',
						'aria-label': i18n.editTarget + ': ' + item.title,
					}, '\u2197' )
				)
			);
		}

		children.push(
			el( Button, {
				className: 'ncr-panel-item-remove',
				onClick: function () { onRemove( item ); },
				disabled: removing,
				label: i18n.remove + ': ' + item.title,
				isSmall: true,
			}, '\u00d7' )
		);

		return el( 'div', {
			className: 'ncr-panel-item' + ( removing ? ' ncr-panel-item--removing' : '' ),
		}, children );
	}

	// -------------------------------------------------------------------------
	// RelationshipGroup — a group with pagination, search, cardinality
	// -------------------------------------------------------------------------

	function RelationshipGroup( props ) {
		var group      = props.group;
		var typeSlug   = props.typeSlug;
		var onRemove   = props.onRemove;
		var removingId = props.removingId;
		var onLoadMore = props.onLoadMore;
		var onSearch   = props.onSearch;
		var i18n       = props.i18n;

		var pagination  = group.pagination || { page: 1, hasMore: false, loading: false };
		var searchState = group.search || { term: '', loading: false, searching: false };
		var total       = group.total || 0;
		var maxConn     = group.maxConnections || 0;
		var items       = group.items || [];
		var showSearch  = total >= SEARCH_THRESHOLD;

		// Cardinality text.
		var cardinalityText = '';
		if ( maxConn > 0 ) {
			cardinalityText = total + ' of ' + maxConn + ' connections';
		} else {
			cardinalityText = total + ( 1 === total ? ' connection' : ' connections' );
		}

		var atMax = maxConn > 0 && total >= maxConn;

		return el(
			'div',
			{ className: 'ncr-panel-group' },
			// Group header.
			el( 'div', { className: 'ncr-panel-group-header' },
				el( 'span', { className: 'ncr-panel-group-label' }, group.label ),
				group.bidirectional
					? el( Tooltip, { text: i18n.bidirectionalHint },
						el( 'span', {
							className: 'ncr-panel-group-badge',
							tabIndex: 0,
							role: 'img',
							'aria-label': i18n.bidirectionalHint,
						}, '\u2194' )
					)
					: null,
				el( 'span', { className: 'ncr-panel-group-count' }, cardinalityText )
			),
			// Items.
			items.map( function ( item ) {
				return el( RelationshipItem, {
					key:      item.id + '-' + typeSlug,
					item:     item,
					typeInfo: group,
					onRemove: onRemove,
					removing: removingId === item.id,
					i18n:     i18n,
				} );
			} ),
			// Loading more.
			pagination.loading
				? el( 'div', { className: 'ncr-panel-group-loading', 'aria-live': 'polite' },
					el( Spinner, { size: 16 } ),
					' ',
					i18n.loadingMore
				)
				: null,
			// Show More.
			( ! pagination.loading && pagination.hasMore && ! searchState.searching )
				? el( 'div', { className: 'ncr-panel-show-more' },
					el( Button, {
						variant: 'link',
						onClick: function () { onLoadMore( typeSlug ); },
					}, i18n.showMore )
				)
				: null,
			// Search field.
			( showSearch && ! searchState.searching && ! pagination.loading )
				? el( 'div', { className: 'ncr-panel-group-search' },
					el( TextControl, {
						placeholder: i18n.searchPlaceholder,
						value: '',
						onChange: function ( val ) {
							if ( val.length >= 2 ) {
								onSearch( typeSlug, val );
							}
						},
						__nextHasNoMarginBottom: true,
					} )
				)
				: null,
			// Active search.
			searchState.searching
				? el( 'div', { className: 'ncr-panel-group-search-active' },
					el( TextControl, {
						placeholder: i18n.searchPlaceholder,
						value: searchState.term,
						onChange: function ( val ) {
							if ( val.length >= 2 ) {
								onSearch( typeSlug, val );
							} else if ( val.length === 0 ) {
								onSearch( typeSlug, '' );
							}
						},
						__nextHasNoMarginBottom: true,
					} ),
					searchState.loading
						? el( Spinner, { size: 16 } )
						: null
				)
				: null,
			// Search empty.
			( searchState.searching && ! searchState.loading && items.length === 0 )
				? el( 'div', { className: 'ncr-panel-group-search-empty' }, i18n.noResults )
				: null
		);
	}

	// -------------------------------------------------------------------------
	// SearchResultItem — a single search result row
	// -------------------------------------------------------------------------

	function SearchResultItem( props ) {
		var item      = props.item;
		var creating  = props.creating;
		var onSelect  = props.onSelect;
		var connected = props.connected;

		var className = 'ncr-panel-add-form-result';
		if ( connected ) { className += ' ncr-panel-add-form-result--connected'; }

		var children = [];
		if ( item.thumbnail ) {
			children.push( el( 'img', {
				className: 'ncr-panel-add-form-result-thumb',
				src: item.thumbnail, alt: '', 'aria-hidden': 'true',
			} ) );
		}

		var textChildren = [ el( 'span', { className: 'ncr-panel-add-form-result-title' }, item.title ) ];
		if ( item.secondaryLabel ) {
			textChildren.push( el( 'span', { className: 'ncr-panel-add-form-result-secondary' }, item.secondaryLabel ) );
		}
		children.push( el( 'span', { className: 'ncr-panel-add-form-result-text' }, textChildren ) );

		if ( connected ) {
			children.push( el( 'span', { className: 'ncr-panel-add-form-result-badge' }, 'Connected' ) );
		}

		return el( 'div', {
			className: className,
			onClick: function () { if ( ! creating && ! connected ) { onSelect( item ); } },
			onKeyDown: function ( e ) {
				if ( ( e.key === 'Enter' || e.key === ' ' ) && ! creating && ! connected ) {
					e.preventDefault(); onSelect( item );
				}
			},
			role: 'option', tabIndex: 0, 'aria-disabled': creating || connected,
		}, children );
	}

	// -------------------------------------------------------------------------
	// AddRelationshipForm — inline add relationship flow
	// -------------------------------------------------------------------------

	function AddRelationshipForm( props ) {
		var types        = props.types;
		var existingRels = props.existingRels;
		var onCancel     = props.onCancel;
		var onCreated    = props.onCreated;
		var postId       = props.postId;
		var i18n         = props.i18n;

		var availableTypes = Object.keys( types );
		var typeOptions    = availableTypes.map( function ( slug ) {
			return { label: types[ slug ].label, value: slug };
		} );

		var selectedTypeState = useState( availableTypes.length > 0 ? availableTypes[ 0 ] : '' );
		var selectedType      = selectedTypeState[ 0 ];
		var setSelectedType   = selectedTypeState[ 1 ];

		var searchTermState   = useState( '' );
		var searchTerm        = searchTermState[ 0 ];
		var setSearchTerm     = searchTermState[ 1 ];

		var resultsState      = useState( [] );
		var results           = resultsState[ 0 ];
		var setResults        = resultsState[ 1 ];

		var loadingState      = useState( false );
		var loading           = loadingState[ 0 ];
		var setLoading        = loadingState[ 1 ];

		var creatingState     = useState( false );
		var creating          = creatingState[ 0 ];
		var setCreating       = creatingState[ 1 ];

		var errorState        = useState( '' );
		var error             = errorState[ 0 ];
		var setError          = errorState[ 1 ];

		var debounceRef       = useRef( null );
		var searchIdRef       = useRef( 0 );

		var currentTypeInfo = types[ selectedType ] || {};
		var toType          = currentTypeInfo.to || 'post';
		var searchAction    = getSearchAction( toType );
		var searchLabel     = 'post' === toType ? i18n.searchPlaceholder : 'Search ' + toType + 's...';

		var connectedIds = {};
		if ( existingRels && existingRels[ selectedType ] ) {
			existingRels[ selectedType ].items.forEach( function ( r ) { connectedIds[ r.id ] = true; } );
		}

		useEffect( function () {
			if ( debounceRef.current ) { clearTimeout( debounceRef.current ); }
			if ( searchTerm.length < 2 ) { setResults( [] ); setLoading( false ); return; }
			setLoading( true ); setError( '' );
			var thisSearchId = ++searchIdRef.current;
			debounceRef.current = setTimeout( function () {
				ajaxSearch( searchTerm, searchAction, data.ajaxNonce, data.ajaxUrl, postId )
					.then( function ( items ) {
						if ( thisSearchId !== searchIdRef.current ) { return; }
						setResults( normalizeSearchResults( items, toType ) );
						setLoading( false );
					} )
					.catch( function () {
						if ( thisSearchId !== searchIdRef.current ) { return; }
						setResults( [] ); setLoading( false );
					} );
			}, 300 );
			return function () { if ( debounceRef.current ) { clearTimeout( debounceRef.current ); } };
		}, [ searchTerm, selectedType ] );

		function handleCreate( item ) {
			setCreating( true ); setError( '' );
			apiFetch( {
				path: data.restBase + '/relationships',
				method: 'POST',
				data: { from_id: postId, to_id: item.id, type: selectedType, to_type: toType },
			} ).then( function ( response ) {
				setCreating( false ); setResults( [] ); setSearchTerm( '' );
				if ( response && response.relation_id ) {
					onCreated( {
						id: item.id, type: selectedType, to_type: toType,
						title: item.title, postType: item.secondaryLabel || item.type || '',
						editLink: item.editLink || '',
					} );
				}
			} ).catch( function ( err ) {
				setCreating( false );
				var msg = i18n.errorDefault;
				if ( err && err.data && err.data.message ) { msg = err.data.message; }
				else if ( err && err.code ) {
					switch ( err.code ) {
						case 'naticore_insufficient_permissions': msg = i18n.errorPermission; break;
						case 'ncr_invalid_type': msg = i18n.errorInvalidType; break;
						case 'ncr_relation_exists': msg = i18n.errorDuplicate; break;
						case 'ncr_invalid_to_type': msg = i18n.errorTypeMismatch; break;
					}
				}
				setError( msg );
			} );
		}

		if ( availableTypes.length === 0 ) {
			return el( 'div', { className: 'ncr-panel-add-form' },
				el( 'p', { className: 'ncr-panel-add-form-notice' }, 'No relationship types available for this content.' ),
				el( 'div', { className: 'ncr-panel-add-form-actions' },
					el( Button, { variant: 'secondary', onClick: onCancel }, i18n.cancel )
				)
			);
		}

		return el( 'div', { className: 'ncr-panel-add-form' },
			el( 'div', { className: 'ncr-panel-add-form-row' },
				el( SelectControl, {
					label: 'Relationship Type', value: selectedType, options: typeOptions,
					onChange: function ( val ) { setSelectedType( val ); setSearchTerm( '' ); setResults( [] ); setError( '' ); },
					disabled: creating,
				} )
			),
			el( 'div', { className: 'ncr-panel-add-form-row' },
				el( TextControl, {
					label: searchLabel, value: searchTerm,
					onChange: function ( val ) { setSearchTerm( val ); },
					placeholder: searchLabel, disabled: creating, autoFocus: true,
				} )
			),
			loading ? el( 'div', { className: 'ncr-panel-add-form-loading', 'aria-live': 'polite' }, el( Spinner, null ), ' ', i18n.searching ) : null,
			error ? el( Notice, { status: 'error', isDismissible: false }, error ) : null,
			( ! loading && searchTerm.length > 0 && searchTerm.length < 2 )
				? el( 'div', { className: 'ncr-panel-add-form-hint' }, 'Enter at least 2 characters.' ) : null,
			results.length > 0
				? el( 'div', { className: 'ncr-panel-add-form-results', role: 'listbox', 'aria-label': searchLabel },
					results.map( function ( item ) {
						return el( SearchResultItem, {
							key: item.id, item: item, creating: creating,
							connected: !! connectedIds[ item.id ], onSelect: handleCreate,
						} );
					} )
				) : null,
			( ! loading && searchTerm.length >= 2 && results.length === 0 && ! error )
				? el( 'div', { className: 'ncr-panel-add-form-empty', 'aria-live': 'polite' }, i18n.noResults ) : null,
			el( 'div', { className: 'ncr-panel-add-form-actions' },
				el( Button, { variant: 'secondary', onClick: onCancel, disabled: creating }, i18n.cancel )
			)
		);
	}

	// -------------------------------------------------------------------------
	// RelationshipPanel — main panel component
	// -------------------------------------------------------------------------

	function RelationshipPanel() {
		// Per-group data: { [typeSlug]: { items, total, maxConnections, label, bidirectional,
		//   pagination: { page, hasMore, loading }, search: { term, loading, searching } } }
		var initialGroups = {};
		if ( data.relationships ) {
			Object.keys( data.relationships ).forEach( function ( slug ) {
				var g = data.relationships[ slug ];
				initialGroups[ slug ] = {
					label:          g.label,
					bidirectional:  g.bidirectional,
					items:          g.items || [],
					total:          g.total || 0,
					maxConnections: g.maxConnections || 0,
					pagination:     { page: 1, hasMore: ( g.items || [] ).length < ( g.total || 0 ), loading: false },
					search:         { term: '', loading: false, searching: false },
				};
			} );
		}

		var groupsState     = useState( initialGroups );
		var groups          = groupsState[ 0 ];
		var setGroups       = groupsState[ 1 ];

		var showAddState    = useState( false );
		var showAdd         = showAddState[ 0 ];
		var setShowAdd      = showAddState[ 1 ];

		var removingState   = useState( null );
		var removingId      = removingState[ 0 ];
		var setRemovingId   = removingState[ 1 ];

		var noticeState     = useState( null );
		var notice          = noticeState[ 0 ];
		var setNotice       = noticeState[ 1 ];

		var addBtnRef       = useRef( null );
		var searchDebounces = useRef( {} );

		// Fetch a page of relationships for a specific type.
		function fetchPage( typeSlug, page, searchTerm ) {
			setGroups( function ( prev ) {
				var updated = Object.assign( {}, prev );
				if ( ! updated[ typeSlug ] ) { return prev; }
				var g = Object.assign( {}, updated[ typeSlug ] );
				g.pagination = Object.assign( {}, g.pagination, { loading: true } );
				if ( typeof searchTerm !== 'undefined' ) {
					// Search: clear items so stale results don't flash during loading.
					g.search = { term: searchTerm || '', loading: true, searching: !! searchTerm };
					if ( searchTerm ) { g.items = []; }
				}
				updated[ typeSlug ] = g;
				return updated;
			} );

			var path = data.restBase + '/post/' + data.postId + '/type/' + typeSlug + '?page=' + page + '&per_page=' + PER_PAGE;
			if ( searchTerm ) {
				path += '&search=' + encodeURIComponent( searchTerm );
			}

			apiFetch( { path: path } ).then( function ( response ) {
				setGroups( function ( prev ) {
					var updated = Object.assign( {}, prev );
					if ( ! updated[ typeSlug ] ) { return prev; }
					var g = Object.assign( {}, updated[ typeSlug ] );

					var newItems = ( 1 === page ) ? response.items : g.items.concat( response.items );
					g.items      = newItems;
					g.total      = response.total;
					g.pagination = {
						page:    response.page,
						hasMore: newItems.length < response.total,
						loading: false,
					};
					if ( searchTerm ) {
						g.search = { term: searchTerm, loading: false, searching: true };
					} else {
						g.search = { term: '', loading: false, searching: false };
					}
					updated[ typeSlug ] = g;
					return updated;
				} );
			} ).catch( function () {
				setGroups( function ( prev ) {
					var updated = Object.assign( {}, prev );
					if ( ! updated[ typeSlug ] ) { return prev; }
					var g = Object.assign( {}, updated[ typeSlug ] );
					g.pagination = Object.assign( {}, g.pagination, { loading: false } );
					g.search = Object.assign( {}, g.search, { loading: false } );
					updated[ typeSlug ] = g;
					return updated;
				} );
				setNotice( { status: 'error', content: data.i18n.errorDefault } );
				setTimeout( function () { setNotice( null ); }, 6000 );
			} );
		}

		function handleLoadMore( typeSlug ) {
			fetchPage( typeSlug, ( groups[ typeSlug ]?.pagination?.page || 1 ) + 1 );
		}

		function handleSearch( typeSlug, term ) {
			if ( searchDebounces.current[ typeSlug ] ) {
				clearTimeout( searchDebounces.current[ typeSlug ] );
			}
			if ( ! term ) {
				// Clear search → reset to page 1.
				fetchPage( typeSlug, 1 );
				return;
			}
			searchDebounces.current[ typeSlug ] = setTimeout( function () {
				fetchPage( typeSlug, 1, term );
			}, 300 );
		}

		function handleRemove( item ) {
			if ( removingId ) { return; }
			setRemovingId( item.id );
			apiFetch( {
				path: data.restBase + '/relationships',
				method: 'DELETE',
				data: { from_id: data.postId, to_id: item.id, type: item.type, to_type: item.to_type },
			} ).then( function () {
				setGroups( function ( prev ) {
					var updated = Object.assign( {}, prev );
					var g = updated[ item.type ];
					if ( g ) {
						g = Object.assign( {}, g );
						g.items = g.items.filter( function ( r ) { return r.id !== item.id; } );
						g.total = Math.max( 0, g.total - 1 );
						g.pagination = Object.assign( {}, g.pagination, {
							hasMore: g.items.length < g.total,
						} );
						updated[ item.type ] = g;
					}
					return updated;
				} );
				setRemovingId( null );
				if ( addBtnRef.current ) { addBtnRef.current.focus(); }
			} ).catch( function ( err ) {
				setRemovingId( null );
				var msg = data.i18n.errorDefault;
				if ( err && err.data && err.data.message ) { msg = err.data.message; }
				setNotice( { status: 'error', content: msg } );
				setTimeout( function () { setNotice( null ); }, 6000 );
			} );
		}

		function handleCreated( newItem ) {
			setGroups( function ( prev ) {
				var updated = Object.assign( {}, prev );
				var type    = newItem.type;

				if ( ! updated[ type ] ) {
					updated[ type ] = {
						label: data.types[ type ] ? data.types[ type ].label : type,
						bidirectional: data.types[ type ] ? data.types[ type ].bidirectional : false,
						items: [], total: 0,
						maxConnections: data.types[ type ] ? data.types[ type ].max_connections : 0,
						pagination: { page: 1, hasMore: false, loading: false },
						search: { term: '', loading: false, searching: false },
					};
				}

				var g = Object.assign( {}, updated[ type ] );
				var exists = g.items.some( function ( r ) { return r.id === newItem.id && r.type === newItem.type; } );
				if ( ! exists ) {
					g.items = [ newItem ].concat( g.items );
					g.total = g.total + 1;
					g.pagination = Object.assign( {}, g.pagination, {
						hasMore: g.items.length < g.total,
					} );
				}
				updated[ type ] = g;
				return updated;
			} );
			setShowAdd( false );
			setTimeout( function () { if ( addBtnRef.current ) { addBtnRef.current.focus(); } }, 50 );
		}

		function handleCancelAdd() {
			setShowAdd( false );
			setTimeout( function () { if ( addBtnRef.current ) { addBtnRef.current.focus(); } }, 50 );
		}

		var groupKeys = Object.keys( groups );
		var isEmpty   = groupKeys.length === 0 && ! showAdd;

		var panelChildren = [];

		if ( notice ) {
			panelChildren.push( el( Notice, { key: 'notice', status: notice.status, isDismissible: false }, notice.content ) );
		}

		if ( isEmpty ) {
			panelChildren.push(
				el( 'div', { key: 'empty', className: 'ncr-panel-empty' },
					el( 'p', null, data.i18n.noRelationships ),
					el( 'p', { className: 'ncr-panel-empty-desc' }, 'Connect this content to other WordPress content.' )
				)
			);
		}

		groupKeys.forEach( function ( typeSlug ) {
			panelChildren.push(
				el( RelationshipGroup, {
					key:        typeSlug,
					typeSlug:   typeSlug,
					group:      groups[ typeSlug ],
					onRemove:   handleRemove,
					removingId: removingId,
					onLoadMore: handleLoadMore,
					onSearch:   handleSearch,
					i18n:       data.i18n,
				} )
			);
		} );

		if ( showAdd ) {
			panelChildren.push(
				el( AddRelationshipForm, {
					key: 'add-form', types: data.types, existingRels: groups,
					onCancel: handleCancelAdd, onCreated: handleCreated,
					postId: data.postId, i18n: data.i18n,
				} )
			);
		} else {
			panelChildren.push(
				el( Button, {
					key: 'add-btn', ref: addBtnRef, className: 'ncr-panel-add-btn',
					onClick: function () { setShowAdd( true ); },
					variant: 'secondary', isSmall: true,
				}, '+ ' + data.i18n.addRelationship )
			);
		}

		return el( PanelBody, {
			title: data.i18n.panelTitle, initialOpen: true, className: 'ncr-panel',
		}, panelChildren );
	}

	// -------------------------------------------------------------------------
	// Register the plugin
	// -------------------------------------------------------------------------

	plugins.registerPlugin( PANEL_NAME, {
		icon:   'admin-links',
		render: RelationshipPanel,
	} );

})(
	window.wp.plugins,
	window.wp.editPost,
	window.wp.element,
	window.wp.components,
	window.wp.apiFetch
);
