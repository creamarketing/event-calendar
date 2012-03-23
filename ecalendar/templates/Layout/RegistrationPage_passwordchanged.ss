<div class="typography">
  <% if Menu(2) %>
    <% include SideBar %>
    <div id="Content">
  <% end_if %>

  <% if Level(2) %>
      <% include BreadCrumbs %>
  <% end_if %>
      
    <div id="StatusMessage" class="Message" style="display:none;"></div>
    <div id="ErrorMessage" class="Message" style="display:none;"></div>
    <h2>$Title</h2>
	<p>
		<% _t('Member.SUBJECTPASSWORDCHANGED', 'Your password has been changed') %>.
	</p>
	<% sprintf(_t('RegistrationPage.BACKTOLOGIN', 'Click <a href="%s">here</a> to go back to the login page.'),$Link) %>	
  <% if Menu(2) %>
    </div>
  <% end_if %>
</div>

