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
	<% if AutoLoginHash %>
		<p><% _t('Security.ENTERNEWPASSWORD', 'Please enter a new password.') %></p>
		<div class="LoginRegistrationForm">
			$ChangePasswordForm
		</div>
	<% else_if MemberChangePassword %>
		<p><% _t('Security.CHANGEPASSWORDBELOW', 'You can change your password below.') %></p>
		<div class="LoginRegistrationForm">
			$ChangePasswordForm
		</div>
    <% else_if InvalidAutoLoginHash %>
		<p>$InvalidAutoLoginHashText</p>
	<% else %>	
		<% _t('Security.ERRORPASSWORDPERMISSION', 'You must be logged in in order to change your password!') %>
	<% end_if %>

  <% if Menu(2) %>
    </div>
  <% end_if %>
</div>

