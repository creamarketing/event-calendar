<% if Messages.MoreThanOnePage %>
	<% if FirstLink %><a class="First" href="$FirstLink" title="<% _t('DataObjectManager.VIEWFIRST', 'View first') %> $PageSize"><img src="internal_messaging/images/resultset_first.png" alt="" /></a>
	<% else %><span class="First"><img src="internal_messaging/images/resultset_first_disabled.png" alt="" /></span><% end_if %>
	<% if PrevLink %><a class="Prev" href="$PrevLink" title="<% _t('DataObjectManager.VIEWPREVIOUS', 'View previous') %> $PageSize"><img src="internal_messaging/images/resultset_previous.png" alt="" /></a>
	<% else %><img class="Prev" src="internal_messaging/images/resultset_previous_disabled.png" alt="" /><% end_if %>
	<span class="Count">
		<% _t('IM_Controller.DISPLAYING', 'Displaying') %> $FirstItem <% _t('IM_Controller.TO', 'to') %> $LastItem <% _t('IM_Controller.OF', 'of') %> $TotalCount
	</span>
	<% if NextLink %><a class="Next" href="$NextLink" title="<% _t('DataObjectManager.VIEWNEXT', 'View next') %> $PageSize"><img src="internal_messaging/images/resultset_next.png" alt="" /></a>
	<% else %><img class="Next" src="internal_messaging/images/resultset_next_disabled.png" alt="" /><% end_if %>
	<% if LastLink %><a class="Last" href="$LastLink" title="<% _t('DataObjectManager.VIEWLAST', 'View last') %> $PageSize"><img src="internal_messaging/images/resultset_last.png" alt="" /></a>
	<% else %><span class="Last"><img src="internal_messaging/images/resultset_last_disabled.png" alt="" /></span><% end_if %>
<% end_if %>
