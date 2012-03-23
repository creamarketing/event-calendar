<input type="hidden" name="RefreshLink" value="$RefreshLink">
<ul class="messages">
	<li class="header">
		<a class="sort-messages" href="{$SortLink(Status)}"><div class="message-status <% if IsCurrentSortField(Status) %>sorted<% end_if %>"><span class="sorted-{$SortDirForTemplate(Status)}">&nbsp;</span></div></a>
		<a class="sort-messages" href="{$SortLink(Subject)}"><div class="message-subject <% if IsCurrentSortField(Subject) %>sorted<% end_if %>"><% _t('IM_Message.SUBJECT', 'Subject') %><span class="sorted-{$SortDirForTemplate(Subject)}"></span></div></a>
		<% if Inbox %>
			<a class="sort-messages" href="{$SortLink(From)}"><div class="message-from <% if IsCurrentSortField(From) %>sorted<% end_if %>"><% _t('IM_Message.FROM', 'Sender') %><span class="sorted-{$SortDirForTemplate(From)}"></span></div></a>
		<% else_if Sent %>
			<a class="sort-messages" href="{$SortLink(To)}"><div class="message-to <% if IsCurrentSortField(To) %>sorted<% end_if %>"><% _t('IM_Message.TO', 'Recipient') %><span class="sorted-{$SortDirForTemplate(To)}"></span></div></a>
		<% else %>			
			<a class="sort-messages" href="{$SortLink(From)}"><div class="message-from <% if IsCurrentSortField(From) %>sorted<% end_if %>"><% _t('IM_Message.FROM', 'Sender') %><span class="sorted-{$SortDirForTemplate(From)}"></span></div></a>
		<% end_if %>
		<a class="sort-messages" href="{$SortLink(Date)}"><div class="message-date <% if IsCurrentSortField(Date) %>sorted<% end_if %>"><% _t('IM_Message.DATE', 'Date') %><span class="sorted-{$SortDirForTemplate(Date)}"></span></div></a>
		<div class="message-actions"><% if Trash %><a href="{$Link}/deleteAll" class="action-trashbox-deleteall"><span class="ui-icon ui-icon-trash"></span></a><% else %>&nbsp;<% end_if %></div>
	</li>	
	<% control Messages.Pagination %>
	<li class="$EvenOdd $FirstLast<% if Top.MoreThanOnePage %> pagination<% end_if %><% if Status = Unread %> unread<% end_if %>">
			<div class="message-status"><a href="{$Top.MessageLink}/{$ID}/<% if Status = Read %>markUnread<% else %>markRead<% end_if %>">$NiceStatus</a></div>
			<a class="open-message" href="{$Top.MessageLink}/{$ID}">
			<div class="message-subject">$Subject</div>
			<% if Top.Inbox %>
				<div class="message-from">$NiceFrom</div>
			<% else_if Top.Sent %>
				<div class="message-to">$NiceTo</div>
			<% else %>
				<div class="message-from">$NiceFrom</div>
			<% end_if %>
			<div class="message-date">$NiceDate</div>
			</a>
			<div class="message-actions"><a href="{$Top.MessageLink}/{$ID}/<% if IsTrash %>delete<% else %>trash<% end_if %>" class="action-trash"><span class="ui-icon ui-icon-trash"></span></a><% if IsTrash %><a href="{$Top.MessageLink}/{$ID}/restore" class="action-restore"><span class="ui-icon ui-icon-arrowreturnthick-1-w"></span></a><% end_if %></div>
		</li>
	<% end_control %>
</ul>
