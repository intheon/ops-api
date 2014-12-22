// magic fuckers

$(document).ready(function(){

	$(".one-third a").click(function(event){

			// get the link target

		var target = event.target.className;
			target = target.substr(0,target.length-5);

			// hide all the others
 
		$(".two-thirds").each(function(event){
			$(this).hide();
		});

			// show the particular one

		$("." + target).hide().fadeIn();

	})

});