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
						arrayOfObjectProperty[arrayOfObjectProperty.length] = value.city + ', ' + value.postcode;
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
						arrayOfObjectProperty[arrayOfObjectProperty.length] = value.postcode + ', ' + value.city;
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
				},
				onSelect: function(e, term, item){
					if (sender == 'city') {
						//lol, kek, 555
						var links = term.split(', ');
						jQuery('#billing_city').val(links[0].trim());
						jQuery('#billing_postcode').val(links[1].trim());
					}
					else if (sender == 'zip') {
						var links = term.split(',');
						jQuery('#billing_city').val(links[1].trim());
						jQuery('#billing_postcode').val(links[0].trim());
					}
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
	
	//jQuery('#billing_postcode').on('input', function() {
	//	dataRequest('zip');
	//});
	jQuery('#billing_postcode').autoComplete({
		minChars: 0,
		source: function(input, suggests) {
			var data = {
				action: 'action',
				address: jQuery('#billing_address_1').val(),
				city: jQuery('#billing_city').val(),
				zip: jQuery('#billing_postcode').val(),
				sender: 'zip'
			};
			jQuery.get( myPlugin.ajaxurl, data, function(response) {
				var arrayOfObjectProperty = [];
				var obj = jQuery.parseJSON(response);
				var htmlElement;
		
				jQuery.each( obj, function( key, value ) {
					if (key = 'postCode') {
						arrayOfObjectProperty[arrayOfObjectProperty.length] = value;//.postcode + ', ' + value.city;
					}
				});
				suggests(arrayOfObjectProperty);
			});
		},
		renderItem: function (item, search){
			var str = item.postcode + ', ' + item.city;
			
			search = search.replace(/[-\/\\^$*+?.()|[\]{}]/g, '\\$&');
			var re = new RegExp("(" + search.split(' ').join('|') + ")", "gi");
			return '<div class="autocomplete-suggestion" data-city="' + item.city + '" data-postcode="' + item.postcode + '" data-val="' + str + '">' + str.replace(re, "<b>$1</b>") + '</div>';

		},
		onSelect: function(e, term, item){
			jQuery('#billing_city').val(jQuery(item).attr("data-city"));
			jQuery('#billing_postcode').val(jQuery(item).attr("data-postcode"));
		}
	});
});