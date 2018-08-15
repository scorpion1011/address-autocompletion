jQuery(function() {

	var mainForm = Object.create(jQuery('form.checkout.woocommerce-checkout').length ? MainFormCheckout : MainFormProfile);

	var billingCorrection = Object.create(AddressCorrection);
	billingCorrection.init('billing', 'Ist Ihre billing adresse korrekt?');

	var shippingCorrection = Object.create(AddressCorrection);
	shippingCorrection.init('shipping', 'Ist Ihre shipping adresse korrekt?');

	var confirmationPopup = Object.create(ConfirmationPopup);
	confirmationPopup.init(function() {
		mainForm.submit();
	});

	confirmationPopup.addCorrection(billingCorrection);
	confirmationPopup.addCorrection(shippingCorrection);

	mainForm.onSubmit(function(event) {
		if (!confirmationPopup.needConfirmation()) {
			// allow the submit AJAX call
			return true;
		}

		confirmationPopup.confirm(function() {
			mainForm.submit();
		});

		event.stopImmediatePropagation();
		return false;
	});

});

var AddressCorrection = {

	xhr:                    null,
	groupPrefix:            null,
	confirmationHeader:     null,
	addressConfirmed:       null,
	postCode_CityConfirmed: null,

	getAddress: function() {
		var addressCorrection = this;

		return jQuery('#' + addressCorrection.groupPrefix + '_address_1').val();
	},

	getCity: function() {
		var addressCorrection = this;

		return jQuery('#' + addressCorrection.groupPrefix + '_city').val();
	},

	getZip: function() {
		var addressCorrection = this;

		return jQuery('#' + addressCorrection.groupPrefix + '_postcode').val();
	},

	init: function (groupPrefix, confirmationHeader) {
		var addressCorrection = this;

		addressCorrection.groupPrefix            = groupPrefix;
		addressCorrection.confirmationHeader     = confirmationHeader;
		addressCorrection.addressConfirmed       = false;
		addressCorrection.postCode_CityConfirmed = false;

		jQuery('#' + addressCorrection.groupPrefix + '_city, #' + addressCorrection.groupPrefix + '_postcode').on('input', function() {
			addressCorrection.postCode_CityConfirmed = false;
			addressCorrection.addressConfirmed       = false;
		});

		jQuery('#' + addressCorrection.groupPrefix + '_address_1').on('input', function() {
			addressCorrection.addressConfirmed = false;
		});

		jQuery('#' + addressCorrection.groupPrefix + '_city')     .autoComplete(addressCorrection.autoCompleteConfig('city'));
		jQuery('#' + addressCorrection.groupPrefix + '_postcode') .autoComplete(addressCorrection.autoCompleteConfig('zip'));
		jQuery('#' + addressCorrection.groupPrefix + '_address_1').autoComplete(addressCorrection.autoCompleteConfig('address'));

	},

	needRequest: function (data) {
		var addressCorrection = this;

		if ( addressCorrection.isGermany() ) {
			if('city' == data.sender) {
				return data.city.length > 0;
			}
			if('zip' == data.sender) {
				return data.zip.length > 0;
			}
			return data.address.length > 0;
		}
		return false;
	},

	autoCompleteConfig: function (sender) {
		var addressCorrection = this;

		return {
			minChars: 0,
			source: function(input, suggests) {
				try {
					addressCorrection.xhr.abort();
				}
				catch(e){
				}
				var data = {
						action:  'action',
						address: addressCorrection.getAddress(),
						city:    addressCorrection.getCity(),
						zip:     addressCorrection.getZip(),
						sender:  sender
				};
				if(addressCorrection.needRequest(data)) {
					addressCorrection.xhr = jQuery.get(myPlugin.ajaxurl, data, function(response) {
						addressCorrection.log('Response is : ' + response);
						var arrayOfObjectProperty = [];
						var jsonObj = jQuery.parseJSON(response);
						jQuery.each(jsonObj, function(key, value) {
							arrayOfObjectProperty.push(value);
						});
						suggests(arrayOfObjectProperty);
					});
					addressCorrection.log('Query has been sent. ' + addressCorrection.compileUrl(data) );
				}
				suggests([]);
			},
			renderItem: function (item, search){
				search = search.replace(/[-\/\\^$*+?.()|[\]{}]/g, '\\$&');
				var re = new RegExp('(' + search.split(' ').join('|') + ')', 'gi');
				if('address' == sender) {
					var str = item.street;
					return '<div class="autocomplete-suggestion" data-street="' + item.street + '" data-val="' + str + '">' + str.replace(re, '<b>$1</b>') + '</div>';
				}
				var str = item.postcode + ', ' + item.city;
				return '<div class="autocomplete-suggestion" data-city="' + item.city + '" data-postcode="' + item.postcode + '" data-val="' + str + '">' + str.replace(re, '<b>$1</b>') + '</div>';
			},
			onSelect: function(e, term, item){
				if('address' == sender) {
					jQuery('#' + addressCorrection.groupPrefix + '_address_1').val(jQuery(item).attr('data-street'));
					addressCorrection.addressConfirmed = true;
				}
				else {
					jQuery('#' + addressCorrection.groupPrefix + '_city').val(jQuery(item).attr('data-city'));
					jQuery('#' + addressCorrection.groupPrefix + '_postcode').val(jQuery(item).attr('data-postcode'));
					addressCorrection.postCode_CityConfirmed = true;
				}
			}
		};
	},

	isGermany: function () {
		var addressCorrection = this;

		return jQuery('#' + addressCorrection.groupPrefix + '_country').val().toLowerCase() == 'de';
	},

	log: function (message) {
		if (myPlugin.isConsoleResponseNeeded) {
			console.log(message);
		}
	},

	compileUrl: function (data) {
		return myPlugin.ajaxurl + '/' + jQuery.param( data );
	},

	needConfirmation: function () {
		var addressCorrection = this;
		//check if this kind of address presents and visible for user
		if(!jQuery('#' + addressCorrection.groupPrefix + '_country').length || !jQuery('#' + addressCorrection.groupPrefix + '_country').is(":visible")) {
			return false;
		}
		return addressCorrection.isGermany() && (!addressCorrection.addressConfirmed || !addressCorrection.postCode_CityConfirmed);
	},

	requestForSuggestions: function() {
		var addressCorrection = this;

		var data = {
				action:  'action',
				address: addressCorrection.getAddress(),
				city:    addressCorrection.getCity(),
				zip:     addressCorrection.getZip(),
				sender:  'submit'
		};

		addressCorrection.suggestions = [];
		xhr = jQuery.get(myPlugin.ajaxurl, data, function(response) {
			addressCorrection.log('Response is : ' + response);
			addressCorrection.suggestions = jQuery.parseJSON(response);
		});
		addressCorrection.log( 'Query has been sent. ' + addressCorrection.compileUrl(data) );

		return xhr;
	},

	disable: function() {
		var addressCorrection = this;

		addressCorrection.addressConfirmed = addressCorrection.postCode_CityConfirmed = true;
	},

	getConfirmationPopupHtml: function(jqTemplate) {
		var addressCorrection = this;

		if(0 == addressCorrection.suggestions.length) {
			return '';
		}

		jQuery('.modal-title', jqTemplate).html(addressCorrection.confirmationHeader);

		jQuery.each(addressCorrection.suggestions, function(key, value) {
			jQuery('.enderecoCorrectedSuggestions', jqTemplate).append('<label><input type="radio" name="' + addressCorrection.groupPrefix + 'addressCorrection" data-id="' + (1 + key) + '">' + value.postcode + ' ' + value.city + ' ' + value.street + '</label><br />');
			jQuery('.enderecoCorrectedSuggestions input', jqTemplate).last().attr({
				'data-city':     value.city,
				'data-postcode': value.postcode,
				'data-address':  value.street
			});
		});
		city = addressCorrection.getCity();
		postcode = addressCorrection.getZip();
		address = addressCorrection.getAddress();
		jQuery('.enderecoCurrentInput', jqTemplate).html('<label><input type="radio" name="' + addressCorrection.groupPrefix + 'addressCorrection" data-id="0" checked="checked">' + postcode + ' ' + city + ' ' + address + '</label><br />');
		jQuery('.enderecoCurrentInput input', jqTemplate).last().attr({
			'data-city':     city,
			'data-postcode': postcode,
			'data-address':  address
		});

		return jqTemplate.html();
	},

	processFormData: function() {
		var addressCorrection = this;

		document.getElementsByName(addressCorrection.groupPrefix + 'addressCorrection').forEach(function(node) {
			if (jQuery(node).attr('checked') == 'checked' && jQuery(node).attr('data-id') != 0) {
				jQuery('#' + addressCorrection.groupPrefix + '_city').val(jQuery(node).attr('data-city'));
				jQuery('#' + addressCorrection.groupPrefix + '_postcode').val(jQuery(node).attr('data-postcode'));
				jQuery('#' + addressCorrection.groupPrefix + '_address_1').val(jQuery(node).attr('data-address'));
			}
		});

		addressCorrection.disable();
	}

};

