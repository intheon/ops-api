// magic fuckers

$(document).ready(function(){

	// the controller for the main panels show/hide

	$(".one-third a").click(function(event){

			// get the link target

		var target = event.target.className;
			target = target.substr(0,target.length-5);

			// hide all the others
 
		$(".two-thirds").each(function(event){
			$(".message").hide();
			$(this).hide();
		});

			// show the particular one

		$("." + target).hide().fadeIn();

	});

	checkLogIn();

	// the controller to get admin keys

	$("#submit_admin").click(function(){
		var key = $("#key").val();
		var shared = $("#shared").val();

		if (!key || !shared)
		{
			$(".message").append("<div class='warning'>These cannot be empty X</div>")
			attachListeners();
		}
		else
		{
			localStorage.setItem("adminKey",key);
			localStorage.setItem("adminShared",shared);	
			checkLogIn();
		}
	});

});

function attachListeners(){
	$(".warning").click(function(){
		$(this).fadeOut(function(){
			$(this).hide();
		});
	});
};


function checkLogIn()
{
	var adminKey = localStorage.getItem("adminKey");
	var adminShared = localStorage.getItem("adminShared");

	if (!adminKey || !adminShared)
	{
		$("#signed_out").fadeIn();
	}
	else 
	{
		$("#signed_out").fadeOut();
		$("#signed_in").fadeIn();

		$("#api_output").append(adminKey);
		$("#shared_output").append(adminShared);
	}

}