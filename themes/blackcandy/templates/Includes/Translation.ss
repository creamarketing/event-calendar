<% if Translations %>
	<ul>
		<% control Translations %>
			<li class="$Locale.RFC1766">
				<a <% if isCurrent %>class="current"<% end_if %> href="$Link" hreflang="$Locale.RFC1766" title="$Title">$Locale.Nice</a>
			</li>
		<% end_control %>
	</ul>
<% end_if %>