// 1) надо убедиться, надо ли эскейпить атрибуты 2)дублирование строк в сабмите

var ConfirmationPopup = {

	corrections: [],

	jqTemplate: null,

	init: function (formSubmitCallback) {
		var confirmationPopup = this;

		ConfirmationPopup.jqTemplate = jQuery('#enderecoAddressCheckModal .panel-body').detach();

		jQuery('#enderecoAddressCheckSubmit').click(function(){
			for(var i in confirmationPopup.corrections) {
				confirmationPopup.corrections[i].processFormData();
			}
			jQuery('#enderecoAddressCheckModal').modal('hide');
			formSubmitCallback();
		});

	},

	addCorrection: function (correction) {
		var confirmationPopup = this;

		confirmationPopup.corrections.push(correction);
	},

	needConfirmation: function () {
		var confirmationPopup = this;

		for(var i in confirmationPopup.corrections) {
			if(confirmationPopup.corrections[i].needConfirmation()) {
				return true;
			}
		}
		return false;
	},

	confirm: function (formSubmitCallback) {
		var confirmationPopup = this;

		var requests = [];
		for(var i in confirmationPopup.corrections) {
			if(confirmationPopup.corrections[i].needConfirmation()) {
				requests.push(confirmationPopup.corrections[i].requestForSuggestions());
			}
		}

		jQuery.when.apply(jQuery, requests).done(function() {
			var html = '';

			for(var i in confirmationPopup.corrections) {
				if(confirmationPopup.corrections[i].needConfirmation()) {
					html += confirmationPopup.corrections[i].getConfirmationPopupHtml(confirmationPopup.jqTemplate.clone());
				}
			}
			if(html) {
				jQuery('#enderecoAddressCheckModal .modal-body').html(html);
				jQuery('#enderecoAddressCheckModal').modal('show');
				return;
			}
			for(var i in confirmationPopup.corrections) {
				confirmationPopup.corrections[i].disable();
			}
			formSubmitCallback();
		});
	}

};

var MainFormCheckout = {

	submit: function() {
		jQuery('form.checkout.woocommerce-checkout').submit();
	},

	onSubmit: function(processCallback) {
		jQuery('form.checkout.woocommerce-checkout').on('checkout_place_order', function(event) {
			return processCallback(event);
		});
	}

}

var MainFormProfile = {

	submit: function() {
		jQuery('div.woocommerce-MyAccount-content form').submit();
	},

	onSubmit: function(processCallback) {
		jQuery('div.woocommerce-MyAccount-content button').on('click', function(event) {
			return processCallback(event);
		});
	}

}