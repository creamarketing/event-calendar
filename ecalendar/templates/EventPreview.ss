<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<style>
		html {
			overflow-x: hidden;
		}
		
		body {
			font-family: Arial, Helvetica, sans-serif;
			font-size: 14px;
			background-color: #fff;
		}
		
		.page {
			padding: 0;
		}		

		/* For show event */

		#ShowEvent .images, 
		#ShowEvent .description,
		#ShowEvent .description-short {
			width: 100%;
		}
		
		#ShowEvent .description-short {
			font-weight: bold;
			font-size: 1.1em;
		}

		#ShowEven .middleWrapper {
			width: 100%;
			height: auto;
			position: relative;
		}

		#ShowEvent .left {
			float: left;
			width: 53%;
		}

		#ShowEvent .right {
			float: right;
			width: 45%;
			text-align: left;
		}		
		
		#ShowEvent h4 {
			border-bottom: 1px solid #ccc;
			margin: 5px 0px 5px 0;
			font-size: 1.5em;
		}
		
		#ShowEvent h5 {
			font-size: 1.2em;
			margin: 10px 0px 6px 0;
		}
		
		#ShowEvent a {
			text-decoration: none;
		}
		
		#ShowEvent ul {
			margin-left: 15px;
			padding-left: 0px;
		}

		#ShowEvent li {
			margin-left: 5px;
			font-size: 0.9em;
		}
		
		#ShowEvent .middleWrapper {
			height: auto;
		}

	</style>

	<title><% _t("EventPage.TITLE","Event") %></title>
</head>
	
<body>
	
<div id="ShowEvent" class="page">
<% control Event %>
<h4>$Title</h4>
<div class="images">
	<% control Images %>
		<img src="$URL" style="margin-right: 5px;"/>
	<% end_control %>
</div>
<br/>

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
				<% if Name %>$Name<br/><% end_if %>
				<% if PostalAddress %>$PostalAddress<% if PostalCode %>, $PostalCode<% end_if %><% if PostalOffice %>, $PostalOffice<% end_if %><br/><br/><% end_if %>

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
		<% if Homepage %>
		<div class="event-homepage">
			<h5><% _t('EventPage.EVENTHOMEPAGE', 'Event homepage') %></h5>
			<a href="$Homepage" target="_blank">$Homepage</a>
		</div>
		<% end_if %>
		<% if hasAttachments %>				
			<div class="event-linksandfiles">
				<h5><% _t('EventPage.EVENTATTACHMENTS', 'Attachments') %></h5>
				<ul>
				<% if Links %>					
					<% control Links %>
					<li><a href="$Link" target="_blank">$Name</a></li>
					<% end_control %>
				<% end_if %>	

				<% if Files %>
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
				$Date.Format(d.m.Y) <% _t('EventPage.CLOCK', '') %> $StartTime.Format(H:i)<% if HasEndTime %> - $EndTime.Format(H:i)<% end_if %>
			<% end_control %>
		</div>		
		<div class="event-location">
			<h5><% _t('EventPage.EVENTLOCATION', 'Event location') %></h5>
			<% if Place %>$Place<br/><% end_if %>
			<% if GoogleMAP %>$GoogleMAP<br/><br/><% end_if %>
			<% if ShowGoogleMap %><div id="GoogleMapsContainer" style="margin-top: 10px; width: 280px; height: 200px; background-image: url(//maps.googleapis.com/maps/api/staticmap?markers={$GoogleMAPEncoded}&size=280x200&maptype=roadmap&sensor=false);">Google maps</div><% end_if %>
		</div>	
		<% if OtherDates %>
		<div class="event-dates header">
			<h5><% _t('EventPage.EVENTDATES', 'Dates') %></h5>
			<% control Dates %>
				<% if First %>
				<% else %>
					$Date.Format(d.m.Y) <% _t('EventPage.CLOCK', '') %> $StartTime.Format(H:i)<% if HasEndTime %> - $EndTime.Format(H:i)<% end_if %>
					<% if Last = 0 %>
						<br/>
					<% end_if %>
				<% end_if %>
			<% end_control %>			
		</div>
		<% end_if %>
	</div>
	<div style="clear: both">&nbsp;</div>
</div>
<% end_control %>
</div>

</body>
</html>