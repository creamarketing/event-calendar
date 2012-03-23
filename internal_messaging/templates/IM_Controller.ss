<div id="IM_Controller">
	<input type="hidden" id="IM_Controller_URL" value="$Link">
	<div class="top-controls">
		<div class="top-controls-left">
			<div class="top-controls-left-buttons">
				<button id="IM_Controller_NewMessageButton" type="button"><% _t('IM_Controller.NEWMESSAGEBUTTON', 'Write new message') %></button>
			</div>
			<div class="top-controls-right">
			</div>
			<div class="top-controls-search">
				<span class="top-controls-search-fields"></span>
				<span class="top-controls-search-input"><input type="text" id="SearchText" name="SearchText" value="<% _t('IM_Controller.SEARCH', 'Search') %>"></span>
				<span class="top-controls-search-reset"></span>
			</div>
		</div>
	</div>
	<div class="content">
		<div id="IM_Tabs">
			<ul>
				<li><a href="#IM_Tab_Inbox"><span class='messagebox-icon messagebox-icon-inbox'></span><% _t('IM_MessageBox.INBOX', 'Inbox') %><span class="unread"></span></a></li>
				<li><a href="#IM_Tab_Sentbox"><span class='messagebox-icon messagebox-icon-sentbox'></span><% _t('IM_MessageBox.SENTBOX', 'Sent') %><span class="unread"></span></a></li>
				<li><a href="#IM_Tab_Trashbox"><span class='messagebox-icon messagebox-icon-trashbox'></span><% _t('IM_MessageBox.TRASHBOX', 'Trash') %><span class="unread"></span></a></li>
			</ul>
			<div id="IM_Tab_Inbox">
			</div>
			<div id="IM_Tab_Sentbox">
			</div>
			<div id="IM_Tab_Trashbox">
			</div>
		</div>
	</div>
	<div class="bottom-controls">
		<div class="bottom-controls-left">
			<div id="IM_Controller_AjaxLoader">
				<img src="internal_messaging/images/ajax-loader.gif" widht="16px" height="16px" style="margin-top: 7px; float: left;">
				<div style="float: left; margin-left: 10px; line-height: 30px;"><% _t('IM_Controller.LOADING', 'Loading') %>...</div>		
			</div>		
			<div class="bottom-controls-pagination">
				<div id="IM_Inbox_Pagination">
				</div>
				<div id="IM_Sentbox_Pagination">
				</div>
				<div id="IM_Trashbox_Pagination">
				</div>
			</div>
			<div class="action-refresh">
				<span id="IM_Action_Refresh" class="ui-icon ui-icon-refresh"></span>
			</div>				
			<div class="bottom-controls-right"></div>
		</div>
	</div>
</div>