(function ($) {

  $(function() {
    $('form#replicate-ui-confirm').on('submit', onReplicateSubmit);
  });

  function onReplicateSubmit() {
    var $form = $(this);

    $form.find('a#edit-cancel').remove();
    var $submit = $form.find('input[type="submit"]');
    $submit.attr('disabled', 'disabled').css('opacity', '0.5');
    $submit.after('<span class="replicate-wait">Replication process may take more than a minute. Please wait...</span>');
  }


})(jQuery);
