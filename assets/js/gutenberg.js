/**
 * Gutenberg Block Integration for Native Content Relationships
 *
 * @fileoverview Provides Gutenberg block integration for the Native Content Relationships plugin
 * @package Native Content Relationships
 * @since 1.0.0
 */

(function (blocks, element, editor, components) {
	var el                = element.createElement;
	var registerBlockType = blocks.registerBlockType;
	var InspectorControls = editor.InspectorControls;
	var PanelBody         = components.PanelBody;
	var SelectControl     = components.SelectControl;
	var RangeControl      = components.RangeControl;
	var ToggleControl     = components.ToggleControl;
	var TextControl       = components.TextControl;

	var relationTypes = (typeof naticoreBlockData !== 'undefined' && naticoreBlockData.relationTypes) ? naticoreBlockData.relationTypes : [
		{ label: 'Related To', value: 'related_to' },
		{ label: 'Parent Of', value: 'parent_of' },
		{ label: 'Depends On', value: 'depends_on' },
		{ label: 'References', value: 'references' }
	];

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

				return el(
					'div',
					{ className: 'naticore-related-posts-block-editor' },
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
						'div',
						{ className: 'naticore-placeholder', style: { padding: '20px', background: '#f6f7f7', border: '1px solid #dcdcde', borderRadius: '2px' } },
						el( 'strong', {}, 'Related Content Block' ),
						el( 'p', {}, attributes.limit + ' posts (Type: ' + attributes.relationType + ', Order: ' + attributes.order + ')' )
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
	window.wp.components
);
