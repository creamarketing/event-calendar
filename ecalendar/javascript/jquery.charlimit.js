(function($){  
 $.fn.charlimit = function(override_options) {  
 
 	var defaults = {
		limit: 200,
		id_result: false,
		alertClass: false
	}
 
 	var options = $.extend(defaults, override_options);
 
    return this.each(function() {  
 
	var	charLimit = options.limit;
	
	if(options.id_result != false)
	{
		$("#"+options.id_result).append('(' + $(this).val().length + ' / ' + charLimit + ')');
	}
	
	$(this).keyup(function(){
		if($(this).val().length > charLimit){
		$(this).val($(this).val().substr(0, charLimit));
		}
		
		if(options.id_result != false)
		{
			var currentChars = $(this).val().length;
			var charsLeft = charLimit - currentChars;
			$("#"+options.id_result).html('(' + currentChars + ' / ' + charLimit + ')');
		
			if(charsLeft <= 10)
			{
				$("#"+options.id_result).addClass(options.alertClass);
			}
			else
			{
				$("#"+options.id_result).removeClass(options.alertClass);
			}
		}
	});
 
});  
 };  
})(jQuery);