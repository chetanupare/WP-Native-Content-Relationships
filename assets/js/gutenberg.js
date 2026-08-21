/**
 * Gutenberg Block Integration for Native Content Relationships
 *
 * @fileoverview Provides Gutenberg block integration for the Native Content Relationships plugin
 * @package Native Content Relationships
 * @since 1.0.0
 */

(function (blocks, element, editor, components, apiFetch) {
	var el                = element.createElement;
	var registerBlockType = blocks.registerBlockType;
	var InspectorControls = editor.InspectorControls;
	var PanelBody         = components.PanelBody;
	var SelectControl     = components.SelectControl;
	var RangeControl      = components.RangeControl;
	var ToggleControl     = components.ToggleControl;
	var TextControl       = components.TextControl;
	var useBlockProps     = editor.useBlockProps;
	var useState          = element.useState;
	var useEffect         = element.useEffect;

	var relationTypes = (typeof naticoreBlockData !== 'undefined' && naticoreBlockData.relationTypes) ? naticoreBlockData.relationTypes : [
		{ label: 'Related To', value: 'related_to' },
		{ label: 'Parent Of', value: 'parent_of' },
		{ label: 'Depends On', value: 'depends_on' },
		{ label: 'References', value: 'references' }
	];

	function BlockPreview(props) {
		var attributes    = props.attributes;
		var postId        = props.postId;
		var posts         = useState([])[0];
		var setPosts      = useState([])[1];
		var loading       = useState(true)[0];
		var setLoading    = useState(true)[1];

		useEffect(function() {
			setLoading(true);
			apiFetch({
				path: '/naticore/v1/post/' + postId + '?relation_type=' + attributes.relationType + '&per_page=' + attributes.limit,
			}).then(function(response) {
				var posts = [];
				if (response && response.data && response.data.relationships) {
					posts = response.data.relationships.map(function(rel) {
						return {
							id: rel.to_id,
							title: { rendered: rel.title || 'Untitled' },
							link: rel.url || '#',
							thumbnail: rel.thumbnail || '',
							excerpt: { rendered: rel.excerpt || '' }
						};
					});
				}
				setPosts(posts);
				setLoading(false);
			}).catch(function() {
				setPosts([]);
				setLoading(false);
			});
		}, [postId, attributes.relationType, attributes.limit]);

		if (loading) {
			return el('div', { style: { padding: '20px', background: '#f6f7f7', border: '1px solid #dcdcde', borderRadius: '2px' } },
				el('span', { className: 'spinner is-active', style: { float: 'none', margin: '0 auto' } }),
				el('p', { style: { textAlign: 'center', marginTop: '10px' } }, 'Loading related content...')
			);
		}

		if (posts.length === 0) {
			return el('div', { style: { padding: '20px', background: '#f6f7f7', border: '1px dashed #dcdcde', borderRadius: '2px', textAlign: 'center' } },
				el('p', { style: { color: '#646970', margin: 0 } }, 'No related content found. Add relationships in the post editor.')
			);
		}

		var items = posts.map(function(post) {
			var children = [];
			if (attributes.showThumbnail && post.thumbnail) {
				children.push(el('img', {
					src: post.thumbnail,
					alt: '',
					style: { width: '60px', height: '60px', objectFit: 'cover', borderRadius: '2px', marginRight: '10px', flexShrink: 0 }
				}));
			}
			var contentChildren = [
				el('a', {
					href: post.link,
					style: { color: '#2271b1', fontWeight: 500, textDecoration: 'none' }
				}, post.title.rendered || post.title)
			];
			if (attributes.excerptLength > 0 && post.excerpt) {
				var excerpt = post.excerpt.rendered || post.excerpt;
				excerpt = excerpt.replace(/<[^>]*>/g, '').substring(0, attributes.excerptLength * 10);
				contentChildren.push(el('p', {
					style: { color: '#646970', fontSize: '13px', margin: '4px 0 0' }
				}, excerpt + '...'));
			}
			children.push(el('div', {}, contentChildren));
			return el('li', {
				key: post.id,
				style: {
					display: 'flex',
					alignItems: 'flex-start',
					padding: '8px 0',
					borderBottom: '1px solid #eee'
				}
			}, children);
		});

		return el('ul', {
			style: { listStyle: 'none', padding: 0, margin: 0, background: '#fff', border: '1px solid #dcdcde', borderRadius: '2px' }
		}, items);
	}

	registerBlockType(
		'naticore/related-posts',
		{
			title: 'Related Content',
			icon: 'admin-links',
			category: 'widgets',
			attributes: {
				relationType: {
					type: 'string',
					default: 'related_to'
				},
				limit: {
					type: 'number',
					default: 5
				},
				order: {
					type: 'string',
					default: 'date'
				},
				layout: {
					type: 'string',
					default: 'list'
				},
				showThumbnail: {
					type: 'boolean',
					default: false
				},
				excerptLength: {
					type: 'number',
					default: 0
				},
				wrapperClass: {
					type: 'string',
					default: ''
				}
			},
			edit: function (props) {
				var attributes    = props.attributes;
				var setAttributes = props.setAttributes;
				var blockProps    = useBlockProps();

				return el(
					'div',
					blockProps,
					el(
						InspectorControls,
						{},
						el(
							PanelBody,
							{ title: 'Settings' },
							el(
								SelectControl,
								{
									label: 'Relation Type',
									value: attributes.relationType,
									options: relationTypes,
									onChange: function (value) {
										setAttributes( { relationType: value } );
									}
								}
							),
							el(
								RangeControl,
								{
									label: 'Number of Posts',
									value: attributes.limit,
									min: 1,
									max: 20,
									onChange: function (value) {
										setAttributes( { limit: value } );
									}
								}
							),
							el(
								SelectControl,
								{
									label: 'Order',
									value: attributes.order,
									options: [
										{ label: 'Date', value: 'date' },
										{ label: 'Title', value: 'title' }
									],
									onChange: function (value) {
										setAttributes( { order: value } );
									}
								}
							),
							el(
								SelectControl,
								{
									label: 'Layout',
									value: attributes.layout || 'list',
									options: [
										{ label: 'List', value: 'list' },
										{ label: 'Grid', value: 'grid' }
									],
									onChange: function (value) {
										setAttributes( { layout: value } );
									}
								}
							),
							el(
								ToggleControl,
								{
									label: 'Show thumbnail',
									checked: attributes.showThumbnail || false,
									onChange: function (value) {
										setAttributes( { showThumbnail: value } );
									}
								}
							),
							el(
								RangeControl,
								{
									label: 'Excerpt length (0 = hide)',
									value: attributes.excerptLength || 0,
									min: 0,
									max: 30,
									onChange: function (value) {
										setAttributes( { excerptLength: value } );
									}
								}
							),
							el(
								TextControl,
								{
									label: 'Wrapper CSS class',
									value: attributes.wrapperClass || '',
									onChange: function (value) {
										setAttributes( { wrapperClass: value } );
									}
								}
							)
						)
					),
					el(
						BlockPreview,
						{
							attributes: attributes,
							postId: typeof naticoreBlockData !== 'undefined' && naticoreBlockData.postId ? naticoreBlockData.postId : 0
						}
					)
				);
			},
			save: function () {
				return null; // Server-side rendered
			}
		}
	);
})(
	window.wp.blocks,
	window.wp.element,
	window.wp.editor,
	window.wp.components,
	window.wp.apiFetch
);
