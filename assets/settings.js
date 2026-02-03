/**
 * Native Content Relationships Settings JavaScript
 * Interactive elements and tab functionality
 */

jQuery(document).ready(function($) {
    'use strict';
    
    // Radio card interactions
    $('.naticore-radio-card').on('click', function() {
        var $card = $(this);
        var $radio = $card.find('input[type="radio"]');
        
        // Remove selected class from all cards in the same group
        $('.naticore-radio-card').removeClass('selected');
        
        // Add selected class to clicked card
        $card.addClass('selected');
        
        // Check the radio button
        $radio.prop('checked', true);
    });
    
    // Checkbox item interactions
    $('.naticore-checkbox-item').on('click', function(e) {
        if (e.target.tagName !== 'INPUT') {
            var $checkbox = $(this).find('input[type="checkbox"]');
            $checkbox.prop('checked', !$checkbox.prop('checked'));
        }
    });
    
    // Toggle switch interactions
    $('.naticore-toggle').on('click', function(e) {
        if (e.target.tagName !== 'INPUT') {
            var $checkbox = $(this).find('input[type="checkbox"]');
            $checkbox.prop('checked', !$checkbox.prop('checked'));
        }
    });
    
    // Tab switching (if needed for future enhancements)
    $('.nav-tab').on('click', function(e) {
        // This is handled by WordPress page reload, but ready for future AJAX tab switching
        return true;
    });
    
    // Initialize selected states on page load
    $('.naticore-radio-card input[type="radio"]:checked').each(function() {
        $(this).closest('.naticore-radio-card').addClass('selected');
    });
    
    // Add hover effects to interactive elements
    $('.naticore-checkbox-item, .naticore-toggle').hover(
        function() {
            $(this).addClass('hover');
        },
        function() {
            $(this).removeClass('hover');
        }
    );
});
