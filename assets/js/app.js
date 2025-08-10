$(function(){
  $('.datatable').DataTable({
    pageLength: 10,
    lengthChange: false,
    order: [],
  });

  // Global AJAX setup to add CSRF token
  $.ajaxSetup({
    beforeSend: function(xhr, settings){
      var token = $('meta[name="csrf-token"]').attr('content');
      if (settings.type && settings.type.toUpperCase() === 'POST' && token) {
        settings.data = (settings.data ? settings.data + '&' : '') + encodeURIComponent('csrf_token') + '=' + encodeURIComponent(token);
      }
    }
  });
});