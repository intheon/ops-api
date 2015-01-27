<?php

// this is the module used to CREATE something on YUDU's DB.

// ALL requests need an api key and shared secret

if (isset($_POST['key']) && isset($_POST['secret']))
{
	require_once "yuduapi.php";

	$key 	= $_POST['key'];
	$secret = $_POST['secret'];

	$yuduConfig['key'] = $key;
	$yuduConfig['sharedSecret'] = $secret;
	$yuduConfig['serviceUrl'] = "https://api.yudu.com/Yudu/services/1.0";
	$yuduConfig['debug'] = true;

	// what the hell are we actually creating...?
		if (isset($_POST['type']))
		{
			$type = $_POST['type'];



			// a user!
			if ($type == "create_user")
			{
				if (isset($_POST['formString']))
				{
					$arr = array();

					parse_str($_POST['formString'],$arr);

					foreach($arr as $key => $value)
					{
						// assign the nodeId
  					  	if ($key == "create_nodeid")
  					  	{
  					  		$yuduConfig['nodeId'] = $value;
  					  	}
					}

					// begin forming the request

					// Create a unique string for identifying a fresh new user.
					$uniqid = uniqid();

					$postData = "";
					$queryVariables = array();

					// Create XML representation of new reader
					$yuduNamespace = "http://schema.yudu.com";
					$dom = new DomDocument();

					$reader = $dom->createElementNS($yuduNamespace, "reader");
					$dom->appendChild($reader);

					$username = $dom->createElement("username");
					$username->appendChild($dom->createTextNode("example$uniqid"));
					$reader->appendChild($username);

					$emailAddress = $dom->createElement("emailAddress");
					$emailAddress->appendChild($dom->createTextNode("user$uniqid@example.com"));
					$reader->appendChild($emailAddress);

					$firstName = $dom->createElement("firstName");
					$firstName->appendChild($dom->createTextNode("firstname$uniqid"));
					$reader->appendChild($firstName);

					$lastName = $dom->createElement("lastName");
					$lastName->appendChild($dom->createTextNode("lastName$uniqid"));
					$reader->appendChild($lastName);

					$password = $dom->createElement("password");
					$password->appendChild($dom->createTextNode("password$uniqid"));
					$reader->appendChild($password);

					$nodeId = $dom->createElement("nodeId");
					$nodeId->appendChild($dom->createTextNode($yuduConfig['nodeId']));
					$reader->appendChild($nodeId);

					$queryVariables = array();
					$postData = $dom->saveXML();

					$readerUri = sendYuduRequest('POST', $yuduConfig['serviceUrl'] . '/readers/', $queryVariables, $postData, $yuduConfig);

					echo $readerUri;

				}
				else
				{
					echo "no data";
				}
			}




			// a subscription period!






			// a permission!
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