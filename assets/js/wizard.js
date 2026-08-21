/**
 * Native Content Relationships - Setup Wizard
 * Interactive step-by-step onboarding flow.
 *
 * @package NativeContentRelationships
 * @since 1.5.0
 */
(function ($) {
  'use strict';

  var NCWizard = {

    currentStep: '',
    steps: [],
    state: {},

    init: function () {
      var $wizard = $('#nc-wizard');
      if (!$wizard.length) return;

      this.currentStep = $wizard.data('step');
      this.steps = ncWizardData.steps || [];
      this.state = ncWizardData.state || {};

      this.bindEvents();
      this.syncStateToUI();
    },

    bindEvents: function () {
      // Next button
      $(document).on('click', '#nc-wizard-next', this.handleNext);

      // Back button
      $(document).on('click', '#nc-wizard-back', this.handleBack);

      // Skip button
      $(document).on('click', '#nc-wizard-skip', this.handleSkip);
      $(document).on('click', '#nc-wizard-skip-confirm', this.handleSkipConfirm);
      $(document).on('click', '#nc-wizard-skip-cancel', this.handleSkipCancel);

      // Finish button
      $(document).on('click', '#nc-wizard-finish', this.handleFinish);

      // Goto step buttons (review edit links)
      $(document).on('click', '.nc-wizard-goto-step', this.handleGotoStep);

      // Type card toggle
      $(document).on('change', '.nc-wizard-type-checkbox', this.handleTypeToggle);

      // Preset card toggle
      $(document).on('change', '.nc-wizard-preset-checkbox', this.handlePresetToggle);

      // Post type checkbox toggle
      $(document).on('change', '.nc-wizard-posttype-checkbox', this.handlePostTypeToggle);

      // Add custom type button
      $(document).on('click', '#nc-wizard-add-type', this.handleAddType);
      $(document).on('click', '#nc-new-type-save', this.handleSaveNewType);
      $(document).on('click', '#nc-new-type-cancel', this.handleCancelNewType);
      $(document).on('keydown', '#nc-new-type-label', function (e) {
        if (e.key === 'Enter') { NCWizard.handleSaveNewType(e); }
        if (e.key === 'Escape') { NCWizard.handleCancelNewType(e); }
      });
    },

    // ===== Navigation =====

    handleNext: function (e) {
      e.preventDefault();
      NCWizard.saveCurrentStep(function () {
        var nextStep = NCWizard.getNextStep();
        if (nextStep) {
          NCWizard.navigateToStep(nextStep);
        }
      });
    },

    handleBack: function (e) {
      e.preventDefault();
      var prevStep = NCWizard.getPrevStep();
      if (prevStep) {
        NCWizard.navigateToStep(prevStep);
      }
    },

    handleSkip: function (e) {
      e.preventDefault();
      $('#nc-wizard-skip-dialog').addClass('open');
    },

    handleSkipConfirm: function (e) {
      e.preventDefault();
      $('#nc-wizard-skip-dialog').removeClass('open');
      $.post(ncWizardData.ajaxUrl, {
        action: 'naticore_wizard_skip',
        nonce: ncWizardData.nonce
      }, function (resp) {
        if (resp.success && resp.data.redirect) {
          window.location.href = resp.data.redirect;
        } else {
          window.location.href = '/wp-admin/admin.php?page=naticore-settings';
        }
      }).fail(function () {
        window.location.href = '/wp-admin/admin.php?page=naticore-settings';
      });
    },

    handleSkipCancel: function (e) {
      e.preventDefault();
      $('#nc-wizard-skip-dialog').removeClass('open');
    },

    handleFinish: function (e) {
      e.preventDefault();
      var $btn = $(e.currentTarget);
      $btn.prop('disabled', true).css('opacity', '0.6');

      NCWizard.saveCurrentStep(function () {
        $.post(ncWizardData.ajaxUrl, {
          action: 'naticore_wizard_complete',
          nonce: ncWizardData.nonce
        }, function (resp) {
          if (resp.success && resp.data.redirect) {
            window.location.href = resp.data.redirect;
          } else {
            $btn.prop('disabled', false).css('opacity', '1');
            alert('Error completing setup. Please try again.');
          }
        }).fail(function () {
          $btn.prop('disabled', false).css('opacity', '1');
          alert('Network error.');
        });
      });
    },

    handleGotoStep: function (e) {
      e.preventDefault();
      var target = $(e.currentTarget).data('goto');
      if (target) {
        NCWizard.saveCurrentStep(function () {
          NCWizard.navigateToStep(target);
        });
      }
    },

    // ===== Step State =====

    saveCurrentStep: function (callback) {
      var data = {};

      if (this.currentStep === 'types') {
        data.selected_types = [];
        $('.nc-wizard-type-checkbox:checked').each(function () {
          data.selected_types.push($(this).val());
        });
      } else if (this.currentStep === 'presets') {
        data.selected_presets = [];
        $('.nc-wizard-preset-checkbox:checked').each(function () {
          data.selected_presets.push($(this).val());
        });
      } else if (this.currentStep === 'post-types') {
        data.enabled_post_types = [];
        $('.nc-wizard-posttype-checkbox:checked').each(function () {
          data.enabled_post_types.push($(this).val());
        });
      }

      $.post(ncWizardData.ajaxUrl, {
        action: 'naticore_wizard_save',
        nonce: ncWizardData.nonce,
        step: this.currentStep,
        data: data
      }, function () {
        if (typeof callback === 'function') callback();
      }).fail(function () {
        if (typeof callback === 'function') callback();
      });
    },

    syncStateToUI: function () {
      var state = this.state;

      // Types step: restore checked state from saved state
      if (state.selected_types && state.selected_types.length) {
        $('.nc-wizard-type-checkbox').each(function () {
          var $cb = $(this);
          var isIncluded = state.selected_types.indexOf($cb.val()) !== -1;
          $cb.prop('checked', isIncluded);
          $cb.closest('.nc-wizard-type-card').toggleClass('selected', isIncluded);
        });
      }

      // Presets step
      if (state.selected_presets && state.selected_presets.length) {
        $('.nc-wizard-preset-checkbox').each(function () {
          var $cb = $(this);
          var isIncluded = state.selected_presets.indexOf($cb.val()) !== -1;
          $cb.prop('checked', isIncluded);
          $cb.closest('.nc-wizard-preset-card').toggleClass('selected', isIncluded);
        });
      }

      // Post types step
      if (state.enabled_post_types && state.enabled_post_types.length) {
        $('.nc-wizard-posttype-checkbox').each(function () {
          var $cb = $(this);
          var isIncluded = state.enabled_post_types.indexOf($cb.val()) !== -1;
          $cb.prop('checked', isIncluded);
        });
      }
    },

    // ===== Card Toggles =====

    handleTypeToggle: function () {
      var $cb = $(this);
      $cb.closest('.nc-wizard-type-card').toggleClass('selected', $cb.is(':checked'));
    },

    handlePresetToggle: function () {
      var $cb = $(this);
      $cb.closest('.nc-wizard-preset-card').toggleClass('selected', $cb.is(':checked'));
    },

    handlePostTypeToggle: function () {
      // No visual toggle needed for checkbox cards
    },

    // ===== Add Custom Type =====

    handleAddType: function (e) {
      e.preventDefault();
      $('#nc-wizard-add-type-form').slideToggle(200);
      $('#nc-new-type-label').focus();
    },

    handleSaveNewType: function (e) {
      e.preventDefault();
      var label = $.trim($('#nc-new-type-label').val());
      if (!label) {
        $('#nc-new-type-label').css('border-color', '#ef4444').focus();
        return;
      }
      var slug = label.toLowerCase().replace(/[^a-z0-9]+/g, '_').replace(/^_|_$/g, '');
      var from = $('#nc-new-type-from').val();
      var to = $('#nc-new-type-to').val();
      var bidir = $('#nc-new-type-bidirectional').is(':checked');

      // Check for duplicate slug
      var exists = false;
      $('.nc-wizard-type-checkbox').each(function () {
        if ($(this).val() === slug) { exists = true; }
      });
      if (exists) {
        $('#nc-new-type-label').css('border-color', '#ef4444').attr('placeholder', 'Type already exists').val('').focus();
        return;
      }

      // Build card HTML
      var badgeClass = bidir ? 'nc-badge nc-badge-type' : 'nc-badge nc-badge-muted';
      var badgeText = bidir ? 'Bidirectional' : 'One-way';
      var cardHtml =
        '<label class="nc-wizard-type-card selected" style="animation:ncFadeIn .3s ease">' +
          '<input type="checkbox" name="nc_wizard_types[]" value="' + slug + '" checked class="nc-wizard-type-checkbox" />' +
          '<div class="nc-wizard-type-card-bar"></div>' +
          '<div class="nc-wizard-type-card-content">' +
            '<div class="nc-wizard-type-card-header">' +
              '<h4 class="nc-headline-sm">' + $('<span>').text(label).html() + '</h4>' +
              '<span class="nc-badge nc-badge-active">Custom</span>' +
            '</div>' +
            '<p class="nc-text-xs nc-text-muted">' + from + ' → ' + to + '</p>' +
            '<span class="' + badgeClass + '">' + badgeText + '</span>' +
          '</div>' +
        '</label>';

      $('.nc-wizard-type-grid').append(cardHtml);

      // Reset form
      $('#nc-new-type-label').val('').css('border-color', '');
      $('#nc-new-type-from').val('post');
      $('#nc-new-type-to').val('post');
      $('#nc-new-type-bidirectional').prop('checked', false);
      $('#nc-wizard-add-type-form').slideUp(200);
    },

    handleCancelNewType: function (e) {
      e.preventDefault();
      $('#nc-wizard-add-type-form').slideUp(200);
      $('#nc-new-type-label').val('').css('border-color', '');
    },

    // ===== Navigation Helpers =====

    getNextStep: function () {
      var idx = this.steps.indexOf(this.currentStep);
      if (idx < this.steps.length - 1) {
        return this.steps[idx + 1];
      }
      return null;
    },

    getPrevStep: function () {
      var idx = this.steps.indexOf(this.currentStep);
      if (idx > 0) {
        return this.steps[idx - 1];
      }
      return null;
    },

    navigateToStep: function (step) {
      var url = new URL(window.location.href);
      url.searchParams.set('step', step);
      window.location.href = url.toString();
    }
  };

  $(document).ready(function () {
    NCWizard.init();
  });

})(jQuery);
