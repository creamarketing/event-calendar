<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<style>
		<% if DocumentType = pdf %>
			@page {
				size: A4 portrait;
				margin: 2cm 1cm;

				@top-left {
					content: element(header);
				}

				@bottom-left {
					content: element(footer);
				}
			}
			#pagenumber:before {
				content: counter(page);
			}
			#pagecount:before {
				content: counter(pages);
			}
			#header {
				position: running(header);
			}
			#footer {
				position: running(footer);
			}
			.page {
				page-break-before: always;
			}
		<% else %>
			.page {
				padding: 0;
			}
		<% end_if %>

		body {
			font-family: Arial, Helvetica, sans-serif;
			font-size: 14px;
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
			font-size: 1.5em;
		}
		
		#ShowEvent h5 {
			font-size: 1.2em;
		}
		
		#ShowEvent a {
			text-decoration: none;
		}
		
		#ShowEvent ul {
			margin-left: 0px;

		}

		#ShowEvent li {
			margin-left: 5px;
			font-size: 0.9em;
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
					<li><a href="$Link" target="_blank">$Title</a></li>
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
		<div class="event-location">
			<h5><% _t('EventPage.EVENTLOCATION', 'Event location') %></h5>
			$Place<br/>
			$PostalAddress<% if PostalCode %>, $PostalCode<% end_if %><% if PostalOffice %>, $PostalOffice<% end_if %><br/><br/>
			<% if ShowGoogleMap %><div id="GoogleMapsContainer" style="margin-top: 10px; width: 300px; height: 200px; background-image: url(http://maps.googleapis.com/maps/api/staticmap?markers={$GA_MarkerAddressEncoded}&size=300x200&maptype=roadmap&sensor=false);">Google maps</div><% end_if %>
		</div>
		<div class="event-closest-date">
			<h5><% _t('EventPage.EVENTCLOSESTDATE', 'Closest date') %></h5>
			<% control Dates.First %>
				$Start.Format(d.m.Y) <% _t('EventPage.CLOCK', '') %> $Start.Format(H:i)<% if HasEndTime %> - $End.Format(H:i)<% end_if %>
			<% end_control %>
		</div>	
		<% if OtherDates %>
		<div class="event-dates header">
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
		</div>
		<% end_if %>		
	</div>
</div>
<% end_control %>
</div>

</body>
</html>