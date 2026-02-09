/**
 * Native Content Relationships Settings JavaScript
 * Interactive elements and tab functionality
 */

jQuery(document).ready(
	function ($) {
		'use strict';

		// Radio card interactions
		$('.naticore-radio-card').on(
			'click',
			function () {
				var $card = $(this);
				var $radio = $card.find('input[type="radio"]');

				// Remove selected class from all cards in the same group
				$('.naticore-radio-card').removeClass('selected');

				// Add selected class to clicked card
				$card.addClass('selected');

				// Check the radio button
				$radio.prop('checked', true);
			}
		);

		// Checkbox item interactions
		$('.naticore-checkbox-item').on(
			'click',
			function (e) {
				if (e.target.tagName !== 'INPUT') {
					var $checkbox = $(this).find('input[type="checkbox"]');
					$checkbox.prop('checked', !$checkbox.prop('checked'));
				}
			}
		);

		// Toggle switch interactions
		$('.naticore-toggle').on(
			'click',
			function (e) {
				if (e.target.tagName !== 'INPUT') {
					var $checkbox = $(this).find('input[type="checkbox"]');
					$checkbox.prop('checked', !$checkbox.prop('checked'));
				}
			}
		);

		// Tab switching - use WordPress core approach (no JavaScript interference)
		$('.nav-tab').on(
			'click',
			function (e) {
				// Let WordPress handle tab navigation naturally
				return true;
			}
		);

		// Initialize selected states on page load
		$('.naticore-radio-card input[type="radio"]:checked').each(
			function () {
				$(this).closest('.naticore-radio-card').addClass('selected');
			}
		);

		// Add hover effects to interactive elements
		$('.naticore-checkbox-item, .naticore-toggle').hover(
			function () {
				$(this).addClass('hover');
			},
			function () {
				$(this).removeClass('hover');
			}
		);

		// Custom Relationship Types - Add Row
		$('#naticore-add-custom-type').on(
			'click',
			function (e) {
				e.preventDefault();
				var $table = $('#naticore-custom-types-table tbody');
				var template = $('#naticore-custom-type-row-template').html();
				var nextId = $table.find('tr').not('.no-items').length;

				// Remove "no items" row if it exists
				$table.find('.no-items').remove();

				var row = template.replace(/{{ID}}/g, 'new_' + nextId);
				$table.append(row);
			}
		);

		// Custom Relationship Types - Remove Row
		$(document).on(
			'click',
			'.naticore-remove-custom-type',
			function (e) {
				e.preventDefault();
				$(this).closest('tr').remove();

				var $table = $('#naticore-custom-types-table tbody');
				if ($table.find('tr').length === 0) {
					$table.append('<tr class="no-items"><td colspan="4">No custom types defined.</td></tr>');
				}
			}
		);
	}
);
