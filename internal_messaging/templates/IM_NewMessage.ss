<div class="message-container newmessage-container">
	<form name="MessageSendForm" method="post" enctype="application/x-www-form-urlencoded">
		<fieldset>
			<div class="message-header">
				<div class="message-header-field"><span class="message-header-fieldname"><% _t('IM_Message.TO', 'To') %>: </span><span class="message-header-fieldvalue">$ToField</span></div>
				<div class="message-header-field"><span class="message-header-fieldname"><% _t('IM_Message.SUBJECT', 'Subject') %>: </span><span class="message-header-fieldvalue">$SubjectField</span></div>
			</div>
			<div class="message-body">
				$BodyField
			</div>
		</fieldset>
	</form>	
	<div class="message-actions">
		<button type="button" class="action-send"><% _t('IM_Message.SEND', 'Send') %></button>
		<input type="hidden" name="SendLink" value="$Top.Link(newMessage/send)">			
	</div>
</div>