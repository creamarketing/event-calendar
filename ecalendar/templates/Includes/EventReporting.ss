<% if showEvent = 1 %>
<% if nothingFound = 1 %>
<% else %>
<div id="ReportEvent">
	<a href="$ReportURL"><img src="ecalendar/images/report-icon.gif"/><span><% _t('EventPage.REPORTEVENT', 'Report this event') %></span></a>
</div>
<% end_if %>
<% end_if %>