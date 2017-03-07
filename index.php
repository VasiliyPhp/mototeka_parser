<meta charset=utf-8 >
<?php
	
	ini_set('display_errors',1);
	error_reporting(E_ALL);
	set_time_limit(0);
	
	// загрузить через composer codepockr/phpQyery
	require '/vendor/autoload.php';
	
	$properties = ['cat','s_cat','desc','name','img','images','manufacturer','props','price'];
	$stop_file = 'start';
	touch($stop_file);
	
	
	const SITE = 'http://mototeka.su';
	$cookie_file = (__DIR__ . '/cookie_' . md5(time()) . '.txt');	
	
	register_shutdown_function(function(){
		echo 'stop eba';
		global $cookie_file, $stop_file;
		file_exists($cookie_file) && unlink($cookie_file);
		file_exists($stop_file) && unlink($stop_file);
		
	});
	try{
		
		$doc = phpQuery::newDocument(curl(SITE . '/catalog/'));
		$tmp = iconv('cp1251', 'utf-8',$doc);
		
		$cats = pq('.catalog-section');
		$cat_ = [];
		foreach($cats as $cat){
			$ar = [];
			$name = pq($cat)->find('.catalog-section-title a')->text();
			$childs = pq($cat)->find('.catalog-section-child a');
			$ar['cat'] = $name;
			foreach($childs as $child){
				
				$link = SITE . pq($child)->attr('href');
				$s_name = pq($child)->find('.text')->text();
				$image = SITE . pq($child)->find('img')->attr('src');
				// $img = save_img($image, iconv('cp1251','utf-8',$s_name), 'категории', iconv('cp1251','utf-8',$name));
				$img = save_img($image, $s_name, 'категории', $name);
				$ar['s_cat'] = $s_name;
				$ar['s_cat_img'] = $img;
				continue;
				parse_good_list($link, $ar);
				
			}
		}
		$doc->unloadDocument();
		
		
		
		
	}
	catch(Exception $e){
		p('Файл <b>' . $e->getFile() . '</b>', 0);
		p('Строка <b>' . $e->getLine() . '</b>', 0);
		p('Ошибка <b>' . $e->getMessage() . '</b>', 0);
		p('Стек трассировки <b>' .  $e->getTraceAsString(). '</b>', 0);
	}
	
	function parse_good_list($link, $ar){
		p($link,0);
		$doc = phpQuery::newDocument(curl($link));
		
		$tmp = pq('#catalog a.item-title');
		
		foreach($tmp as $g_l){
			$good_link = SITE . pq($g_l)->attr('href');
			$doc2 = phpQuery::newDocument(curl($good_link));
			
			$ar['desc'] = iconv('cp1251','utf-8', trim(pq('.description')->html()));
			$ar['name'] = (trim(pq('.body_text>h1')->text()));
			$ar['img'] = SITE . pq('.detail_picture .catalog-detail-images')->attr('href');
			$images = pq('.more_photo .catalog-detail-images');
			$_img = [];
			if(count($images)){
				foreach($images as $img){
					$_img[] = SITE . pq($img)->attr('href');
				}
			}
			$ar['images'] = serialize($_img);
			$props = pq('.catalog-detail-property');
			$_prop = [];
			foreach($props as $prop){
				$tmp_name = trim(pq('.name',$prop)->text());
				if($tmp_name == "Производитель"){
					$ar['manufacturer'] = trim(pq('.val',$prop)->text());
					continue;
				}
				$_prop[trim(pq('.name',$prop)->text())] = (pq('.val',$prop)->text());
			}
			$ar['props'] = serialize($_prop);
			$ar['price'] = trim(pq('.catalog-detail-price [itemprop=price]')->attr('content'));
			save_csv($ar);
			// p($ar);
			$doc2->unloadDocument();
		}
		if(count($next_page = pq('.pagination_ms .active',$doc)->next()->not('.last'))){
			$next_page = SITE . pq('a', $next_page)->attr('href');
			$doc->unloadDocument();
			
			parse_good_list($next_page, $ar);
		}
		else{
			p('нет следущей ссылки',0);
			$doc->unloadDocument();
		}
		
		
	}
	
	
	function save_csv($ar){
		global $properties,$stop_file;
		file_exists($stop_file) || p('Вызвана остановка');
		$path = 'csv/' . translit($ar['cat']);
		file_exists($path) or mkdir($path,null,1);
		$path .= '/';
		$csv = $path . translit($ar['s_cat']) . '.csv';
		$exists = file_exists($csv);
		$f = fopen($csv, 'a');
		if(!$exists){
			fputcsv($f, ($properties), ';','"');
			// fputcsv($f, ($prop2), ';','"');
		}
		$result = [];
		foreach($properties as $prop){
			// echo $value;
			$result[] = isset($ar[$prop])? $ar[$prop] : '';
		}
		fputcsv($f, $result, ';','"');
	}
	
	
	function save_img($url, $name, $cat, $subcut = null){
		$path = 'images/'.translit($cat );
		$path .= $subcut?  '/' . translit($subcut ) : '';
		file_exists($path) or mkdir($path,null,1);
		$name = translit($name);
		file_put_contents($path . '/' . $name . '.jpg', file_get_contents($url));
		return $path . '/' . $name . '.jpg';
		
	}
	
	function curl ($url){
		global $cookie_file;
		
		$ch = curl_init($url);
		
		
		
		curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie_file);
		curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie_file);
		
		// curl_setopt($ch, CURLOPT_HEADER, true);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_MAXREDIRS, 3);
		curl_setopt($ch,CURLOPT_ENCODING,'gzip,deflate');
		curl_setopt($ch, CURLOPT_HTTPHEADER, [
		// 'Accept:text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
		'Accept-Encoding:gzip, deflate, sdch',
		// 'Accept-Language:ru,en-US;q=0.8,en;q=0.6',
		// 'Cache-Control:max-age=0',
		// 'Connection:keep-alive',
		// 'Cookie:PHPSESSID=54517f45edeebe2aa1105617a7d0eb80; ALTASIB_SITETYPE=original; _ym_uid=1487770247474786456; BX_USER_ID=f56519b68a9fad68d64107859d50a280; WhiteCallback_shownOn=onshow; WhiteCallback_noShowWindow=1; WhiteCallback_noShowOnExit=1; _ym_isad=1; BITRIX_SM_sort=sort; BITRIX_SM_order=asc; BITRIX_SM_limit=12; BITRIX_SM_view=table; BITRIX_CONVERSION_CONTEXT_s1=%7B%22ID%22%3A1%2C%22EXPIRE%22%3A1488574740%2C%22UNIQUE%22%3A%5B%22conversion_visit_day%22%5D%7D; _ga=GA1.2.873273508.1487770247; _ym_visorc_30695403=w; WhiteCallback_visitorId=250654453; WhiteCallback_visit=393098121; BITRIX_SM_GUEST_ID=660385; BITRIX_SM_LAST_VISIT=03.03.2017+07%3A32%3A38; BITRIX_SM_ALTASIB_LAST_IP=94.180.119.184; BITRIX_SM_ALTASIB_GEOBASE=%7B%22ID%22%3A%222012%22%2C%22BLOCK_BEGIN%22%3A%221588858880%22%2C%22BLOCK_END%22%3A%221588887551%22%2C%22BLOCK_ADDR%22%3A%2294.180.16.0+-+94.180.127.255%22%2C%22COUNTRY_CODE%22%3A%22RU%22%2C%22CITY_ID%22%3A%222012%22%2C%22CITY_NAME%22%3A%22%D0%9D%D0%BE%D0%B2%D0%BE%D1%81%D0%B8%D0%B1%D0%B8%D1%80%D1%81%D0%BA%22%2C%22REGION_NAME%22%3A%22%D0%9D%D0%BE%D0%B2%D0%BE%D1%81%D0%B8%D0%B1%D0%B8%D1%80%D1%81%D0%BA%D0%B0%D1%8F+%D0%BE%D0%B1%D0%BB%D0%B0%D1%81%D1%82%D1%8C%22%2C%22COUNTY_NAME%22%3A%22%D0%A1%D0%B8%D0%B1%D0%B8%D1%80%D1%81%D0%BA%D0%B8%D0%B9+%D1%84%D0%B5%D0%B4%D0%B5%D1%80%D0%B0%D0%BB%D1%8C%D0%BD%D1%8B%D0%B9+%D0%BE%D0%BA%D1%80%D1%83%D0%B3%22%2C%22BREADTH_CITY%22%3A%2255.03923%22%2C%22LONGITUDE_CITY%22%3A%2282.927818%22%7D; BITRIX_SM_ALTASIB_GEOBASE_COUNTRY=%7B%22country%22%3A%22RU%22%7D; BITRIX_SM_SALE_UID=6392016; BITRIX_SM_ALTASIB_GEOBASE_RDR=Y; WhiteCallback_openedpage_HjWxu=1488516159; WhiteCallback_timeAll=96957; WhiteCallback_timePage=1236',
		// 'Host:mototeka.su',
		// 'Upgrade-Insecure-Requests:1',
		// 'User-Agent:Mozilla/5.0 (Windows NT 6.2; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/56.0.2924.87 Safari/537.36',
		
		]);
		curl_setopt($ch, CURLINFO_HEADER_OUT,1);
		$response = curl_exec($ch);
		$information = curl_getinfo($ch,CURLINFO_HEADER_OUT);
		
		// fclose($cookie_file);
		
		// p($information,0);
		if($curl = curl_error($ch)){
			throw new Exception($curl);
		}
		return $response;
		return iconv('cp-1251','utf-8',$response);
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