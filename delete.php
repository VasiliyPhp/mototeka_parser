<?php
	
	
	/****
		*
		*   удаление всех данных обо всех товарах вмечсте с товарами
		*
		*
		*
	****/
	
	set_time_limit(0);
	
	$host = 'localhost';
	
	$user = 'Local5';
	
	$password = '208208';
	
	$db_name = 'tdrive';
	
	$db = mysqli_connect($host, $user, $password, $db_name);
	
	mysqli_query($db, "delete from oc_product");echo mysqli_error($db);
	mysqli_query($db, "delete from oc_product_attribute");echo mysqli_error($db);
	mysqli_query($db, "delete from oc_product_description");echo mysqli_error($db);
	mysqli_query($db, "delete from oc_product_image");echo mysqli_error($db);
	mysqli_query($db, "delete from oc_product_to_category");echo mysqli_error($db);
	mysqli_query($db, "delete from oc_product_to_layout");echo mysqli_error($db);
	mysqli_query($db, "delete from oc_product_to_store");echo mysqli_error($db);
	mysqli_query($db, "delete from oc_url_alias where query like 'product_id=%'");
	