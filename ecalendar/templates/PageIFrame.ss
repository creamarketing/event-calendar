<!DOCTYPE html>

<html lang="$ContentLocale" class="inside-iframe">
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
			 @import url(themes/blackcandy/css/ie6.css);
			</style> 
		<![endif]-->
		
		$IFrameStyles
	</head>
<body>
	<div id="BgContainer">
		<div id="Container">	
			<div id="Layout">
				$Layout
			</div>
		</div>
	</div>
</body>
</html>