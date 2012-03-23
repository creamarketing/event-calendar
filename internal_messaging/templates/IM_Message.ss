<input type="hidden" id="Message{$ID}" name="MessageID" value="{$ID}">
<div class="message-container">
	<div class="message-header">
		<div class="message-header-field"><span class="message-header-fieldname"><% _t('IM_Message.FROM', 'From') %>: </span><span class="message-header-fieldvalue">$NiceFrom</span><span class="message-header-fieldname"><% _t('IM_Message.TO', 'To') %>: </span><span class="message-header-fieldvalue">$NiceTo</span></div>
		<div class="message-header-field"><span class="message-header-fieldname"><% _t('IM_Message.SUBJECT', 'Subject') %>: </span><span class="message-header-fieldvalue">$Subject</span></div>
	</div>
	<div class="message-body">
		$Body.Parse(BBCodeParser)
	</div>
	<div class="message-actions">
		<% if IsTrash %>
			<button type="button" id="Message{$ID}_Restore" class="action-restore"><% _t('IM_Message.RESTORE', 'Restore') %></button>
			<input type="hidden" id="Message{$ID}_RestoreLink" name="RestoreLink" value="$Top.RestoreLink">
			<button type="button" id="Message{$ID}_Delete" class="action-delete"><% _t('IM_Message.DELETE', 'Delete') %></button>
			<input type="hidden" id="Message{$ID}_DeleteLink" name="DeleteLink" value="$Top.DeleteLink">				
		<% else %>
			<% if CanReply %>
				<button type="button" id="Message{$ID}_Reply" class="action-reply"><% _t('IM_Message.REPLY', 'Reply') %></button>
				<input type="hidden" id="Message{$ID}_ReplyLink" name="ReplyLink" value="$Top.ReplyLink">
			<% end_if %>
			<button type="button" id="Message{$ID}_Trash" class="action-trash"><% _t('IM_Message.TRASH', 'Trash') %></button>
			<input type="hidden" id="Message{$ID}_TrashLink" name="TrashLink" value="$Top.TrashLink">			
		<% end_if %>
	</div>
</div>

