/*
 * Security Guard
 * THemology NEt
 */

function sg_block_ui(content_el) {
  jQuery('html.wp-toolbar').addClass('sg-overlay-active');
  jQuery('#wpadminbar').addClass('sg-overlay-active');
  jQuery('#sg_overlay .themo-sg-overlay-outer').css('height', (jQuery(window).height() - 200) + 'px');
  jQuery('#sg_overlay').show();

  if (content_el) {
    jQuery(content_el, '#sg_overlay').show();
  }
} // sg_block_ui

function sg_unblock_ui(content_el) {
  jQuery('html.wp-toolbar').removeClass('sg-overlay-active');
  jQuery('#wpadminbar').removeClass('sg-overlay-active');
  jQuery('#sg_overlay').hide();

  if (content_el) {
    jQuery(content_el, '#sg_overlay').hide();
  }
} // sg_block_ui

jQuery(document).ready(function($){
  // init tabs
  $('#tabs').tabs({
    activate: function( event, ui ) {
        $.cookie('sg_tabs_selected', $('#tabs').tabs('option', 'active'));
    },
    active: $('#tabs').tabs({ active: $.cookie('sg_tabs_selected') })
  });

  // run tests, via ajax
  $('#run-tests').click(function(){
    var data = {action: 'sg_run_tests'};

    sg_block_ui('#sg-site-scan');

    $.post(ajaxurl, data, function(response) {
      if (response != '1') {
        alert('Undocumented error. Page will automatically reload.');
        window.location.reload();
      } else {
        window.location.reload();
      }
    });
  }); // run tests

  // show test details/help tab
  $('.sg-details a.button').live('click', function(){
    if ($('#themo-ss-dialog').length){
      $('#themo-ss-dialog').dialog('close');
    }
    $('#tabs').tabs('option', 'active', 1);

    // get the link anchor and scroll to it
    target = this.href.split('#')[1];
    $.scrollTo('#' + target, 500, {offset: {top:-30, left:0}});

    return false;
  }); // show test details

  // hide add-on tab
  $('.hide_tab').on('click', function(e){
    e.preventDefault();
    data = {action: 'sg_hide_tab', 'tab': $(this).data('tab-id')};

    $.post(ajaxurl, data, function(response) {
      if (!response.success) {
        alert('Undocumented error. Page will automatically reload.');
        window.location.reload();
      } else {
        window.location.reload();
      }
    });
  }); // hide add-on tab

  // abort scan by refreshing
  $('#abort-scan').on('click', function(e){
    e.preventDefault();
    if (confirm('Are you sure you want to cancel the scan?')) {
      window.location.reload();
    }
  }); // abort scan

  // refresh update info
  $('#sg-refresh-update').on('click', function(e){
    e.preventDefault();
    $.post(ajaxurl, {action: 'sg_refresh_update'}, function(response) {
      window.location.reload();
    });
  }); // refresh update info
});