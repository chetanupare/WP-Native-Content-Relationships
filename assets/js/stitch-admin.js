/**
 * Stitch Admin UI - Interactive Features
 * Native Content Relationships Plugin v1.5.0
 */
(function ($) {
  'use strict';

  var NCStitch = {

    init: function () {
      this.bindEvents();
      this.initExplorer();
      this.initWpSidebarHover();
      this.initTopbarDropdown();
      this.moveAdminNotices();
    },

    bindEvents: function () {
      // Tab switching
      $(document).on('click', '.nc-tab', this.handleTabSwitch);

      // Settings nav
      $(document).on('click', '.nc-settings-nav-item', this.handleSettingsNav);

      // Modal open/close
      $(document).on('click', '.nc-open-modal', this.openModal);
      $(document).on('click', '.nc-modal-close, .nc-modal-overlay', this.closeModal);
      $(document).on('click', '.nc-modal', function (e) { e.stopPropagation(); });

      // Dropdown toggle
      $(document).on('click', '.nc-dropdown-toggle', this.toggleDropdown);

      // Table select all
      $(document).on('change', '.nc-select-all', this.handleSelectAll);
      $(document).on('change', '.nc-row-select', this.handleRowSelect);

      // Import tab switching
      $(document).on('click', '.nc-import-tab', this.handleImportTab);

      // Dropzone drag & drop
      this.initDropzone();

      // Toggle switches - fire change event for settings
      $(document).on('change', '.nc-toggle input', this.handleToggleChange);

      // Relationship Types: edit button
      $(document).on('click', '.nc-edit-type-btn', this.handleTypeEdit);

      // Relationship Types: modal submit
      $(document).on('click', '#nc-type-modal-submit', this.handleTypeSubmit);

      // Relationships: edit button
      $(document).on('click', '.nc-edit-relation-btn', this.handleRelationEdit);

      // Relationships: delete button
      $(document).on('click', '.nc-delete-relation-btn', this.handleRelationDelete);

      // Close dropdowns on outside click
      $(document).on('click', function (e) {
        if (!$(e.target).closest('.nc-dropdown').length) {
          $('.nc-dropdown-menu').hide();
        }
      });
    },

    // ===== Tab Switching =====
    handleTabSwitch: function (e) {
      e.preventDefault();
      var $tab = $(this);
      var target = $tab.data('tab');

      $tab.closest('.nc-tabs').find('.nc-tab').removeClass('active');
      $tab.addClass('active');

      // Show/hide tab content
      $('[data-tab-content]').addClass('nc-hidden');
      $('[data-tab-content="' + target + '"]').removeClass('nc-hidden');
    },

    // ===== Settings Navigation =====
    handleSettingsNav: function (e) {
      e.preventDefault();
      var $item = $(this);
      var target = $item.data('section');

      $item.closest('.nc-settings-nav-inner').find('.nc-settings-nav-item').removeClass('active');
      $item.addClass('active');

      // Show/hide settings sections
      $('.nc-settings-section').addClass('nc-hidden');
      $('#nc-section-' + target).removeClass('nc-hidden');
    },

    // ===== Modal =====
    openModal: function (e) {
      e.preventDefault();
      var modalId = $(this).data('modal');
      $('#' + modalId).addClass('open');
      $('body').css('overflow', 'hidden');
    },

    closeModal: function (e) {
      e.preventDefault();
      $(this).closest('.nc-modal-overlay').removeClass('open');
      $('body').css('overflow', '');
    },

    // ===== Dropdown =====
    toggleDropdown: function (e) {
      e.preventDefault();
      e.stopPropagation();
      var $menu = $(this).siblings('.nc-dropdown-menu');
      $('.nc-dropdown-menu').not($menu).hide();
      $menu.toggle();
    },

    // ===== Table Selection =====
    handleSelectAll: function () {
      var checked = $(this).prop('checked');
      $('.nc-row-select').prop('checked', checked);
    },

    handleRowSelect: function () {
      var total = $('.nc-row-select').length;
      var selected = $('.nc-row-select:checked').length;
      $('.nc-select-all').prop('checked', total === selected && total > 0);
    },

    // ===== Import Tab =====
    handleImportTab: function (e) {
      e.preventDefault();
      var target = $(this).data('tab');

      $('.nc-import-tab').removeClass('active');
      $(this).addClass('active');

      $('.nc-import-section').addClass('nc-hidden');
      $('#nc-import-' + target).removeClass('nc-hidden');
    },

    // ===== Dropzone =====
    initDropzone: function () {
      var $dropzone = $('.nc-dropzone');
      if (!$dropzone.length) return;

      var $input = $dropzone.find('input[type="file"]');

      $dropzone.on('click', function () {
        $input.trigger('click');
      });

      $dropzone.on('dragover', function (e) {
        e.preventDefault();
        $(this).addClass('dragover');
      });

      $dropzone.on('dragleave', function () {
        $(this).removeClass('dragover');
      });

      $dropzone.on('drop', function (e) {
        e.preventDefault();
        $(this).removeClass('dragover');
        if (e.originalEvent.dataTransfer.files.length) {
          $input[0].files = e.originalEvent.dataTransfer.files;
          $input.trigger('change');
        }
      });
    },

    // ===== Toggle Change =====
    handleToggleChange: function () {
      // Auto-save indicator
      var $footer = $('.nc-sticky-footer');
      if ($footer.length) {
        $footer.find('.nc-sticky-footer-left').html(
          '<span class="material-symbols-outlined" style="font-size:16px;color:var(--nc-primary);">info</span> Unsaved changes'
        );
      }
    },

    // ===== Relationship Types: Edit =====
    handleTypeEdit: function (e) {
      e.preventDefault();
      e.stopPropagation();
      var $btn = $(this);
      var slug = $btn.data('slug');
      var label = $btn.data('label');
      var bidirectional = $btn.data('bidirectional');
      var fromType = $btn.data('from');
      var toType = $btn.data('to');
      var isBuiltin = $btn.data('builtin');

      // Populate the modal fields.
      var $modal = $('#nc-type-modal');
      $modal.find('input[placeholder*="Post Authors"]').val(label);
      $modal.find('input[placeholder*="post_authors"]').val(slug);
      if (isBuiltin) {
        $modal.find('input[placeholder*="post_authors"]').prop('readonly', true);
      } else {
        $modal.find('input[placeholder*="post_authors"]').prop('readonly', false);
      }

      // Set cardinality.
      $modal.find('input[name="nc_cardinality"][value="one-to-many"]').prop('checked', true);

      // Set bidirectional toggle.
      $modal.find('#nc_toggle_bidir').prop('checked', !!bidirectional);

      // Set source/target selects.
      $modal.find('select').each(function() {
        var $sel = $(this);
        $sel.find('option').each(function() {
          var optText = $(this).text().trim().toLowerCase();
          if (optText === fromType || optText === fromType + 's') {
            $sel.val($(this).val());
          }
        });
      });

      // Mark as edit mode.
      $modal.data('edit-mode', true).data('edit-slug', slug);
      $('#nc-type-modal-submit-text').text('Update Type');
      $modal.find('.nc-modal-header h2').text('Edit Relationship Type');

      // Open modal.
      $modal.addClass('open');
      $('body').css('overflow', 'hidden');
    },

    // ===== Relationship Types: Submit (Create or Update) =====
    handleTypeSubmit: function (e) {
      e.preventDefault();
      var $modal = $('#nc-type-modal');
      var isEdit = $modal.data('edit-mode');
      var slug = isEdit ? $modal.data('edit-slug') : '';
      var $labelInput = $modal.find('input[placeholder*="Post Authors"]');
      var $slugInput = $modal.find('input[placeholder*="post_authors"]');
      var label = $.trim($labelInput.val());

      if (!isEdit) {
        slug = $.trim($slugInput.val());
      }

      if (!label) {
        alert('Label is required.');
        return;
      }
      if (!slug) {
        alert('Slug is required.');
        return;
      }

      var bidirectional = $modal.find('#nc_toggle_bidir').is(':checked') ? 1 : 0;
      var sortable = $modal.find('#nc_toggle_sort').is(':checked') ? 1 : 0;

      var $submit = $('#nc-type-modal-submit');
      $submit.prop('disabled', true).css('opacity', '0.6');

      $.post(ncStitchData.ajaxUrl, {
        action: 'naticore_save_type',
        nonce: ncStitchData.nonce,
        slug: slug,
        label: label,
        bidirectional: bidirectional,
        from_type: 'post',
        to_type: 'post'
      }, function (resp) {
        $submit.prop('disabled', false).css('opacity', '1');
        if (resp.success) {
          window.location.reload();
        } else {
          alert(resp.data && resp.data.message ? resp.data.message : 'Error saving type.');
        }
      }).fail(function () {
        $submit.prop('disabled', false).css('opacity', '1');
        alert('Network error.');
      });
    },

    // ===== Relationships: Edit =====
    handleRelationEdit: function (e) {
      e.preventDefault();
      var $btn = $(this);
      var relationId = $btn.data('relation-id');
      if (!relationId) return;

      // For now, navigate to the source post editor where the meta box is available.
      // A full modal editor would require a separate UI — use the existing meta box.
      $.get(ncStitchData.ajaxUrl, {
        action: 'naticore_get_relation',
        nonce: ncStitchData.nonce,
        relation_id: relationId
      }, function (resp) {
        if (resp.success && resp.data) {
          var editUrl = '/wp-admin/post.php?post=' + resp.data.from_id + '&action=edit';
          window.location.href = editUrl;
        } else {
          alert(resp.data && resp.data.message ? resp.data.message : 'Could not load relationship.');
        }
      });
    },

    // ===== Relationships: Delete =====
    handleRelationDelete: function (e) {
      e.preventDefault();
      var $btn = $(this);
      var relationId = $btn.data('relation-id');
      var fromId = $btn.data('from-id');
      var toId = $btn.data('to-id');
      var type = $btn.data('type');
      if (!relationId) return;

      var msg = 'Delete this relationship?';
      if (type) {
        msg = 'Delete the "' + type + '" relationship between #' + fromId + ' and #' + toId + '?';
      }
      if (!confirm(msg)) return;

      $btn.prop('disabled', true).css('opacity', '0.5');

      $.post(ncStitchData.ajaxUrl, {
        action: 'naticore_delete_relation',
        nonce: ncStitchData.nonce,
        relation_id: relationId
      }, function (resp) {
        if (resp.success) {
          var $row = $btn.closest('tr');
          $row.fadeOut(300, function () {
            $(this).remove();
            // Update count if visible.
            var $count = $('.nc-pagination-bar .nc-text-xs');
            if ($count.length) {
              var text = $count.text();
              var match = text.match(/of ([\d,]+)/);
              if (match) {
                var newTotal = parseInt(match[1].replace(/,/g, ''), 10) - 1;
                $count.text(text.replace(/of [\d,]+/, 'of ' + newTotal.toLocaleString()));
              }
            }
          });
        } else {
          $btn.prop('disabled', false).css('opacity', '1');
          alert(resp.data && resp.data.message ? resp.data.message : 'Error deleting relationship.');
        }
      }).fail(function () {
        $btn.prop('disabled', false).css('opacity', '1');
        alert('Network error.');
      });
    },

    // ===== Explorer =====
    initExplorer: function () {
      var $canvas = $('#nc-canvas-surface');
      if (!$canvas.length) return;

      // Node selection
      $(document).on('click', '.nc-node-card', function () {
        $('.nc-node-card').removeClass('selected');
        $(this).addClass('selected');
        NCStitch.updateExplorerSidebar($(this).data('node-id'));
      });

      // Zoom controls
      $(document).on('click', '.nc-zoom-in', function () {
        NCStitch.zoomExplorer(10);
      });
      $(document).on('click', '.nc-zoom-out', function () {
        NCStitch.zoomExplorer(-10);
      });
      $(document).on('click', '.nc-zoom-fit', function () {
        NCStitch.resetZoom();
      });
    },

    currentZoom: 100,

    zoomExplorer: function (delta) {
      this.currentZoom = Math.max(50, Math.min(200, this.currentZoom + delta));
      $('#nc-canvas-surface').css('transform', 'scale(' + (this.currentZoom / 100) + ')');
      $('.nc-zoom-level').text(this.currentZoom + '%');
    },

    resetZoom: function () {
      this.currentZoom = 100;
      $('#nc-canvas-surface').css('transform', 'scale(1)');
      $('.nc-zoom-level').text('100%');
    },

    updateExplorerSidebar: function (nodeId) {
      // In production, this would fetch node data via AJAX
      // For now, it's a visual placeholder
      console.log('Explorer: selected node', nodeId);
    },

    // WP Admin Sidebar: show settings sub-items on hover of Settings
    initWpSidebarHover: function () {
      var $settingsItem = $('#adminmenu a[href*="page=naticore-settings"]').closest('li');
      var $subItems = $('#adminmenu li.nc-settings-sub');
      var isOnSettingsPage = $subItems.filter('.current').length > 0;

      if (isOnSettingsPage) {
        $subItems.addClass('nc-settings-sub-show');
        return;
      }

      var hoverTimeout;
      $settingsItem.on('mouseenter', function () {
        clearTimeout(hoverTimeout);
        $subItems.addClass('nc-settings-sub-show');
      });
      $settingsItem.on('mouseleave', function () {
        hoverTimeout = setTimeout(function () {
          $subItems.removeClass('nc-settings-sub-show');
        }, 200);
      });
      $subItems.on('mouseenter', function () {
        clearTimeout(hoverTimeout);
        $subItems.addClass('nc-settings-sub-show');
      });
      $subItems.on('mouseleave', function () {
        hoverTimeout = setTimeout(function () {
          $subItems.removeClass('nc-settings-sub-show');
        }, 200);
      });
    },

    // Move WP admin notices inside our custom layout so they aren't hidden under the fixed topbar
    moveAdminNotices: function () {
      var $notices = $('#wpbody-content > .notice, #wpbody-content > .update-nag, #wpbody-content > .updated, #wpbody-content > .error');
      var $main = $('.nc-main');
      if ($notices.length && $main.length) {
        var $noticeWrapper = $('<div class="nc-notices-wrapper" style="margin-bottom: 20px;"></div>');
        $notices.detach().appendTo($noticeWrapper);
        $main.prepend($noticeWrapper);
      }
    },

    // Stitch Topbar: Settings dropdown on hover
    initTopbarDropdown: function () {
      var $settingsTab = $('.nc-topbar-tab-settings-wrap');
      var $dropdown = $settingsTab.find('.nc-topbar-dropdown');
      var hideTimeout;

      $settingsTab.on('mouseenter', function () {
        clearTimeout(hideTimeout);
        $dropdown.addClass('nc-topbar-dropdown-show');
      });
      $settingsTab.on('mouseleave', function () {
        hideTimeout = setTimeout(function () {
          $dropdown.removeClass('nc-topbar-dropdown-show');
        }, 150);
      });
    }
  };

  $(document).ready(function () {
    NCStitch.init();
  });

})(jQuery);
