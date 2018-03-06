/**
 * @file
 * Paragraphs accordion script.
 */

(function ($, Drupal, window, document) {
  Drupal.behaviors.paragraphs_jquery_ui_accordion = {
    attach: function (context, settings) {
      var accordion_id = '#' + drupalSettings.paragraphs_jquery_ui_accordion.id;
      var autoscroll = parseInt(drupalSettings.paragraphs_jquery_ui_accordion.autoscroll);

      if (window.location.hash) {
        var activeParagraph = false;
      } else {
        var activeParagraph = 0;
      }

      // Init jQuery UI Accordion
      $(accordion_id, context).accordion({
        active: activeParagraph,
        collapsible: true,
        animated: 'slide',
        autoHeight: false,
        navigation: true,
        heightStyle: "content"
      });

      if (autoscroll === 1) {
        $(accordion_id, context).on( "accordionactivate", function( event, ui ) {
          var newHash = $(ui.newHeader).find('a').attr('href');
          changeHash(newHash);
        });
      }

      // Open content that matches the hash
      $( window ).on('hashchange', function() {
        activateParagraph(accordion_id);
      }).trigger('hashchange');

      function changeHash(newHash) {
        if (newHash !== 'undefined' && newHash) {
          var target = $(newHash);
          $('html, body').animate({
            scrollTop: target.offset().top - 50
          }, 250);
          return false;
        }
      }

      function activateParagraph(accordion_id) {
        var hash = window.location.hash;
        if (hash) {
          var thash = hash.substring(hash.lastIndexOf('#'), hash.length);
          var paragraph = $(accordion_id).find('a[href*=\\' + thash + ']').closest('h3');
          if (!$(paragraph).hasClass("ui-state-active")) {
            $(paragraph).trigger('click');
          }
        }
      }
    }
  };

}(jQuery, Drupal, this, this.document));
