<% if nothingFound = 1 %>
	No hits
<% else %>
	<% control Event %>
	<div class="header-wrapper">
		<h4>$Title</h4>
		<div class="action-links">
			<a target="_blank" href="{$Top.AbsoluteLink}showEvent/$ID?pdf=1" class="pdf-download-link">
				<span><% _t('EventPage.SAVEASPDF', 'PDF') %></span>
				<img src="{$themeDir}/images/icons/page_white_acrobat.png"/>
			</a>
			<a class="addthis_button" href="http://www.addthis.com/bookmark.php?v=250&amp;pubid=xa-4ed5daf242d9c4e9">
				<img src="{$themeDir}/images/lg-share-<% _t('Locale.SHORTCODE', 'en') %>.gif" width="125" height="16" alt="Bookmark and Share" style="border:0"/>
			</a>
		</div>
	</div>
	<div class="images">
		<% control Images %>
			<img src="$URL" alt=""/>
		<% end_control %>
	</div>
		
	<div class="middleWrapper">
		<div class="left">
			<div class="description">
				<h5>$EventTextShort</h5>
				$EventText
			</div>
			<br/>
			<% if PriceText %>
			<div class="price">
				<h5><% _t('EventPage.PRICEINFORMATION', 'Price information') %></h5>
				$PriceText
			</div>
			<br />
			<% end_if %>
			<div class="organizer">
				<h5><% _t('EventPage.ORGANIZER', 'Organizer') %></h5>
				<% if AssociationLogo %>
					<% control AssociationLogo %>
						<img src="$URL"/><br/><br/>
					<% end_control %>
				<% end_if %>				
				<% control Association %>
					$Name<br/>
					$PostalAddress<% if PostalCode %>, $PostalCode<% end_if %><% if PostalOffice %>, $PostalOffice<% end_if %><br/><br/>

					<% if Phone %>
						<span style="display: inline-block; min-width: 70px; margin-right: 10px;"><% _t('EventPage.PHONE', 'Phone') %></span>$Phone<br/>
					<% end_if %>
					<% if Email %>
						<span style="display: inline-block; min-width: 70px; margin-right: 10px;"><% _t('EventPage.EMAIL', 'Email') %></span><a href="mailto:$Email">$Email</a><br/>
					<% end_if %>
					<% if Homepage %>
						<span style="display: inline-block; min-width: 70px; margin-right: 10px;"><% _t('EventPage.HOMEPAGE', 'Homepage') %></span><a href="$Homepage" target="_blank">$Homepage</a><br/>
					<% end_if %>
				<% end_control %>
			</div>
			<br/>

			<% if hasAttachments %>	
			<br />
			<div class="event-linksandfiles">
				<h5><% _t('EventPage.EVENTATTACHMENTS', 'Attachments') %></h5>
				<ul>
				<% if Links %>
					<!-- <li class="AttachmentType"><% _t('EventPage.EVENTLINKS', 'Links') %></li> -->
					<% control Links %>
					<li><a href="$Link" target="_blank">$Title</a></li>
					<% end_control %>
				<% end_if %>
				<% if Files %>
					<!-- <li class="AttachmentType"><% _t('EventPage.EVENTFILES', 'Files') %></li> -->
					<% control Files %>
					<li><a href="$Link" target="_blank">$Title</a></li>
					<% end_control %>
				<% end_if %>
				</ul>
			</div>				
			<% end_if %>
		</div>
		<div class="right">
			<div class="event-closest-date">
				<h5><% _t('EventPage.EVENTCLOSESTDATE', 'Closest date') %></h5>
				<% control Dates.First %>
					$Start.Format(d.m.Y) <% _t('EventPage.CLOCK', '') %> $Start.Format(H:i)<% if HasEndTime %> - $End.Format(H:i)<% end_if %>
				<% end_control %>
			</div>	
			<br/>
			<% if Homepage %>
			<div class="event-homepage">
			<h5><% _t('EventPage.EVENTHOMEPAGE', 'Event homepage') %></h5>
			<a href="$Homepage" target="_blank">$Homepage</a>
			</div>
			<br/>
			 <% end_if %>			
			<div class="event-location">
				<h5><% _t('EventPage.EVENTLOCATION', 'Event location') %></h5>
				<% if Place %>$Place<br /><% end_if %>
				$PostalAddress<% if PostalCode %>, $PostalCode<% end_if %><% if PostalOffice %>, $PostalOffice<% end_if %><br/><br/>
				<% if ShowGoogleMap %><div id="GoogleMapsContainer" style="width: 300px; height: 200px; display: none">Google maps</div><% end_if %>
			</div>
			<br/>		
		
			<% if OtherDates %>
			<div class="event-dates">				
				<h5><% _t('EventPage.EVENTDATES', 'Dates') %></h5>
				<% control Dates %>
					<% if First %>
					<% else %>
						$Start.Format(d.m.Y) <% _t('EventPage.CLOCK', '') %> $Start.Format(H:i)<% if HasEndTime %> - $End.Format(H:i)<% end_if %>
						<% if Last = 0 %>
							<br/>
						<% end_if %>
					<% end_if %>
				<% end_control %>	
				<% if MoreDates.Count %>
				<div class="event-show-more-dates"><a href="#"><% _t('EventPage.SHOWMORE', 'Show more') %></a></div>
				<span class="event-more-dates">
					<% control MoreDates %>
						<% if First %>
							<br/>
						<% else %>
							$Start.Format(d.m.Y) <% _t('EventPage.CLOCK', '') %> $Start.Format(H:i)<% if HasEndTime %> - $End.Format(H:i)<% end_if %>
							<% if Last = 0 %>
								<br/>
							<% end_if %>
						<% end_if %>
					<% end_control %>
				</span>
				<div class="event-hide-more-dates"><a href="#"><% _t('EventPage.SHOWLESS', 'Show less') %></a></div>
				<% end_if %>
			</div>
			<% end_if %>
		</div>
		<div class="clear">&nbsp;</div>
	</div>
	<% end_control %>
<% end_if %>
