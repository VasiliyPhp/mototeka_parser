<?php
	
	
	set_time_limit(0);
	
	$host = 'localhost';
	
	$user = 'Local5';
	
	$password = '208208';
	
	$db_name = 'tdrive';
	
	$db = mysqli_connect($host, $user, $password, $db_name);
	
	$dirs = glob('./csv/*');
	foreach($dirs as $key=>$dir){
		if($key < 3){
			continue;
		}
		$files = glob($dir. '/*');
		foreach($files as $key=>$file){
			if($key < 4){
				continue;
			}
			$f = fopen($file, 'r');
			$i = 0;
			while(!feof($f)){
				if(!$i){
					$i++;
					$ar = fgetcsv($f,650000, ';','"');//echo $ar[7];
					continue;
				}
				$i++;
				$row = fgetcsv($f,650000, ';','"');
				if(!is_array($row)){
					p('не арэу ' . gettype($row),0);
					continue;
				}
				$ar = array_map('trim',$row);//echo $ar[7];
				$attributes = unserialize($ar[7]);
				$images = unserialize($ar[5]);
				$imgs = [];
				foreach($images as $key=>$image){
					$imgs[] = save_img($image, translit($ar[3]) . "_$key.jpg", $ar[0] . ' ' . $ar[1] );
				}
				$m_image = save_img($ar[4], translit($ar[3]) . '.jpg', $ar[0] . ' ' . $ar[1]);
				$vendor  = $ar[6];
				$price   = $ar[8];
				
				$res = mysqli_query($db, "select manufacturer_id from oc_manufacturer where name='$vendor'");
				$manufacturer_id = mysqli_fetch_row($res)[0] ? : 0;
				
				$weight  = isset($attributes['Вес (кг)']) ? (int)$attributes['Вес (кг)'] : 0;
				$model   = isset($attributes['Модель']) ? mysqli_real_escape_string($db, $attributes['Модель']) : '';
				$name    = $ar[3];
				$mysql_name = mysqli_real_escape_string($db,$name);
				
				/* проверка на существование товара */
				$res = mysqli_query($db, "select count(name) from oc_product_description where name = '$mysql_name' ");
				if(mysqli_fetch_row($res)[0]){
					p("<b color='#f00'>$ar[3]</b> already exists",0);
					continue;
				}
				
				/* запись товара */
				$query = "insert into oc_product values(null,'$model', '', '', '', '', '', '', '',
				1,7,'$m_image',$manufacturer_id,1,0,$price,0,0,now(),$weight,1,0,0,0,1,1,1,1,1,1,now(),now())";
				$res = mysqli_query($db, $query);
				p("<b color='#0f0'>$ar[3]</b> has been saved",0);
				if(mysqli_errno($db)){
					echo $query . ' ' . __LINE__ . ' ' .mysqli_error($db);
					die;
				}
				$id = mysqli_insert_id($db);
				$res = mysqli_query($db, "insert into oc_product_to_store values($id,0)");
				$res = mysqli_query($db, "insert into oc_product_to_layout values($id,0,0)");
				
				/* проверка на существование категори ....*/
				$res = mysqli_query($db, "select category_id from oc_category_description where name='$ar[1]'");
				if ( !($cat_id = mysqli_fetch_row($res)[0]) ) {
					// p($cat_id. ' not exists child', 0);
					$res = mysqli_query($db, "select category_id from oc_category_description where name='$ar[0]'");
					if(!$cat_id = mysqli_fetch_row($res)[0]){
						
						$res = mysqli_query($db, "select category_id from oc_category_description where name='$ar[0]-$ar[1]'");
						if(!$cat_id = mysqli_fetch_row($res)[0]){
							
							/* категории нет, создаем ее */
							$res = mysqli_query($db, "insert into oc_category values(null,'',92,'',0,'fa_non','no_image.png',1,1,0,1,now(),now()) ");
							$cat_id = mysqli_insert_id($db);
							if(!$cat_id){
								p($ar[1] . ', '.$ar[0] .' - КАТЕГОРИЯ НЕ СОХРАНИЛАСЬ', 0);
							}
							else{
								p($ar[1] . ', '.$ar[0] .' - новая категория', 0);
								mysqli_query($db, "insert into oc_category_description values($cat_id, 1, '$ar[0]-$ar[1]', '', '$ar[0]-$ar[1]', '$ar[0]-$ar[1]', '$ar[0]-$ar[1] купить в интернете', '$ar[0]-$ar[1] купить в интернете')");
								mysqli_query($db, "insert into oc_category_to_store values($cat_id,0)");
								mysqli_query($db, "insert into oc_category_to_layout values($cat_id,0,0)");
								mysqli_query($db, "insert into oc_category_path values($cat_id, $cat_id, 1)");
								mysqli_query($db, "insert into oc_category_path values($cat_id, 92, 0)");
								mysqli_query($db, "insert into oc_url_alias values(null, 'category_id=$cat_id', '".translit($ar[0].'-'.$ar[1])."')");
							}
						}
					}
				}
				if($cat_id){
					$res = mysqli_query($db, "insert into oc_product_to_category values($id,$cat_id,0)");
					if(mysqli_errno($db)){
						echo __LINE__ . ' ' .mysqli_error($db);
						die;
					}
				}
				
				// приписываем alias пути
				mysqli_query($db, "insert into oc_url_alias values(null, 'product_id=$id', '".translit($name)."')");
				if(mysqli_errno($db)){
					echo __LINE__ . ' ' .mysqli_error($db);
					die;
				}
				$res = mysqli_query($db, "insert into oc_product_description values($id,1,'$mysql_name','".
				mysqli_real_escape_string($db, preg_replace('~(<a[^>]+>.+</a>|<img[^>]+>)~Uu','',$ar[2]))."','','$mysql_name','$mysql_name','$mysql_name купить в интернете','$mysql_name купить в интернете')");
				
				if(mysqli_errno($db)){
					echo __LINE__ . ' ' .mysqli_error($db);
					die;
				}
				
				foreach($imgs as $img){
					$res = mysqli_query($db, "insert into oc_product_image values(null,$id,'$img',0,'')");
				}
				
				foreach($attributes as $attr=>$attr_value){
					$attr_value = mysqli_real_escape_string($db, $attr_value);
					if($attr == 'Вес (кг)'){
						// p("пропускаем атрибут $attr", 0);
						continue;
					}
					$res = mysqli_query($db, "select attribute_id from oc_attribute_description where name='$attr' limit 1");
					if(mysqli_errno($db)){
						echo $query . ' ' . __LINE__ . ' ' .mysqli_error($db);
						die;
					}
					if($res && ($attr_id = mysqli_fetch_row($res)[0])){
						$query =  "insert into oc_product_attribute values($id,$attr_id,1,'$attr_value')";
						$res = mysqli_query($db,$query);
						if(mysqli_errno($db)){
							echo __LINE__ . ' ' .mysqli_error($db);
							die;
						}
					}
					else{
						p($attr . ' not exists',0);
					}
				}
			}
			fclose($f);
			// die;
		}
	}
	
	
	// $vendors = array_filter($vendors);
	// p($vendors);
	die;
	foreach($vendors as $vendor){
		
		
		$img = $vendor[2] ? save_img($vendor[2], basename($vendor[2])) : '';
		$query = sprintf('insert into oc_manufacturer values(null,"%s","%s","%s")', $vendor[0], $img, 0);
		$res = mysqli_query($db, $query);
		if(!$res){
			p('1  ' . mysqli_error($db));
		}
		p('вставили ' . $vendor[0],0);
		$id = mysqli_insert_id($db);
		$query = sprintf('insert into oc_manufacturer_description values(%s,1,"%s","%s","%s","%s","%s","%s")',
		$id, $vendor[0], mysqli_real_escape_string($db, $vendor[1]),$vendor[0],null,null,null);
		$res = mysqli_query($db, $query);
		if(!$res){
			p('2  ' . mysqli_error($db));
		}
		$query = 'insert into oc_manufacturer_to_store values(' . $id . ',0)';
		$res = mysqli_query($db, $query);
		if(!$res){
			p('3  ' . mysqli_error($db));
		}
		
		
	}
	
	function save_img($url, $name, $cat){
		$path = 'catalog/goods/' .translit($cat);
		file_exists($path) or mkdir($path,null,1);
		$filename = $path . '/' . $name;
		file_exists($filename) || file_put_contents($filename, file_get_contents($url));
		return $filename;
	}
	
	function p($M,$die = 1){
		
		printf('<pre>%s</pre>',print_r($M,1));
		$die && die();
		ob_flush();
		flush();
	}				
	
	
	function translit($str){
		$str = preg_replace('~\s+~iUus',' ', trim(mb_strtolower($str)));
		$tr = array(
		"А"=>"a", "Б"=>"b", "В"=>"v", "Г"=>"g", "Д"=>"d",
		"Е"=>"e", "Ё"=>"yo", "Ж"=>"zh", "З"=>"z", "И"=>"i", 
		"Й"=>"j", "К"=>"k", "Л"=>"l", "М"=>"m", "Н"=>"n", 
		"О"=>"o", "П"=>"p", "Р"=>"r", "С"=>"s", "Т"=>"t", 
		"У"=>"u", "Ф"=>"f", "Х"=>"kh", "Ц"=>"ts", "Ч"=>"ch", 
		"Ш"=>"sh", "Щ"=>"sch", "Ъ"=>"", "Ы"=>"y", "Ь"=>"", 
		"Э"=>"e", "Ю"=>"yu", "Я"=>"ya", "а"=>"a", "б"=>"b", 
		"в"=>"v", "г"=>"g", "д"=>"d", "е"=>"e", "ё"=>"yo", 
		"ж"=>"zh", "з"=>"z", "и"=>"i", "й"=>"j", "к"=>"k", 
		"л"=>"l", "м"=>"m", "н"=>"n", "о"=>"o", "п"=>"p", 
		"р"=>"r", "с"=>"s", "т"=>"t", "у"=>"u", "ф"=>"f", 
		"х"=>"kh", "ц"=>"ts", "ч"=>"ch", "ш"=>"sh", "щ"=>"sch", 
		"ъ"=>"", "ы"=>"y", "ь"=>"", "э"=>"e", "ю"=>"yu", 
		"я"=>"ya", " "=>"-", "."=>"", ","=>"", "/"=>"-",  
		":"=>"", ";"=>"", "|"=>"", "`"=>"", "\""=>"", "'"=>"", "—"=>"", "("=>"_", ")"=>"_", "["=>"_", "]"=>"_"
		);
		$str = strtr($str,$tr);
		return preg_replace(['~-+~','~[-_]+$~','~^[-_]+~'],['-','',''],$str);
	}																																																																												