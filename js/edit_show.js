jQuery(function($) {
    $('body').on('click init', '#limit_episodes', function() {
        $('#max_episodes').prop('disabled', !$(this).prop('checked'));
    });
    $('#limit_episodes').trigger('init');
});
