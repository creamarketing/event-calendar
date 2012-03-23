if(typeof(ss) == 'undefined' || typeof(ss.i18n) == 'undefined') {
  console.error('Class ss.i18n not defined');
} else {
  ss.i18n.addDictionary('en_US', {
   	'Event.REPEATAFTER_DAYS': 'days',		
	'Event.REPEATAFTER_WEEKS': 'weeks',		
	'Event.REPEATAFTER_MONTHS': 'months'
  }
  );
}