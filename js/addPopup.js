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

			var newHtml = startText + '<a class="popupText" style="cursor:default" hoverText="' + text + '">' + linkText + '</a>' + oldHtml;

			labelSelector.html(newHtml);
		}
	}

	$('.popupText').hover(function() {
		var hoverText = $(this).attr('hoverText');

		$('#popupDisplayModal').html("<div class='ui-widget ui-dialog ui-widget-content ui-corner-all ui-front' " +
						"style='width:200px;min-height:50px;padding:10px;margin-left:2px'>" + hoverText + "</div>")
				.show()
				.position({my:"left center",at:"right top",of: $(this)
		});
	}, function() {
		$('#popupDisplayModal').html("").hide();
	});

	// Make this onHover instead of click and make it just an empty box
	// instead of using the dialog setup with a header and button
});