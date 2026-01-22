(function(blocks, element, editor, components) {
	var el = element.createElement;
	var registerBlockType = blocks.registerBlockType;
	var InspectorControls = editor.InspectorControls;
	var PanelBody = components.PanelBody;
	var SelectControl = components.SelectControl;
	var RangeControl = components.RangeControl;
	
	var relationTypes = (typeof wpncrBlockData !== 'undefined' && wpncrBlockData.relationTypes) ? wpncrBlockData.relationTypes : [
		{ label: 'Related To', value: 'related_to' },
		{ label: 'Parent Of', value: 'parent_of' },
		{ label: 'Depends On', value: 'depends_on' },
		{ label: 'References', value: 'references' }
	];
	
	registerBlockType('wpncr/related-posts', {
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
			}
		},
		edit: function(props) {
			var attributes = props.attributes;
			var setAttributes = props.setAttributes;
			
			return el('div', { className: 'wpncr-related-posts-block-editor' },
				el(InspectorControls, {},
					el(PanelBody, { title: 'Settings' },
						el(SelectControl, {
							label: 'Relation Type',
							value: attributes.relationType,
							options: relationTypes,
							onChange: function(value) {
								setAttributes({ relationType: value });
							}
						}),
						el(RangeControl, {
							label: 'Number of Posts',
							value: attributes.limit,
							min: 1,
							max: 20,
							onChange: function(value) {
								setAttributes({ limit: value });
							}
						}),
						el(SelectControl, {
							label: 'Order',
							value: attributes.order,
							options: [
								{ label: 'Date', value: 'date' },
								{ label: 'Title', value: 'title' }
							],
							onChange: function(value) {
								setAttributes({ order: value });
							}
						})
					)
				),
				el('div', { className: 'wpncr-placeholder', style: { padding: '20px', background: '#f6f7f7', border: '1px solid #dcdcde', borderRadius: '2px' } },
					el('strong', {}, 'Related Content Block'),
					el('p', {}, attributes.limit + ' posts (Type: ' + attributes.relationType + ', Order: ' + attributes.order + ')')
				)
			);
		},
		save: function() {
			return null; // Server-side rendered
		}
	});
})(
	window.wp.blocks,
	window.wp.element,
	window.wp.editor,
	window.wp.components
);
