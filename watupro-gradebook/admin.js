jQuery( function( $ ) {

	// export button handler
  $( '#gradebook_class_export' ).on( 'click', function() {
  	window.location.href = $(this).data('href')
  });

	// datatables init
	var table = $('.rwmb-input table').DataTable({
		rowReorder: {
	    selector: 'tr',
	    update: false,
    },
	});

	/*
	table.on( 'row-reordered', function ( e, diff, edit ) {

	});
	*/

});
