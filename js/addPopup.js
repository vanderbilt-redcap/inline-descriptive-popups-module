/**
 * Created by mcguffk on 3/9/2017.
 */
ExternalModules.popupTexts = [];
ExternalModules.popupLocations = [];
ExternalModules.popupFields = [];

$(document).ready(function() {
	for(var i in ExternalModules.popupTexts) {
		var text = ExternalModules.popupTexts[i];
		var location = ExternalModules.popupLocations[i];
		var field = ExternalModules.popupFields[i];

		if(text && location && field) {
			var fieldSelector = $('#' + field + '-tr');

			if(fieldSelector.length == 0) continue;

			var labelSelector = fieldSelector.children('.labelrc').first();

			// If there are question numbers, these will appear as labelrd too
			if(labelSelector.is('.questionnum')) {
				labelSelector = labelSelector.next();
			}

			// If on data-entry form, sometimes actual label string is in table below labelrc
			if(labelSelector.find('td').length != 0) {
				labelSelector = labelSelector.find('td').first();
			}
			location = location.split(',');
			location[0] = parseInt(location[0]);
			location[1] = parseInt(location[1]);

			var oldHtml = labelSelector.html().trim();
			var startText = oldHtml.substr(0,location[0]);
			var linkText = oldHtml.substr(location[0],location[1]);
			var oldHtml = oldHtml.substr(location[0] + location[1]);

			var newHtml = startText + '<a class="popupText" hoverText="' + text + '">' + linkText + '</a>' + oldHtml;

			labelSelector.html(newHtml);
		}
	}

	$('.popupText').click(function() {
		var hoverText = $(this).attr('hoverText');

		//alert(hoverText);

		$('#popupDisplayModal').html(hoverText).dialog({
			modal: false,
			width: (isMobileDevice ? $(window).width() : 500),
			buttons: {
				Okay: function() { $(this).dialog('close'); }
			},
			closeOnEscape: true,
			position: {
				my: "left center",
				at: "right center",
				of: $(this)
			}
		}).onblur(function() {
			$(this).dialog('close');
		});
	});
});