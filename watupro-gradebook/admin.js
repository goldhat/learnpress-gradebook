jQuery( function( $ ) {

	// export button handler
  $( '#gradebook_class_export' ).on( 'click', function() {
  	window.location.href = $(this).data('href')
  });

	// datatables init
	var table = $('.rwmb-input table').DataTable({
		rowReorder: true,
		bFilter: false,
		lengthChange: false,
		bInfo: false,
		bPaginate: false,
		columnDefs: [
      { orderable: true, className: 'reorder', visible: false, targets: 0 },
      { orderable: false, targets: '_all' }
    ]
	});

	table.on( 'row-reordered', function ( e, diff, edit ) {
		var users = JSON.parse( gradebookClassesUserJson )
		table.one('draw', function () {
			var users = [];
			table.rows({order:'current'}).every( function ( rowIdx, tableLoop, rowLoop ) {
	    	var rowData = this.data();
			  users.push( rowData[1] )
			})
			var userJson = JSON.stringify( users )
			$('#gradebook_classes_user_selection_json').val( userJson )
	  });
	});

	// delete click event handler
	$('button.gb-delete').on('click', function() {

		var rowDeleteEl = $(this).closest('tr')
		table.row(rowDeleteEl).remove().draw();

		var users = [];
		table.rows({order:'current'}).every( function ( rowIdx, tableLoop, rowLoop ) {
			var rowData = this.data();
			users.push( rowData[1] )
		})
		var userJson = JSON.stringify( users )
		$('#gradebook_classes_user_selection_json').val( userJson )

	})

	// stash user json on load
	$('#gradebook_classes_user_selection_json').val( gradebookClassesUserJson )

});
