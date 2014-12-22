// This is where the magic happens, fuckers !
// Ben @ YUDU 2015

var rootDir = "http://localhost/ops-api/"
var adminKey, adminShared, createReaderFormData;
// check if keys have already been added
checkLogIn();

$(document).ready(function()
{

	// the controller for the main panels show/hide
	$(".one-third a").click(function(event)
	{
		// get the link target
		var target = event.target.className;
			target = target.substr(0,target.length-5);

			// hide all the others
			$(".two-thirds").each(function(event)
			{
				$(".message").hide();
				$(this).hide();
			});

				// show the particular one
				$("." + target).hide().fadeIn();
	});

	// the controller to get admin keys
	$("#submit_admin").click(function()
	{
	// may be blank, may be not
		var key = $("#key").val();
		var shared = $("#shared").val();
			// check if they are
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

	// controller to show the 'create' options
	$(".create a").click(function(event)
	{
		// get the link target
		var target = event.currentTarget.className;
			target = target.substr(12,target.length);
			$(".create_options").hide();
			$(".create_panel_" + target).hide().fadeIn();
	});

	// controller for the 'read' options
	$(".read a").click(function(event)
	{
		// get the link target
		var target = event.currentTarget.className;
			target = target.substr(10,target.length);
			$(".create_options").hide();
			$(".read_panel_" + target).hide().fadeIn();
	});

	//
	// TODO
	//

	// - add update viewcontroller
	// - add delete view controller

	// back button controller
	$(".back_button").click(function(event)
	{
		$(".panel").hide().fadeOut(function()
		{
			$(".create_options").fadeIn();
		});
	});

	//
	// event listeners and functions to parse form data
	//

	// create a new reader
	$("#submit_create_reader").click(function(event)
	{
		var emptyFields = 0;
		var numFields = 0;

		$(".create_panel_reader form input[type='text']").each(function(event)
		{
			numFields++;
		});

		$(".create_panel_reader form input[type='text']").each(function(event)
		{
			var tempValue = $(this).val();
				// fire a warning message if empty
				if (!tempValue)
				{
					emptyFields++;
					$(this).addClass("input_empty");
				}
				else
				{
					$(this).removeClass("input_empty");
				}
		});

		if (emptyFields > 0)
		{
			$(".prompts").html("<div class='warning'>These cannot be empty - There are " +emptyFields + " empty fields X</div>");
			attachListeners();
		}
		else
		{
			createReaderFormData = $(".create_panel_reader form").serialize();

			$(".create_panel_reader form").hide(function(){

				$(".create_panel_reader .response").html("<h4>Working...</h4>");

				$(".create_panel_reader .response").hide().fadeIn();

				$.ajax({
					url: rootDir + "php/module_create.php",
					type: "POST",
					data: {
						type		: "create_user", 
						key 		: adminKey,
						secret 		: adminShared, 
						formString	: createReaderFormData,
					},
					success: function(response)
					{
						console.log(response);
					}
				});
			});
		}

	});


});


// attaches an event listener to any newly created dom element
function attachListeners()
{
	$(".warning").click(function()
	{
		$(this).fadeOut(function()
		{
			$(this).hide();
		});
	});
};

// check if any details are already in localstorage
function checkLogIn()
{
	adminKey = localStorage.getItem("adminKey");
	adminShared = localStorage.getItem("adminShared");
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