$ = jQuery;

$(document).ready(function($) {
	$("#enable-feature").change( function() {

		if($('#enable-feature').is(':checked')) {
			var enable_feature = 1;
			alert("Feature is enabled.");
		} else {
			var enable_feature = 0;
			alert("Feature is disabled.");
		}
		$.post(
			ajaxurl, {
				data: {
					"enable-feature" : enable_feature,
					"nonce" : DX_DAM.nonce,
				},
				action: "add_to_base",
				success: function() {
					var url = window.location.href;
					url = url.replace(/&paged=[0-9]*/, '&paged=1')
					window.setTimeout( function(){
						window.location = url;
					}, 1000 );
				}
			},
		).fail(function(data) {
			alert( data.responseJSON.data.message );
		});
	});

	$("#date_sort_new").change( function() {
		if($('#date_sort_new').is(':checked')) {
			var date_sort_old = 0;
			var date_sort_new = 1;
			alert("Showing newest media.");
		} else {
			var date_sort_old = 1;
			var date_sort_new = 0;
			alert("Showing oldest media.");
		}
		$.post(
			ajaxurl, {
				data: {
					"date_sort_new" : date_sort_new,
					"date_sort_old" : date_sort_old,
					"nonce" : DX_DAM.nonce,
				},
				action: "add_to_base",
				success: function() {
					var url = window.location.href;
					url = url.replace(/&paged=[0-9]*/, '&paged=1')
					window.setTimeout( function(){
						window.location = url;
					}, 1000 );
				}
			},
		).fail(function(data) {
			alert( data.responseJSON.data.message );
		});
	});

	$("#date_sort_old").change( function() {
		if($('#date_sort_old').is(':checked')) {
			var date_sort_old = 1;
			var date_sort_new = 0;
			alert("Showing oldest media.");
		} else {
			var date_sort_old = 0;
			var date_sort_new = 1;
			alert("Showing newest media.");
		}
		$.post(
			ajaxurl, {
				data: {
					"date_sort_new" : date_sort_new,
					"date_sort_old" : date_sort_old,
					"nonce" : DX_DAM.nonce,
				},
				action: "add_to_base",
				success: function() {
					var url = window.location.href;
					url = url.replace(/&paged=[0-9]*/, '&paged=1')
					window.setTimeout( function(){
						window.location = url;
					}, 1000 );
				}
			},
		).fail(function(data) {
			alert( data.responseJSON.data.message );
		});
	});

	$("#with_parent").change( function() {
		if($('#with_parent').is(':checked')) {
			var with_parent = 1;
			var without_parent = 0;
			alert("Showing used media.");
		} else {
			var with_parent = 0;
			var without_parent = 1;
		alert("Showing unused media.");
		}
		$.post(
			ajaxurl, {
				data: {
					"with_parent" : with_parent,
					"without_parent" : without_parent,
					"nonce" : DX_DAM.nonce,
				},
				action: "add_to_base",
				success: function() {
					var url = window.location.href;
					url = url.replace(/&paged=[0-9]*/, '&paged=1')
					window.setTimeout( function(){
						window.location = url;
					}, 1000 );
				}
			},
		).fail(function(data) {
			alert( data.responseJSON.data.message );
		});
	});

	$("#without_parent").change( function() {
		if($('#without_parent').is(':checked')) {
			var without_parent = 1;
			var with_parent = 0;
			alert("Showing unused media.");
		} else {
			var without_parent = 0;
			var with_parent = 1;
			alert("Showing used media.");
		}
		$.post(
			ajaxurl, {
				data: {
					"with_parent" : with_parent,
					"without_parent" : without_parent,
					"nonce" : DX_DAM.nonce,
				},
				action: "add_to_base",
				success: function() {
					var url = window.location.href;
					url = url.replace(/&paged=[0-9]*/, '&paged=1')
					window.setTimeout( function(){
						window.location = url;
					}, 1000 );
				}
			},
		).fail(function(data) {
			alert( data.responseJSON.data.message );
		});
	});
});
