<% if Events %>
<% control Events %>
	$Title<br/>
	<% if Top.AlwaysIncludeShortText %>
		$EventTextShort<br/>
	<% else %>
		<% if HasUniqueShortText %>$EventTextShort<br/><% end_if %>
	<% end_if %>
	<% _t('DataExport.PLACE', 'Place') %>: $DataExportPlaceDetails<br/>
	<% _t('DataExport.TICKETS', 'Tickets') %>: <% if PriceType = NotFree %>$DataExportPriceText<% else %><% _t('DataExport.FREEVENT', 'Free') %><% end_if %><br/>
	<% _t('DataExport.ORGANIZER', 'Organizer') %>: $Association.Name<br/>
	<% _t('DataExport.INFO', 'Info') %>: $Association.DataExportInfo<br/>
	$DataExportDate<br/>
	<% if DataExportMoreDates %>
	<% control DataExportMoreDates %>
		<% control PrettyDates %>
			$Nice<br/>
		<% end_control %>
	<% end_control %>
	<% end_if %>
	<br/>
<% end_control %>
<% else %>
	<p><% _t('EventReport.NOTHINGFOUND', 'No events found') %></p>
<% end_if %>