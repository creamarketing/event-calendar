<div class="file_heading">
	<% if Multi %>
		<% if IsImagesOnly %>
			<% _t('Uploadify.ATTACHEDIMAGES','Attached images') %>
		<% else %>
			<% _t('Uploadify.ATTACHEDFILES','Attached files') %>
		<% end_if %>	
	<% else %>
		<% if IsImagesOnly %>
			<% _t('Uploadify.ATTACHEDIMAGE','Attached image') %>
		<% else %>
			<% _t('Uploadify.ATTACHEDFILE','Attached file') %>
		<% end_if %>
	<% end_if %>
</div>
<div class="upload_previews">
	<% if Files %>
	<ul class="<% if Sortable %>sortable {'url' : '$Link(dosort)'}<% end_if %>">
		<% control Files %>
			<li id="file-{$ID}" class="uploadifyFile clr">
				<div class="image"><img src="$Thumb" width="32" height="32" alt="$Thumb" /></div>
				<div class="filename">$Name</div>
				<div class="delete">
					<% if Top.Backend %>
						<a class="remove" title="<% _t('Uploadify.REMOVE','Remove') %>" rel="$ID" title="<% _t('Uploadify.REMOVEANDDELETE','Remove') %>" href="<% control Top %>$Link(removefile)<% end_control %>"><img src="uploadify/images/remove.png" height="16" width="16" alt="<% _t('Uploadify.REMOVEANDDELETE','Remove') %>" /></a>&nbsp;
						<a class="delete" title="<% _t('Uploadify.REMOVEANDDELETE','Remove and delete') %>" rel="$ID" title="<% _t('Uploadify.REMOVEANDDELETE','Remove and delete') %>" href="<% control Top %>$Link(deletefile)<% end_control %>"><img src="uploadify/images/delete.png" height="16" width="16" alt="<% _t('Uploadify.REMOVEANDDELETE','Remove and delete') %>" /></a>
					<% else %>
						<a class="delete" title="<% _t('Uploadify.REMOVE','Remove') %>" rel="$ID" title="<% _t('Uploadify.REMOVEANDDELETE','Remove') %>" href="<% control Top %>$Link(removefile)<% end_control %>"><img src="uploadify/images/delete.png" height="16" width="16" alt="<% _t('Uploadify.REMOVEANDDELETE','Remove') %>" /></a>
					<% end_if %>
				</div>
			</li>
		<% end_control %>
	</ul>
	<% else %>
	<div class="no_files">
		<% if Multi %>
			<% if IsImagesOnly %>
				<% _t('Uploadify.NOIMAGES','No images attached') %>
			<% else %>
				<% _t('Uploadify.NOFILES','No files attached') %>
			<% end_if %>
		<% else %>
			<% if IsImagesOnly %>
				<% _t('Uploadify.NOIMAGE','No image attached') %>
			<% else %>
				<% _t('Uploadify.NOFILE','No file attached') %>
			<% end_if %>
		<% end_if %>
	</div>
	<% end_if %>
</div>

<div class="inputs">
	<% if Files %>
		<% control Files %>
			<input type="hidden" name="<% if Top.Multi %>{$Top.Name}[]<% else %>{$Top.Name}ID<% end_if %>" value="$ID" />
		<% end_control %>
	<% end_if %>
</div>