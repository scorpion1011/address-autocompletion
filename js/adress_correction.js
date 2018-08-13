jQuery(function() {
	var addressConfirmed = false;
	var postCode_CityConfirmed = false;
	var xhr;
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
				try { 
					xhr.abort(); 
				} 
				catch(e){
				}
				var data = {
					action: 'action',
					address: jQuery('#billing_address_1').val(),
					city: jQuery('#billing_city').val(),
					zip: jQuery('#billing_postcode').val(),
					sender: sender
				};
				if(needRequest(data)) {
					xhr = jQuery.get(myPlugin.ajaxurl, data, function(response) {
						var arrayOfObjectProperty = [];
						var jsonObj = jQuery.parseJSON(response);
						jQuery.each(jsonObj, function(key, value) {
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
					addressConfirmed = true;
				}
				else {
					jQuery('#billing_city').val(jQuery(item).attr("data-city"));
					jQuery('#billing_postcode').val(jQuery(item).attr("data-postcode"));
					postCode_CityConfirmed = true;
				}
			}
		};
	}
	
	jQuery('#billing_city').on('input', function() {
		postCode_CityConfirmed = false;
		addressConfirmed = false;
	});
	
	jQuery('#billing_postcode').on('input', function() {
		postCode_CityConfirmed = false;
		addressConfirmed = false;
	});
	
	jQuery('#billing_address_1').on('input', function() {
		addressConfirmed = false;
	});

	jQuery('#billing_city')     .autoComplete(autoCompleteConfig('city'));
	jQuery('#billing_postcode') .autoComplete(autoCompleteConfig('zip'));
	jQuery('#billing_address_1').autoComplete(autoCompleteConfig('address'));
	
	jQuery('form.checkout.woocommerce-checkout').on('checkout_place_order', function(event) {
		if (addressConfirmed && postCode_CityConfirmed) {
			// allow the submit AJAX call
			return true;
		}
		
		var data = {
				action: 'action',
				address: jQuery('#billing_address_1').val(),
				city: jQuery('#billing_city').val(),
				zip: jQuery('#billing_postcode').val(),
				sender: 'submit'
			};

		jQuery.get(myPlugin.ajaxurl, data, function(response) {

			var jsonObj = jQuery.parseJSON(response);

			if(jsonObj.length){
				jQuery('#enderecoCorrectedSuggestions').empty();
				jQuery.each(jsonObj, function(key, value) {
					jQuery('#enderecoCorrectedSuggestions').append('<label><input type="radio" name="addressCorrection" data-city="' + value.city + '" data-postcode="' + value.postcode + '" data-address="' + value.street + '" data-id="' + (1 + key) + '">' + value.postcode + ' ' + value.city + ' ' + value.street + '</label><br />');
				});
				jQuery('.could-not-find-corrections').hide();
				jQuery('#enderecoCorrectedSuggestions').show();
			}
			else {
				jQuery('#enderecoCorrectedSuggestions').hide();
				jQuery('.could-not-find-corrections').show();
			}

			var city = jQuery('#billing_city').val();
			var postcode = jQuery('#billing_postcode').val();
			var address = jQuery('#billing_address_1').val();
			jQuery('#enderecoCurrentInput').html('<label><input type="radio" name="addressCorrection" data-city="' + city + '" data-postcode="' + postcode + '" data-address="' + address + '" data-id="0" checked="checked">' + postcode + ' ' + city + ' ' + address + '</label><br />');
			jQuery('#enderecoAddressCheckModal').modal('show');
			
		});

		event.stopImmediatePropagation();
		return false;
	});
	
	// 3) оптимизация запросов
	jQuery('#enderecoAddressCheckSubmit').click(function(){
		if (addressConfirmed == true && postCode_CityConfirmed == true) {
			jQuery('#enderecoAddressCheckModal').modal('hide');
			jQuery('form.checkout.woocommerce-checkout').submit();
		}
		else {
			var allRadio = document.getElementsByName('addressCorrection');
			
			allRadio.forEach(function(node) {
				if (jQuery(node).attr('checked') == 'checked' && jQuery(node).attr('data-id') != 0) {
					jQuery('#billing_city').val(jQuery(node).attr('data-city'));
					jQuery('#billing_postcode').val(jQuery(node).attr('data-postcode'));
					jQuery('#billing_address_1').val(jQuery(node).attr('data-address'));
				}
			});
			
			addressConfirmed = postCode_CityConfirmed = true;
			jQuery('#enderecoAddressCheckModal').modal('hide');
			jQuery('form.checkout.woocommerce-checkout').submit();
		}
	});

});