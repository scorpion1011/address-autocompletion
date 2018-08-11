jQuery(function() {
	jQuery('#billing_city').on('input', function() {
		var data = {
		action: 'action',
		city: jQuery(this).val(),
		zip: jQuery('#billing_postcode').val(),
		sender: 'city'
		};

		jQuery.get( myPlugin.ajaxurl, data, function(response) {
			alert(response);
		});
	});
	
	//jQuery('#billing_country').on('change', function() {
	//	alert("222");
	//});
	
	jQuery('#billing_postcode').on('input', function() {
		var data = {
		action: 'action',
		zip: jQuery(this).val(),
		city: jQuery('#billing_city').val(),
		sender: 'zip'
		};

		jQuery.get( myPlugin.ajaxurl, data, function(response) {
			alert(response);
		});
	});
});