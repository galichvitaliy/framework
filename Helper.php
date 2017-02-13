<?php namespace Mirage;

class Helper {

	static public function checkDir($dir = false)
	{
		if(is_dir($dir)) {
			if(!is_writable($dir)) {
				exit("'$dir' must be writable!");
			}
		} else {
			mkdir($dir, 0777, true);
		}
	}

	static public function delTree($dir)
	{
		$files = array_diff(scandir($dir), array('.','..'));
		foreach ($files as $file) {
			(is_dir("$dir/$file")) ? self::delTree("$dir/$file") : unlink("$dir/$file");
		}
		return rmdir($dir);
	}


	static public function translit($string)
	{
		$charlist = array(
			"А"=>"A","Б"=>"B","В"=>"V","Г"=>"G",
			"Д"=>"D","Е"=>"E","Ж"=>"J","З"=>"Z","И"=>"I",
			"Й"=>"Y","К"=>"K","Л"=>"L","М"=>"M","Н"=>"N",
			"О"=>"O","П"=>"P","Р"=>"R","С"=>"S","Т"=>"T",
			"У"=>"U","Ф"=>"F","Х"=>"H","Ц"=>"TS","Ч"=>"CH",
			"Ш"=>"SH","Щ"=>"SCH","Ъ"=>"","Ы"=>"YI","Ь"=>"",
			"Э"=>"E","Ю"=>"YU","Я"=>"YA","а"=>"a","б"=>"b",
			"в"=>"v","г"=>"g","д"=>"d","е"=>"e","ж"=>"j",
			"з"=>"z","и"=>"i","й"=>"y","к"=>"k","л"=>"l",
			"м"=>"m","н"=>"n","о"=>"o","п"=>"p","р"=>"r",
			"с"=>"s","т"=>"t","у"=>"u","ф"=>"f","х"=>"h",
			"ц"=>"ts","ч"=>"ch","ш"=>"sh","щ"=>"sch","ъ"=>"y",
			"ы"=>"yi","ь"=>"","э"=>"e","ю"=>"yu","я"=>"ya"," "=>"_"
		);
		return strtr($string,$charlist);
	}

	static public function recursiveFilename($path, $filename, $ext, $i=false)
	{

		$add = $i ? "_".$i : "";

		if(file_exists($path.$filename.$add.".".$ext)) {
			$i ? $i++ : $i=1;
			return self::recursiveFilename($path, $filename, $ext, $i);
		} else {
			return $filename.$add.".".$ext;
		}
	}

	static public function base_encode($num, $alphabet = "23456789abcdefghijkmnpqrstuvwxyz")
	{
		$base_count = strlen($alphabet);
		$encoded = '';
		while ($num >= $base_count) {
			$div = $num/$base_count;
			$mod = ($num-($base_count*intval($div)));
			$encoded = $alphabet[$mod] . $encoded;
			$num = intval($div);
		}

		if ($num) $encoded = $alphabet[$num] . $encoded;

		return $encoded;
	}

	static public function uniqHash($table, $field="hash")
	{

		$hash = self::base_encode(mt_rand(10000000, 99999999));
		$exist = DB::getCell("SELECT 1 FROM $table WHERE $field=:field LIMIT 1", [':field' => $hash]);

		if($exist) {
			self::uniqHash($table, $field);
		} else {
			return $hash;
		}

	}

