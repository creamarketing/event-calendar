<script type="text/javascript">
	var eCalendarAdminHref = '{$BaseHref}admin/ecalendar/';
	var changeURL = eCalendarAdminHref + 'change';
</script>

<% if view != default %>
<div id="GoHomeLink"><a href="{$BaseHref}admin/ecalendar/"><span><% _t('eCalendarAdmin.GOHOME', 'Go to first page') %></span><img src="ecalendar/images/go-home.png"/></a></div>
<% end_if %>

<% if view = default %>

	$FakeDOMRequirements
	<% include PermissionRequestInfobox %>
	<% include NewAssociationCreatedInfobox %>

	<div id="WelcomeMessage">
		<h1><% _t('FirstPage.MENUTITLE','Ostrobothnia Eventcalendar') %></h1>
		<p><% _t('FirstPage.WELCOMEMESSAGE','Welcome to the event calendar') %></p>
		
		<br />
		<div id="FirstPageLinks">			
			<% if FrontPageLinks %>
				<% control FrontPageLinks %>
					<% if MultipleOf(4) %><div class="clear">&nbsp;</div><% end_if %>
					<div class="frontpage-link">
						<div class="frontpage-link-header"><a class="$LinkClass" href="$Link" $LinkExtra><span class="frontpage-link-header-text" style="background-image: url($LinkIcon)">$LinkHeader</span></a></div>
						<div class="frontpage-link-help">$LinkHelp</div>
					</div>
				<% end_control %>
				<div class="clear">&nbsp;</div>
			<% end_if %>
		</div>
	</div>
<% else_if view = editmessages %>
	$IncludeIMConfirmScripts
	<div id="MessagesContainer">
		<h2><% _t('eCalendarAdmin.MESSAGES', 'Messages') %></h2>	
		$InternalMessages
	</div>
<% else_if view = handleregistrations %>
	<% if UnhandledRegistrations %>
	<div id="FirstPageInfoFeed">	
		<h2><% _t('UnhandledRegistrations.HEADER', 'Registrations not confirmed <small>(by Admin or Moderator)</small>') %></h2>
	</div>
	<% end_if %>

	<% if showInNavigation(AssociationOrganizer) %>	
		<div class="form-container" <% if UnhandledAssociationOrganizers %><% else %>style="display: none"<% end_if %>>
			<h3><% _t('UnhandledRegistrations.ORGANIZERS', 'Unhandled organizers') %></h3>
			$EditOrganizersForm_NotConfirmed
		</div>
	<% end_if %>

	<% if showInNavigation(Association) %>	 
		<div class="form-container" <% if UnhandledAssociations %><% else %>style="display: none"<% end_if %>>
			<h3><% _t('UnhandledRegistrations.ASSOCIATIONS', 'Unhandled associations') %></h3>
			$EditAssociationsForm_New
		</div>
	<% end_if %>

	<% if showInNavigation(PermissionRequests) %>
	<div class="form-container" <% if UnhandledPermissionRequests %><% else %>style="display: none"<% end_if %>>
		<h3><% _t('UnhandledRegistrations.PERMISSIONREQUESTS', 'Unhandled permission requests') %></h3>
		$EditPermissionRequestsForm
	</div>
	<% end_if %>
	
	<% if showInNavigation(UserInviteRequests) %>
	<div class="form-container" <% if UnhandledUserInviteRequests %><% else %>style="display: none"<% end_if %>>
		<h3><% _t('UnhandledRegistrations.USERINVITEREQUESTS', 'Unhandled user invite requests') %></h3>
		$EditUserInviteRequestsForm
	</div>
	<% end_if %>	

	<% if showInNavigation(Event) %>	 
		<div class="form-container" <% if UnhandledEvents %><% else %>style="display: none"<% end_if %>>
			<h3><% _t('UnhandledRegistrations.EVENTS', 'Unhandled events') %></h3>
			$EditEventsForm_Unhandled
		</div>
	<% end_if %>
<% else_if view = editlanguages %>
	<div class="form-container">
		<h2><% _t('CalendarLocale.PLURALNAME', 'Languages') %></h2>
		$EditLocalesForm
	</div>
<% else_if view = editmunicipals %>
	<div class="form-container">
		<h2><% _t('Municipal.PLURALNAME', 'Municipalities') %></h2>
		$EditMunicipalsForm
	</div>
<% else_if view = editevents %>
	<div class="form-container">
		<h2><% _t('Event.PLURALNAME', 'Events') %></h2>
		<p><% _t('Event.HELPTEXT_1', 'All your events will be listed on this page. You can create a new event by clicking on the "Add new event" button. When you move the mouse cursor above an event, the entire row will highlight and icons will appear on the right side. The icons allows you to edit, delete and duplicate an event.') %></p>
	</div>
	<div class="form-container">
		<h2><% _t('Event.UPCOMINGEVENTS', 'Upcoming events') %></h2>
		$EditEventsForm_Mine
	</div>
	
	<div class="form-container" <% if CountEvents(Draft) %><% else %>style="display: none"<% end_if %>>
		<h2><% _t('Event.DRAFTEVENTS', 'Draft events') %></h2>
		$EditEventsForm_Draft
	</div>
	<div class="form-container" <% if CountEvents(History_Mine) %><% else %>style="display: none"<% end_if %>>
		<h2><% _t('Event.PASTEVENTS', 'Past events') %></h2>
		$EditEventsForm_History_Mine
	</div>
	
	<div class="form-container" <% if CountEvents(Others) %><% else %>style="display: none"<% end_if %>>
		<h2><% _t('Event.UPCOMINGEVENTS_OTHERS', 'Upcoming events (from other users)') %></h2>
		$EditEventsForm_Others
	</div>
	<div class="form-container" <% if CountEvents(History_Others) %><% else %>style="display: none"<% end_if %>>
		<h2><% _t('Event.PASTEVENTS_OTHERS', 'Past events (from other users)') %></h2>
		$EditEventsForm_History_Others
	</div>
<% else_if view = editcategories %>
	<div class="form-container">
		<h2><% _t('EventCategory.PLURALNAME', 'Categories') %></h2>
		$EditCategoriesForm
	</div>
<% else_if view = newassociation %>
    $AddAssociationForm
<% else_if view = editassociations %>
	<% include PermissionRequestInfobox %>
	<% include NewAssociationCreatedInfobox %>
	<div class="form-container">
		<h2><% _t('Association.PLURALNAME', 'Associations') %></h2>
		$EditAssociationsForm
	</div>
<% else_if view = editorganizers %>
	<% include UserInviteRequestInfobox %>
	<div class="form-container">
		<h2><% _t('eCalendarAdmin.USERSANDROLES', 'Users and roles') %></h2>
		$EditOrganizersForm
	</div>
<% else_if view = eventreport %>
	<div class="form-container">
		$EventReportForm.ReportForm
	</div>
<% else_if view = dataexport %>
	<div class="form-container">
		$DataExportForm.ReportForm
	</div>
<% else_if view = logreport %>
	<% if showInNavigation(LogReport) %>
		<div class="form-container">
			<h2><% _t('LogReport.NAME', 'Log report') %></h2>
			$LogEntryForm
		</div>
	<% end_if %>
<% end_if %>
