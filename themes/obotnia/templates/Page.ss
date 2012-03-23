<!DOCTYPE html>

<html lang="$ContentLocale">
  <head>
		<% base_tag %>
		<title><% if MetaTitle %>$MetaTitle<% else %>$Title<% end_if %> &raquo; $SiteConfig.Title</title>
		$MetaTags(false)
		<link rel="shortcut icon" href="/favicon.ico" />
		
		<% require themedCSS(layout) %> 
		<% require themedCSS(typography) %> 
		<% require themedCSS(form) %> 
		
		<!--[if IE 6]>
			<style type="text/css">
			 @import url(themes/obotnia/css/ie6.css);
			</style> 
		<![endif]-->
		
		<!--[if IE 7]>
			<style type="text/css">
			 @import url(themes/obotnia/css/ie7.css);
			</style> 
		<![endif]-->
		
		<!--[if IE 8]>
			<style type="text/css">
			 @import url(themes/obotnia/css/ie8.css);
			</style> 
		<![endif]-->	
		
		<!--[if lte IE 8]>
			<style type="text/css">
			 @import url(themes/obotnia/css/ie68.css);
			</style>
		<![endif]-->		
		
		$ExtraFrameStyles
		
		<script type="text/javascript">

		  var _gaq = _gaq || [];
		  _gaq.push(['_setAccount', 'UA-28630431-2']);
		  _gaq.push(['_setDomainName', 'none']); 
		  _gaq.push(['_setAllowLinker', true]); 
		  _gaq.push(['_setAllowHash', false]); 
		  _gaq.push(['_trackPageview']);

		  (function() {
			var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
			ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
			var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
		  })();

		</script>
	</head>
<body>
	<div id="BgContainer">
		<div id="BgLeft"></div>
		<div id="BgRight"></div>		
		<div id="Container">
			<div id="Header">
				$SearchForm
				<% if UseDefaultLayout %>
					<div style="width: 100%; min-height: 100px; position: relative">
						<a href="$BaseHref?clear_theme=1"><img src="{$themeDir}/images/banner_{$Locale}.jpg" class="topSplashImage"/></a>
					</div>
				<% else %>
				<div style="width: 100%; min-height: 100px; position: relative">
					<% if UseCustomHeader %>
						<% if HeaderLeftImage %>
							<% control HeaderLeftImage.SetHeight(100) %>
								<img src="$AbsoluteURL" alt="" id="HeaderLeftImage"/>
							<% end_control %>
						<% end_if %>
					<% else %>
						<img src="$themeDir/images/liitto_logo.png" alt="" id="LiittoLogo"/>
					<% end_if %>
					<div class="title-wrapper">
						<% if CustomTitle %>
							<h1>$CustomTitle</h1>			
						<% else %>
							<h1>$SiteConfig.Title</h1>
						<% end_if %>
						<p><a href="$BaseHref?clear_theme=1" style="text-decoration: none; color: #000;">$SiteConfig.Tagline</a></p>
					</div>
					<% if UseCustomHeader %>
						<% if HeaderRightImage %>
							<% control HeaderRightImage.SetHeight(100) %>
								<img src="$AbsoluteURL" alt="" id="HeaderRightImage"/>
							<% end_control %>
						<% end_if %>					
					<% else %>
						<img src="$themeDir/images/kb_logo.png" alt="" id="KustLogo"/>
					<% end_if %>
				</div>
				<% end_if %>
				<div id="Translation">
					<% include Translation %>
				</div>				
			</div>
		
			<div id="Navigation">
				<% include Navigation %>
		  	</div>

			<% if ShowSubNavigation %>
			<div id="SubNavigation">
				<% include SubNavigation %>
		  	</div>			
			<% end_if %>
	  	
		  	<div class="clear"><!-- --></div>
		
			<div id="Layout">
			  $Layout
			</div>
		
		   <div class="clear"><!-- --></div>
		</div>
		<div id="FooterFix"></div>
		<div id="Footer">
			<% include EventReporting %>
			<% include Footer %>
		</div> 
	</div>
</body>
</html>