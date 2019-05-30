<?php
	//echo (int)"hello world";
	//echo (string)(time() * rand(1000000,9999999));
	//echo (int)(time() * rand(1000000,9999999));
	//echo rand(1000000,9999999);
	//echo (int)9999999999;
	//echo (time() * rand(1000,9999));
	//echo (time() * 10000);
	//echo (time() * 9999);
	//echo base64_encode(((string)(time() * rand(1000,9999))) . ((string)(rand(100000000,999999999))));
	echo (string)(strtotime("+1 day")) . "<br />" . (int)(strtotime("+1 day"));
?>