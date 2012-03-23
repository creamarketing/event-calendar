<% if nothingFound = 1 %>
	<% _t('EventPage.NOHITS', 'No hits') %>
<% else %>
	<table id="SearchResultsTable">
		<thead>
			<tr>
				<th style="width: 12%"><a href="{$SortByLink.Date}$AppendIFrameParam(&)"><% _t('EventPage.DATE', 'Date') %></a></th>
				<th style="width: 10%"><a href="{$SortByLink.Period}$AppendIFrameParam(&)"><% _t('EventPage.PERIOD', 'Period') %></a></th>
				<th><a href="{$SortByLink.Title}$AppendIFrameParam(&)"><% _t('Event.SINGULARNAME', 'Event') %></a></th>
				<th style="width: 16%; text-align: right"><a href="{$SortByLink.Categories}$AppendIFrameParam(&)"><% _t('EventPage.CATEGORIES', 'Categories') %></a></th>
				<th style="width: 15%; text-align: right"><a href="{$SortByLink.Municipality}$AppendIFrameParam(&)"><% _t('EventPage.MUNICIPALITY', 'Municipality') %></a></th>
				<th style="width: 15%; text-align: right"><a href="{$SortByLink.Place}$AppendIFrameParam(&)"><% _t('EventPage.LOCATION', 'Place') %></a></th>
			</tr>
		</thead>
		<tbody>
			<% control Events %>
				<tr class="<% if Odd %>odd<% end_if %>">
					<td><% control ShowMoreDates.First %>$Start.Format(d.m.Y)<% end_control %><br/>
						<% if ShowMoreDatesPopup %>
						<a class="more-dates-tooltip"><% _t('EventPage.SHOWMORE', 'Show more') %></a>
						<div class="more-dates-tooltip-data"><% control ShowMoreDates %><% if ShowInMore %><% if First %><span style="font-weight: bold; font-size: 1.1em"><% end_if %>$Start.Format(d.m.Y) <% _t('EventPage.CLOCK', 'at') %> $Start.Format(H:i)<% if HasEnd %> - $End.Format(H:i)<% end_if %><% if First %></span><% end_if %><br/><% end_if %><% end_control %></div>
						<% end_if %>
					</td>
					<td><% if ShowPeriod %>$Start.Format(d.m.Y)<br/>$End.Format(d.m.Y)<% end_if %></td>
					<td><a href="{$Top.AbsoluteLink}showEvent/$ID$Top.AppendIFrameParam(?)">$Title</a><br/>
						$EventTextShort
					</td>
					<td style="text-align: right; padding-right: 0px;"><% control Categories %>
							$Name<br/>
						<% end_control %></td>
					<td style="text-align: right; padding-right: 0px; padding-left: 5px;">$Municipality</td>					
					<td style="text-align: right; padding-right: 0px; padding-left: 5px;">$Place</td>
				</tr>			
			<% end_control %>				
			<% if Events.MoreThanOnePage %>
				<tr class="pagination">
					<td colspan="6">
					<% if Events.PrevLink %><a href="$Events.PrevLink$AppendIFrameParam(&)">« <% _t('Pagination.PREV', 'Prev') %></a> | <% end_if %>
						<% control Events.Pages %>
							<% if CurrentBool %>
								<strong>$PageNum</strong>
							<% else %>
								<a href="$Link$Top.AppendIFrameParam(&)" title="<% _t('Pagination.GO', 'GO') %> $PageNum">$PageNum</a>
							<% end_if %>
						<% end_control %>
						<% if Events.NextLink %> | <a href="$Events.NextLink$AppendIFrameParam(&)"><% _t('Pagination.NEXT', 'Next') %> »</a><% end_if %>
					</td>
				</tr>
			<% end_if %>
		</tbody>		
	</table>
	<div id="ShowResultsRss"><a href="$RSSLink" target="_blank"><img src="ecalendar/images/small_rss_icon.png"/><span><% _t('EventPage.SHOWRESULTSASRSS', 'Show results as RSS') %></span></a></div>
<% end_if %>