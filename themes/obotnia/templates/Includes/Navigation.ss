<ul>
 	<% control Menu(1) %>	  
		<% if SpecialLink %>
			<li><a href="$SpecialLink" title="Go to the $Title.XML page" class="$LinkingMode"><span>$MenuTitle.XML</span></a></li>
		<% else %>
			<li><a href="$Link" title="Go to the $Title.XML page" class="$LinkingMode"><span>$MenuTitle.XML</span></a></li>
		<% end_if %>
   	<% end_control %>
</ul>