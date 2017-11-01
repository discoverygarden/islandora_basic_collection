/**
 * @file
 * AJAX callback to populate the count block.
 */

(function ($, Drupal) {
  Drupal.behaviors.islandora_basic_collection_count_block = {
    attach: function (context, settings) {
      $('span#' + settings.islandora_basic_collection.count_block.id, context).once('islandora_basic_collection_count_block').each(function () {
        $.ajax({
          url: settings.islandora_basic_collection.count_block.callback,
          success: function (data, textStatus, jqXHR) {
            $(this).html(data);
          },
          context: this
        });
      });
    }
  }
})(jQuery, Drupal);
