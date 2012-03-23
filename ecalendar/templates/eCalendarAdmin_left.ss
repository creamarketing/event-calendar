<div id="leftContainer">
	<ul id="sitetree" class="tree unformatted">
		<li class="Root last">
			<a href="{$Link}"><% _t('eCalendarAdmin.FRONTPAGE', 'Ostrobothnia events') %></a>
			<ul>
				<% if showInNavigation(Messages) %>
        			<li><a href="{$Link}editmessages" <% if CurrentView(editmessages) %>class="current"<% end_if %>><span class="menu-messages <% if UnreadMessages %>has-unread<% end_if %>"><% _t('eCalendarAdmin.MESSAGES', 'Messages') %></span><span class="menu-unread-messages"><% if UnreadMessages %> ($UnreadMessages)<% end_if %></span></a></li>
				<% end_if %>
				
				<% if UnhandledRegistrations %>
					<li><a href="{$Link}handleregistrations" <% if CurrentView(handleregistrations) %>class="current"<% end_if %>><span class="menu-unhandled-registrations"><% _t('eCalendarAdmin.UNHANDLEDREGISTRATIONS', 'Unhandled registrations') %></span></a></li>
				<% end_if %>
				
				<% if showInNavigation(Event) %>
					<li><a href="{$Link}editevents" <% if CurrentView(editevents) %>class="current"<% end_if %>><% _t('Event.PLURALNAME', 'Events') %></a></li>
				<% end_if %>
				
				<% if showInNavigation(Association) %>						
					<li><a href="{$Link}editassociations" <% if CurrentView(editassociations) %>class="current"<% end_if %>><% _t('Association.PLURALNAME', 'Associations') %></a></li>
				<% end_if %>				
				
       			<% if showInNavigation(AssociationOrganizer) %>
					<li><a href="{$Link}editorganizers" <% if CurrentView(editorganizers) %>class="current"<% end_if %>><% _t('eCalendarAdmin.USERSANDROLES', 'Users & roles') %></a></li>
				<% end_if %>				
				
				<% if showInNavigation(Municipal) %>
        			<li><a href="{$Link}editmunicipals" <% if CurrentView(editmunicipals) %>class="current"<% end_if %>><% _t('eCalendarAdmin.MYMUNICIPALS', 'My municipals') %></a></li>
				<% end_if %>						
        		 				
				<% if showInNavigation(Reports) %> 
				<li class="<% if IsReportsOpen %>children<% else %>closed<% end_if %>">
			      	<a><% _t('eCalendarAdmin.REPORTS', 'Reports') %></a>
			        <ul>
			        	<li><a href="{$Link}eventreport" <% if CurrentView(eventreport) %>class="current"<% end_if %>><% _t('EventReport.NAME', 'Event report') %></a></li>
						<% if showInNavigation(DataExport) %>
							<li><a href="{$Link}dataexport" <% if CurrentView(dataexport) %>class="current"<% end_if %>><% _t('DataExport.NAME', 'Data export') %></a></li>
						<% end_if %>
						<% if showInNavigation(LogReport) %>
							<li><a href="{$Link}logreport" <% if CurrentView(logreport) %>class="current"<% end_if %>><% _t('LogReport.NAME', 'Log report') %></a></li>
						<% end_if %>						
			        </ul>
			      </li>				
				<% end_if %>
				<% if isAdmin %>            
		          <li class="<% if IsSystemSettingsOpen %>children<% else %>closed<% end_if %>">
		            <a><% _t('eCalendarAdmin.SYSTEMSETTINGS', 'System settings') %></a>
		            <ul>
						<li><a href="{$Link}editcategories" <% if CurrentView(editcategories) %>class="current"<% end_if %>><% _t('EventCategory.PLURALNAME', 'Event categories') %></a></li>
		                <li><a href="{$Link}editmunicipals" <% if CurrentView(editmunicipals) %>class="current"<% end_if %>><% _t('Municipal.PLURALNAME', 'Municipals') %></a></li>
		                <li><a href="{$Link}editlanguages" <% if CurrentView(editlanguages) %>class="current"<% end_if %>><% _t('CalendarLocale.PLURALNAME', 'Event languages') %></a></li>
		            </ul>
		          </li>               
        		<% end_if %>        						
				<li><a href="Security/logout" id="LogoutLink"><% _t('eCalendarAdmin.ss.LOGOUT','Log out') %></a></li>
			</ul>
		</li>
	</ul>
	
	<a id="showOrHideLeft" href='#' onclick="showOrHideLeft(); return false;">&nbsp;</a>
</div>

<script type="text/javascript">
	
	var leftWidth = 0;
	function showOrHideLeft() {
		jQuery('.qtip').remove();
		if (jQuery('#left').width() > 12) {
			leftWidth = jQuery('#left').width();
			jQuery('#left').animate(
				{
					width: 12
				},
				{
					duration: 400,
					step: function(){
						fixRightWidth();
					},
					complete: function() {
						jQuery('#sitetree').hide();
						jQuery('#showOrHideLeft').css('background-image', 'url(ecalendar/images/arrowRight.gif)');
						jQuery(window).resize();
					}
				}
			);
		}
		else {
			jQuery('#sitetree').show();
			jQuery('#left').animate(
				{
					width: leftWidth
				},
				{
					duration: 400,
					step: function(){
						fixRightWidth();
					},
					complete: function() {
						jQuery('#showOrHideLeft').css('background-image', 'url(ecalendar/images/arrowLeft.gif)');
						jQuery(window).resize();
					}
				}
			);
		}
	}
	
</script>
