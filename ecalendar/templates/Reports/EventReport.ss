<% if Start || End %>
	<p><b>Period:</b> $Start - $End</p>
<% end_if %>
<% if Events %>
	<table>
		<tr>
			<th width="35%"><% _t('Event.TITLE', 'Title') %><br/><% _t('Event.DESCRIPTIONSHORT', 'Short description') %><br/><% _t('Municipal.SINGULARNAME', 'Municipality') %>, <% _t('Event.PLACE', 'Place') %><br/><% _t('EventCategory.PLURALNAME', 'Categories') %></th>
			<th width="20%"><% _t('Event.START', 'Start') %><br/><% _t('Event.END', 'End') %></th>
			<th width="20%"><% _t('Association.SINGULARNAME', 'Association') %><br/><% _t('AssociationOrganizer.SINGULARNAME', 'User') %></th>
			<th width="20%"><% _t('Event.CREATORTITLE', 'Created by') %></th>
			<th><% _t('Event.STATUS', 'Status') %></th>
		</tr>
		<% control Events %>
			<tr>
				<td>$Title<br/>$EventTextShort<br/>$Municipal.Name, $Place<br/>$NiceCategories</td>
				<td>$PeriodNiceWithBr</td>
				<td>$OrganizerName</td>
				<td>$CreatorName</td>
				<td>$NiceStatus</td>
			</tr>
		<% end_control %>
	</table>
<% else %>
	<p><% _t('EventReport.NOTHINGFOUND', 'No events found') %></p>
<% end_if %>