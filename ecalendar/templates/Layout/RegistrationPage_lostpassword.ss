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
		<p>You must be logged out to reset a password<p>
	<% else %>	
	<div class="LoginRegistrationForm">
		<p><% _t('Security.NOTERESETPASSWORD', 'Enter your e-mail address and we will send you a link with which you can reset your password') %>.</p>
		$LostPasswordForm
	</div>
	<% end_if %>
  <% if Menu(2) %>
    </div>
  <% end_if %>
</div>

