<?php
	
	
	set_time_limit(0);
	
	$host = 'localhost';
	
	$user = 'Local5';
	
	$password = '208208';
	
	$db_name = 'tdrive';
	
	$db = mysqli_connect($host, $user, $password, $db_name);
	
	$dirs = glob('./csv/*');
	foreach($dirs as $dir){
		$files = glob($dir. '/*');
		foreach($files as $file){
			$f = fopen($file, 'r');
			$i = 0;
			while(!feof($f)){
				if(!$i){
					$i++;
					$ar = fgetcsv($f,650000, ';','"');//echo $ar[7];
					continue;
				}$i++;
				$ar = fgetcsv($f,650000, ';','"');//echo $ar[7];
				$attributes = unserialize($ar[7]);
				if(!is_array($attributes)){
					// echo $file, $i;
					continue;
				}
				foreach($attributes as $name=>$value){
					$res = mysqli_query($db, 'select count(*) from oc_attribute_description where name = "'.$name.'"');
					$exists = mysqli_fetch_row($res)[0];
					if(!$exists){
						mysqli_query($db, 'insert into oc_attribute values(null, 7,0)');
						echo mysqli_error($db);
						mysqli_query($db, 'insert into oc_attribute_description values(' . mysqli_insert_id($db) . ', 1, "'.$name.'")');
						if(mysqli_error($db)){
							p($name);
						}
					}
					
					// p('Существует ' . $name , 0);
				}
			}
			fclose($f);
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
					
					function save_img($url, $name){
						$path = 'catalog/vendors';
						file_exists($path) or mkdir($path,null,1);
						file_put_contents($path . '/' . $name, file_get_contents($url));
						return $path . '/' . $name;
						
					}
					
					function p($M,$die = 1){
						
						printf('<pre>%s</pre>',print_r($M,1));
						$die && die();
						ob_flush();
						flush();
					}				
					
					
					function translit($str){
						$str = preg_replace('~\s+~iUus',' ', mb_strtolower($str));
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
						":"=>"", ";"=>"","—"=>"", "–"=>"-"
						);
						return strtr($str,$tr);
					}																			