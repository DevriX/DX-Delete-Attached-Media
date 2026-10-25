/* global jQuery, ajaxurl, DX_DAM */
jQuery( function ( $ ) {
	// Each control saves its own setting (and clears its opposite option), then the page reloads
	// because the media list below depends on the settings.
	var opposites = {
		'enable-feature': null,
		date_sort_new: 'date_sort_old',
		date_sort_old: 'date_sort_new',
		with_parent: 'without_parent',
		without_parent: 'with_parent'
	};
	var $status = $( '#dx-dam-status' );

	function showStatus( type, message ) {
		$status.removeClass( 'notice-info notice-success notice-error' ).addClass( 'notice-' + type ).show().find( 'p' ).text( message );
	}

	$.each( opposites, function ( id, opposite ) {
		$( '#' + id ).on( 'change', function () {
			var enabled = $( this ).is( ':checked' ) ? 1 : 0;
			var data = { nonce: DX_DAM.nonce };

			data[ id ] = enabled;
			if ( opposite ) {
				data[ opposite ] = enabled ? 0 : 1;
				$( '#' + opposite ).prop( 'checked', ! enabled );
			}

			showStatus( 'info', DX_DAM.saving );
			$.post( ajaxurl, { action: 'dx_dam_add_to_base', data: data } )
				.done( function () {
					showStatus( 'success', DX_DAM.saved );
					window.location = window.location.href.replace( /&paged=[0-9]*/, '&paged=1' );
				} )
				.fail( function ( xhr ) {
					var message = xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message;
					showStatus( 'error', message || DX_DAM.failed );
				} );
		} );
	} );
} );
