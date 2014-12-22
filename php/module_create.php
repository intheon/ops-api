<?php

// this is the module used to CREATE something on YUDU's DB.

// ALL requests need an api key and shared secret

if (isset($_POST['key']) && isset($_POST['secret']))
{
	$key 	= $_POST['key'];
	$secret = $_POST['secret'];

	// what the hell are we actually creating
		if (isset($_POST['type']))
		{
			$type = $_POST['type'];

			if ($type == "create_user")
			{
				if (isset($_POST['formString']))
				{
					$arr = array();

					parse_str($_POST['formString'],$arr);

					echo print_r($arr);
				}
				else
				{
					echo "no data";
				}
			}
		}
		else
		{
			echo "no type";
		}
}
else
{
	echo "no credentials";
}

?>