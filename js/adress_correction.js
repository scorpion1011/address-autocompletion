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
			var arrayOfObjectProperty = [];
			var obj = jQuery.parseJSON(response);
			var htmlElement;
			if (sender == 'city'){
				htmlElement = '#billing_city';
				jQuery.each( obj, function( key, value ) {
					if (key = 'city')
					{
						arrayOfObjectProperty[arrayOfObjectProperty.length] = value.city;
					}
				});
			}
			else if (sender == 'address'){
				htmlElement = '#billing_address_1';
				jQuery.each( obj, function( key, value ) {
					if (key = 'street')
					{
						arrayOfObjectProperty[arrayOfObjectProperty.length] = value.street;
					}
				});
			}
			else if (sender == 'zip'){
				htmlElement = '#billing_postcode';
				jQuery.each( obj, function( key, value ) {
					if (key = 'postCode')
					{
						arrayOfObjectProperty[arrayOfObjectProperty.length] = value.postcode;
					}
				});
			}
			jQuery(htmlElement).autoComplete({
				minChars: 0,
				source: function(input, suggests) {
					input = input.toLowerCase();
					var matches = [];
					for (i=0; i<arrayOfObjectProperty.length; i++) {
						if (~arrayOfObjectProperty[i].toLowerCase().indexOf(input)){
							matches.push(arrayOfObjectProperty[i])
						};
					}
					suggests(matches);
				}
			});
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