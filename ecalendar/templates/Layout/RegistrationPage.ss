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
    
           
      <% if IsVerifiedEmail %>
        <% if TryVerifyMessage %>
          <span class="LoggedInMessage">$TryVerifyMessage</span>          
        <% else %>
          $OrganizerLoggedIn
        <% end_if %>
        <a href="admin/ecalendar"><% _t('RegistrationPage.GOTOCALENDARADMIN', 'Open the Eventcalendar administration') %></a>
      <% else %>
        <span class="LoggedInMessage">$LoggedInMessage</span>
        <% if TryVerifyMessage %>
          <span class="LoggedInMessage">$TryVerifyMessage</span> 
        <% else %>
          <% sprintf(_t('RegistrationPage.CHECKYOURMAIL', 'You have registered for Ostrobothnia Eventcalendar but not yet verified your e-mail address. Please check your emails and click the link in the message with the subject "<strong>%s</strong>".'),$EmailVerifySubject) %>
        <% end_if %> 
      <% end_if %>  
        
    <% else %>
      <% if TryVerifyMessage %>
        <span class="LoggedInMessage">$TryVerifyMessage</span>
      <% else %>
         $Content
      <% end_if %>   

	<div class="LoginRegistrationForm">
		<div id="Actions">
			<% if TryVerifyMessage %>
				<input id="LoginAction" type="radio" name="loginOrRegister" checked="checked" onclick="SetAction();" style="display: none"/>
				$SwitchToLogin
			<% else %>
			<input id="LoginAction" type="radio" name="loginOrRegister" checked="checked" onclick="SetAction();" /><label for="LoginAction"><% _t('RegistrationPage.LOGIN', 'Login') %></label>
			<input id="RegisterAction" type="radio" name="loginOrRegister" onclick="SetAction();" /><label for="RegisterAction"><% _t('RegistrationPage.REGISTER', 'Register as organizer') %></label>
			<% end_if %>
		</div>
		<% if TryVerifyMessage %>
		<% else %>
		<div class="clear">&nbsp;</div>
		<% end_if %>
		<div id="Forms">
			<div id="Login">
				$OrganizerLoginForm
			</div>
			<div id="Registration" style="display:none;">
				<% if TryVerifyMessage %>
				<% else %>
				$OrganizerRegistrationForm
				<% end_if %>
			</div>
		</div>
	</div>	  
    <% end_if %>
    
    <br />
    $Form
    $PageComments
  <% if Menu(2) %>
    </div>
  <% end_if %>
</div>

