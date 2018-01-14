(function ($, window, Drupal) {

  'use strict';

  Drupal.behaviors.gnuwhine_ui_cocktails = {
    attach: function () {

      $('.gnuwine_ui_cocktails tr').click(function(c){
        $(this).find('input').prop("checked", true);
      });

    }
  };

})(jQuery, window, Drupal);