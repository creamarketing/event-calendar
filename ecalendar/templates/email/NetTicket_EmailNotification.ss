<h3><% _t('NetTicket_EmailNotification.HELLO', 'Hello NetTicket') %>,</h3>

<p><% _t('NetTicket_EmailNotification.MSG', 'The user that created the following event has requested to be contacted by you to discuss ticket sales.') %></p>

<h3><% _t('AssociationOrganizer.SINGULARNAME', 'User') %></h3>
<div>
	<p>
		<strong><% _t('AssociationOrganizer.NAME', 'Name') %>: </strong>$Organizer.FullName<br/>
		<strong><% _t('AssociationOrganizer.PHONE', 'Phone') %>: </strong>$Organizer.Phone<br/>
		<strong><% _t('AssociationOrganizer.EMAIL', 'Email') %>: </strong>$Organizer.Email
	</p>
</div>

<h3><% _t('Association.SINGULARNAME', 'Organizer') %></h3>
<div>
	<p>
		<strong><% _t('Association.NAME', 'Name') %>: </strong>$Association.Name<br/>
		<strong><% _t('Event.ADDRESS', 'Address') %>: </strong>$Association.PostalAddress, $Association.PostalCode, $Association.PostalOffice<br/>
		<strong><% _t('Association.PHONE', 'Phone') %>: </strong>$Association.Phone<br/>
		<strong><% _t('Association.EMAIL', 'Email') %>: </strong>$Association.Email<br/>
		<strong><% _t('Association.HOMEPAGE', 'Homepage') %>: </strong><a href="$Association.Homepage">$Association.Homepage</a>
	</p>
</div>

<h3><% _t('Event.SINGULARNAME', 'Event') %></h3>
<div>
	<h4>$Event.Title</h4>
	<strong>$Event.EventTextShort</strong>
	<p>$Event.EventText</p>
	<p>
		<strong><% _t('Event.PERIOD', 'Period') %>: </strong>$Event.PeriodNice<br/>
		<strong><% _t('Event.PLACE', 'Place') %>: </strong>$Event.Place<br/>
		<strong><% _t('Event.ADDRESS', 'Address') %>: </strong>$Event.GoogleMAP<br/>
		<strong><% _t('Event.HOMEPAGE', 'Homepage') %>: </strong><a href="$Event.Homepage">$Event.Homepage</a><br/>
		<strong><% _t('Event.PRICETEXT', 'Price information: ') %>: </strong>$Event.PriceText
	</p>
	<span><% sprintf(_t('NetTicket_EmailNotification.CLICKHERE', 'Click <a href="%s">here</a> to see more information about the event.'),$Event.Link) %></span>
</div>

<br/>
<br/>
<% _t('NetTicket_EmailNotification.FOOTER', 'Greetings,<br/>Ostrobothnia event calendar') %>