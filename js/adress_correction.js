jQuery(function() {
	function dataRequest(sender) {
		var data = {
			action: 'action',
			address: jQuery('#billing_address_1').val(),
			city: jQuery('#billing_city').val(),
			zip: jQuery('#billing_postcode').val(),
			sender: sender
		};
		
		jQuery.get( myPlugin.ajaxurl, data, function(response) {
			alert(response);
		});
	}
	jQuery('#billing_city').on('input', function() {
		dataRequest('city');
	});
	
	jQuery('#billing_address_1').on('input', function() {
		dataRequest('address');
	});
	
	jQuery('#billing_postcode').on('input', function() {
		dataRequest('zip');
	});
});