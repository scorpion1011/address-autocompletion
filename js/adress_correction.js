jQuery(function() {
	
	function needRequest(data) {
		if('city' == data.sender) {
			return data.city.length > 0;
		}
		if('zip' == data.sender) {
			return data.zip.length > 0;
		}
		return data.address.length > 0;
	}
	
	function autoCompleteConfig(sender) {
		return {
			minChars: 0,
			source: function(input, suggests) {
				var data = {
					action: 'action',
					address: jQuery('#billing_address_1').val(),
					city: jQuery('#billing_city').val(),
					zip: jQuery('#billing_postcode').val(),
					sender: sender
				};
				if(needRequest(data)) {
					jQuery.get(myPlugin.ajaxurl, data, function(response) {
						var arrayOfObjectProperty = [];

						jQuery.each(jQuery.parseJSON(response), function(key, value) {
							arrayOfObjectProperty.push(value);
						});
	
						suggests(arrayOfObjectProperty);
					});
				}
				suggests([]);
			},
			renderItem: function (item, search){
				search = search.replace(/[-\/\\^$*+?.()|[\]{}]/g, '\\$&');
				var re = new RegExp("(" + search.split(' ').join('|') + ")", "gi");
				if('address' == sender) {
					var str = item.street;
					return '<div class="autocomplete-suggestion" data-street="' + item.street + '" data-val="' + str + '">' + str.replace(re, "<b>$1</b>") + '</div>';
				}
				var str = item.postcode + ', ' + item.city;
				return '<div class="autocomplete-suggestion" data-city="' + item.city + '" data-postcode="' + item.postcode + '" data-val="' + str + '">' + str.replace(re, "<b>$1</b>") + '</div>';
			},
			onSelect: function(e, term, item){
				if('address' == sender) {
					jQuery('#billing_address_1').val(jQuery(item).attr("data-street"));
				}
				else {
					jQuery('#billing_city').val(jQuery(item).attr("data-city"));
					jQuery('#billing_postcode').val(jQuery(item).attr("data-postcode"));
				}
			}
		};
	}

	jQuery('#billing_city')     .autoComplete(autoCompleteConfig('city'));
	jQuery('#billing_postcode') .autoComplete(autoCompleteConfig('zip'));
	jQuery('#billing_address_1').autoComplete(autoCompleteConfig('address'));
	
	jQuery('form.checkout.woocommerce-checkout').on('checkout_place_order', function(event) {
	    if (1 /* your condition, e.g. "$payment_method == 'paypal'" */ ) {
	        // prevent the submit AJAX call
	        alert( 'submit cancelled!' );
	        // @todo: !! test in different browsers !!
			event.stopImmediatePropagation()
	        return false;
	    }
	    // allow the submit AJAX call
	    return true;
	});

});