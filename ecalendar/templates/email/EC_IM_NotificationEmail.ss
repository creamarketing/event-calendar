<% _t('IM_NotificationEmail.GREETING', 'Hello') %> $Member.FirstName,<br/>
<% if Message %>
	<% control Message %>
	<% if EmailBodyOnly = 1 %>
		<p>$Body.Parse(BBCodeParser)</p>
	<% else %>
	<br/>
	<strong><% _t('IM_NotificationEmail.RECEIVEDTEXT', 'You have received an internal message that contains the follow') %>:</strong><br/><br/>
	<strong><% _t('IM_Message.FROM', 'From') %>:</strong> $NiceFrom<br/>
	<strong><% _t('IM_Message.SUBJECT', 'Subject') %>:</strong> $Subject<br/>
	<strong><% _t('IM_Message.BODY', 'Body') %>:</strong><br/><p>$Body.Parse(BBCodeParser)</p><br/>
	<% end_if %>
	<% end_control %>
<% end_if %>
<br/>
<% sprintf(_t('IM_NotificationEmail.IM_FOOTER','You can login to the Event Calendar <a href="%s">here</a>.'),$LoginLink) %>
