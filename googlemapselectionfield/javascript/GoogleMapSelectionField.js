(function($) {
	$(document).ready(function() {
	
		$("input[name=$Name_MapURL]").val("User did not generate a Url as the field is not required");
		$('#Form_Form').submit(function() {
			var checkval = $("input[name=$Name_MapURL]").val();
			if( checkval == "User did not generate a Url as the field is not required" && $("#EditableGoogleMapSelectableField38").attr("class") == "field googlemapselectable  requiredField"){
				alert("please click 'Go' to check address in the map");
				return false;
			}
		});
		
		// default values
		var map = new GMap2(document.getElementById("map_$Name"));
		var center = new GLatLng($DefaultLat, $DefaultLon);
		var geocoder = new GClientGeocoder();
		var marker = new GMarker(center, {draggable: true});
		map.setCenter(center, $Zoom);
		map.addOverlay(marker);
		map.addControl(new GMenuMapTypeControl());
		map.addControl(new GSmallZoomControl3D());
		
		GEvent.addListener(marker, "dragend", function(overlay, point) {
			var point = marker.getLatLng();
			map.setCenter(point);
			geocoder.getLocations(new GLatLng(point.y, point.x), function(response) {
				if(response.Status.code == 200) {
					$("input[name=$Name]").val(response.Placemark[0].address);
					$("input[name=$Name_MapURL]").val("http://maps.google.com/?ll=" + point.toUrlValue() +"&q="+ point.toUrlValue() +"&z="+ map.getZoom());
				}
			});
		});

		$("input[name=$Name]").focus(function() {
			if($(this).val() == $(this).siblings("input[name=GoogleMAP_Default]:first").val()) {
				$(this).val("");
			}
		});
		$("input[name=$Name]").blur(function() {
			if(!$(this).val().length) {
				$(this).val($(this).siblings("input[name=GoogleMAP_Default]:first").val());
			}
		});		
		
		
		$("input.googleMapAddressSubmit").click(function() {
			var address = $("input.googleMapAddressField").val();
		 	geocoder.getLatLng(
		 		address,
		 		function(point) {
		 			if (!point) {
		 				alert(address + " not found");
		 			} else {
		 				map.setCenter(point,16);
		 				marker.setPoint(point);
						$("input[name=$Name_MapURL]").val("http://maps.google.com/?ll=" + point.toUrlValue() +"&q="+ point.toUrlValue() +"&z="+ map.getZoom());
		 			}
		 		}
			);
			return false;
		});

		// On init
		var address = $("input.googleMapAddressField").val();
		geocoder.getLatLng(
				address,
				function(point) {
		 			if (!point) {
		 				// Ignore invalid addresses
		 			} else {
		 				map.setCenter(point,16);
		 				marker.setPoint(point);
						$("input[name=$Name_MapURL]").val("http://maps.google.com/?ll=" + point.toUrlValue() +"&q="+ point.toUrlValue() +"&z="+ map.getZoom());
		 			}
		 		}
		);
	});
})(jQuery);