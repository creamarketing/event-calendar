<% require javascript(sapphire/thirdparty/jquery/jquery.js) %>
<% require javascript(sapphire/thirdparty/jquery-ui/jquery.ui.datepicker.js) %>
<% require javascript(dialog_dataobject_manager/javascript/jquery-ui-1.8.6.custom.min.js) %>
<% require css(dialog_dataobject_manager/css/smoothness/jquery-ui-1.8.6.custom.css) %>

<% require css(ecalendar/css/EventPage.css) %>
<% require javascript(ecalendar/javascript/jquery.qtip-1.0.min.js) %>
<% require javascript(ecalendar/javascript/EventPage.js) %>
<% require javascript(ecalendar/javascript/jquery.validate.min.js) %>

<div class="typography">
	<% if showResults = 1 %>
		<div id="SearchResults">
			<a href="javascript:history.go(-1)">&laquo; <% _t('EventPage.GOBACK', 'Go back') %></a><br/><br/>
			
			<h2><% _t('EventPage.SEARCHRESULTS', 'Search results') %><% if SearchText %> ($SearchText)<% end_if %></h2>
			<% include EventPage_ShowResults %>
		</div>
	<% else_if showEvent = 1 %>
		<div id="ShowEvent">
			<a href="javascript:history.go(-1)">&laquo; <% _t('EventPage.GOBACK', 'Go back') %></a><br/><br/>
			
			<%-- <h2><% _t('Event.SINGULARNAME', 'Event') %></h2> --%>
			<% include EventPage_ShowEvent %>
		</div>
	<% else %>
		<div id="EventSearch">
			$Content
			
			<h2><% _t('EventPage.SEARCH', 'Search') %></h2>
			
			$EventSearchForm
		</div>
		<% if ShowCategories %>	
		<div id="EventCategories">
			<h2><% _t('EventCategory.PLURALNAME', 'Categories') %></h2>
			<% include EventPage_EventCategories %>			
		</div>
		<% end_if %>
		<% if ShowEventsToday %>
		<div id="CurrentEvents">
			<h2><% _t('EventPage.CURRENTEVENTS', 'Current events') %></h2>
			<% include EventPage_CurrentEvents %>
		</div>
		<% end_if %>
		<div class="clear">&nbsp;</div>
	<% end_if %>
</div>