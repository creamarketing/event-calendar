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
    <% if IsLoggedIn %>
		<p>You must be logged out to reset a password</p>
	<% else %>	
	<div class="LoginRegistrationForm">
		<p><% sprintf(_t('Security.PASSWORDSENTTEXT', "Thank you! A reset link has been sent to  '%s', provided an account exists for this email address."),$PasswordSentToEmail) %></p>
		<% sprintf(_t('RegistrationPage.BACKTOLOGIN', 'Click <a href="%s">here</a> to go back to the login page.'),$Link) %>
	</div>
	<% end_if %>
  <% if Menu(2) %>
    </div>
  <% end_if %>
</div>

