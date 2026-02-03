/**
 * Elementor Controls for Native Content Relationships
 *
 * @package Native Content Relationships
 * @since 1.0.11
 */

( function ( $ ) {
	'use strict';

	// Relationship Type Control
	var NCR_RelationshipTypeControl = elementor.modules.controls.BaseData.extend(
		{
			getTemplate: function () {
				return '#tmpl-ncr-relationship-type-control';
			},

			onReady: function () {
				// Initialize select2 if available
				if ( $().select2 ) {
					this.$select = this.$element.find( 'select' ).select2();
				}
			},

			setValue: function ( value ) {
				if ( this.$select ) {
					this.$select.val( value ).trigger( 'change' );
				} else {
					this.$element.find( 'select' ).val( value );
				}
			},

			getValue: function () {
				if ( this.$select ) {
					return this.$select.val();
				} else {
					return this.$element.find( 'select' ).val();
				}
			}
		}
	);

	// Register the control
	elementor.addControlView( 'ncr_relationship_type', NCR_RelationshipTypeControl );

	// Add relationship type options dynamically
	function updateRelationshipTypeOptions( $select, targetType ) {
		if ( ! $select.length ) {
			return;
		}

		// Show loading state
		$select.empty().append( '<option value="">Loading...</option>' );

		// Fetch relationship types via AJAX
		$.ajax(
			{
				url: ajaxurl,
				type: 'POST',
				data: {
					action: 'ncr_get_relationship_types',
					target_type: targetType,
					nonce: ncr_vars.nonce
				},
				success: function ( response ) {
					if ( response.success && response.data ) {
						$select.empty();
						$.each(
							response.data,
							function ( value, label ) {
								$select.append( '<option value="' + value + '">' + label + '</option>' );
							}
						);
					} else {
						$select.empty().append( '<option value="">No types available</option>' );
					}
				},
				error: function () {
					$select.empty().append( '<option value="">Error loading types</option>' );
				}
			}
		);
	}

	// Initialize on elementor ready
	$( document ).on(
		'elementor:init',
		function () {
			// Add event listeners for target type changes
			$( document ).on(
				'change',
				'[data-setting*="target_type"]',
				function () {
					var $this               = $( this );
					var targetType          = $this.val();
					var $relationshipSelect = $this.closest( '.elementor-control-field' ).find( '[data-setting*="relationship_type"] select' );

					if ( $relationshipSelect.length ) {
						updateRelationshipTypeOptions( $relationshipSelect, targetType );
					}
				}
			);
		}
	);

} )( jQuery );