	static public function paginator($sql, $klvo = 10, $pnum = 9, $bind_params = [], $max_limit = false)
	{
		$page   = HTTP::$page;
		$layout = $klvo * ($page - 1);

		$sql_num = preg_replace('|SELECT(.*?)FROM|s', "SELECT 1 FROM", $sql);
		$sql_num = preg_replace('|ORDER BY(.*?)$|s', "", $sql_num);
		if ($max_limit && is_numeric($max_limit)) {
			$sql_num .= " LIMIT 0, $max_limit";
		}

		$sql	= str_replace(";","",$sql);
		$sql	= preg_replace('/LIMIT(.*?)$/Uis', "", $sql);
		$sql	.= " LIMIT $layout, $klvo;";

		$rows = DB::getCol($sql_num, $bind_params);
		$num_rows = sizeof($rows);
		unset($rows);

		$pages	= ceil($num_rows/$klvo)+1;

		$url_params = explode("?", $_SERVER['REQUEST_URI']);
		$url = preg_replace('/page-(.*?)$/Uis', "", $url_params[0]);
		if( strpos($url, '/', strlen($url)-1) ) {
			$url = substr($url, 0, -1);
		}

		$pos = floor($pnum/2);	//Get pages num before and after current page
		if($page <= $pos) {		//if curent page in the begining, crop last page by pages number
			$start_page = 1;
			$end_page = $pages < $pnum ? $pages : $pnum;
		} else {
			$start_page = $page - $pos;
			$end_page = $pages < ($start_page + $pnum) ? $pages : $start_page + $pnum;
		}

		$tpl = App::get('view');
		$tpl->assign('page_items', $num_rows);
		$tpl->assign('page_from', $layout+1);
		$tpl->assign('page_to', $layout+$klvo>=$num_rows ? $num_rows : $layout+$klvo);

		if($pages > 2) {
			$tpl->assign('page_url', $url);
			$tpl->assign('page_url_get', !empty($url_params[1]) ? "?" . $url_params[1] : "");
			$tpl->assign('start_page', $start_page);
			$tpl->assign('end_page', $end_page);
			$tpl->assign('page', $page);
			$tpl->assign('pages', 1);
			$tpl->assign('pages_num', $pages);
		}

		return $sql;
	}

	static public function formatBytes($bytes, $precision = 2)
	{
		$units = array('б', 'Кб', 'Мб', 'Гб', 'Тб');

		$bytes = max($bytes, 0);
		$pow = floor(($bytes ? log($bytes) : 0) / log(1024));
		$pow = min($pow, count($units) - 1);

		// Uncomment one of the following alternatives
		$bytes /= pow(1024, $pow);
		// $bytes /= (1 << (10 * $pow));

		return round($bytes, $precision) . ' ' . $units[$pow];
	}


	// Функция предназначена для вывода численных результатов с учетом
	// склонения слов, например: "1 ответ", "2 ответа", "13 ответов" и т.д.
	//
	// $digit — целое число
	// можно вместе с форматированием, например "<b>6</b>"
	//
	// $expr — массив, например: array("ответ", "ответа", "ответов").
	// можно указывать только первые 2 элемента, например для склонения английских слов
	// (в таком случае первый элемент - единственное число, второй - множественное)
	//
	// $expr может быть задан также в виде строки: "ответ ответа ответов", причем слова разделены
	// символом "пробел"
	//
	// $onlyword - если true, то выводит только слово, без числа;
	// необязательный параметр
	static public function declension($digit, $expr, $onlyword = false)
	{
		if (!is_array($expr)) $expr = array_filter(explode(' ', $expr));
		if (empty($expr[2])) $expr[2] = $expr[1];
		$i = preg_replace('/[^0-9]+/s','',$digit)%100; //intval не всегда корректно работает
		if ($onlyword) $digit='';
		if ($i>=5 && $i<=20) {
			$res=$digit.' '.$expr[2];
		} else {
			$i%=10;
			if ($i==1) $res=$digit.' '.$expr[0];
			elseif ($i>=2 && $i<=4) $res=$digit.' '.$expr[1];
			else $res=$digit.' '.$expr[2];
		}
		return trim($res);
	}

	static function convertFilesArray($files) {
		$new_files_array = [];
		foreach ($files as $name=>$file) {
			foreach ($file as $key=>$val) {
				$new_files_array[$key][$name] = $val;

			}
		}
		return $new_files_array;
	}


} 