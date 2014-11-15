<?php

/**
 * Download Station search module from CPasBien.pe
 * Documentation : http://download.synology.com/download/ds/userguide/DLM_Guide.pdf
 */

// Uncomment the following block to test the code

header("Content-Type: text/plain; charset=utf-8");
class Plugin{
private $i = 0;
public function __call($name,$args){
switch ($name){
case 'addJsonResults':
	$args = json_decode($args[0],true);
case 'addResult':
case 'addRSSResults':
default:
	var_dump($args);
}}}
$curl = curl_init();
$cpb = new CPasBien;
$cpb->prepare($curl,"hannibal");
$return = curl_exec($curl);
curl_close($curl);
echo $cpb->parse(new Plugin,$return),"\n";
/**/

class CPasBien{

private static $query;
private static $plugin;
private static $count = 0;
const PAGE_MAX = 10;
const PROTOCOL = "http";
const HOST = "www.cpasbien.pe";
const URL = "%s://%s/recherche/%s/page-%s";
const USER_AGENT = "Mozilla/5.0 (Windows NT 6.1; WOW64; Trident/7.0; rv:11.0) like Gecko";
const REG_RESULT = '#<a[^\r\n]*? href="(?P<page>[^"]+)" title="(?P<category>[^\r\n]+)<br/>[^<]+<br/>(?P<date>(?:[0-9]{2}/){2}[0-9]{4})">\s*<img src="[^"]+" ?/>\s*(?P<title>[^\s][^\r\n]+?[^\s])\s*</a>\s*</td>\s*<td[^>]*>(?P<size>[^<]+)</td>\s*<td[^>]*><img src="[^"]+" ?/>\s*<span[^>]*>(?P<seeds>[^<]+)</span></td>\s*<td[^>]*><img src="[^"]+" ?/>(?P<leechs>[^<]+)</td>#is';
const REG_DOWNLOAD_MATCH = '#^(?P<host>http://[^/]+/).*?(?P<file>[^/]+)\.html$#';
const REG_DOWNLOAD_REPLACE = '${host}_torrents/${file}.torrent';
const REG_SIZE = '#^(?P<size>[0-9]+\.[0-9]+)\s*(?P<unit>[KMGTP]?o)$#i';
const SIZE_POWER_LIMIT = 2;
private static $size = array('o','ko','mo','go','to','po','eo','zo','yo');
const REG_DATETIME_MATCH = '#^(?P<day>[0-9]{2})/(?P<month>[0-9]{2})/(?P<year>[0-9]{4})$#';
const REG_DATETIME_REPLACE = '${year}-${month}-${day} 00:00:00';
const REG_PAGE = '#<a href="[^"]+page\-(?P<page>[0-9]+)">.+?</a>\s*?</div>#';

public function __construct(){}

public function prepare($curl,$query){
	self::$query = preg_replace('#\s+#','+',$query);
	$this->_prepare($curl,0);
}
private function _prepare($curl,$page){
	curl_setopt($curl,CURLOPT_USERAGENT,self::USER_AGENT);
	curl_setopt($curl,CURLOPT_URL,sprintf(self::URL,self::PROTOCOL,self::HOST,self::$query,$page));
	curl_setopt($curl,CURLOPT_RETURNTRANSFER,true);
	curl_setopt($curl,CURLOPT_TIMEOUT,30);
}

public function parse($plugin,$response){
	self::$plugin = $plugin;
	$this->_parse($response);
	return self::$count;
}
private function _parse($response){
	if (preg_match_all(self::REG_RESULT,$response,$match,PREG_SET_ORDER)>0)
		foreach ($match as $m){
			self::$plugin->addResult(
				trim($m['title']),	// title
				preg_replace(self::REG_DOWNLOAD_MATCH,self::REG_DOWNLOAD_REPLACE,trim($m['page'])),	// download
				$this->cpasbienSize(trim($m['size'])),	// size
				preg_replace(self::REG_DATETIME_MATCH,self::REG_DATETIME_REPLACE,trim($m['datetime'])),	// datetime
				trim($m['page']),	// page
				self::$count,	// hash (must be unique, not mentioned in official documentation)
				(int) $m['seeds'],	// seeds
				(int) $m['leechs'],	// leechs
				trim($m['category'])	// category
			);
			self::$count ++;
		}
	if (preg_match_all(self::REG_PAGE,$response,$m,PREG_SET_ORDER)>0){
		if ($m['page']>=self::PAGE_MAX)
			return self::$count;
		$curl = curl_init();
		$this->_prepare($curl,$m['page']);
		$response = curl_exec($curl);
		curl_close($curl);
		return $this->_parse($response);
	}
	return self::$count;
}

private function cpasbienSize($size){
	if (preg_match(self::REG_SIZE,strtolower($size),$m)!==1)
		return 0;
	$unit = array_search($m['unit'],self::$size);
	if ($unit===false)
		return 0;
	if ($unit<=self::SIZE_POWER_LIMIT)
		return (int) round($m['size']*pow(2,10*$unit));
	return round(
		$m['size']*pow(2,10*self::SIZE_POWER_LIMIT)*pow(1.024,$unit-self::SIZE_POWER_LIMIT)
	).str_repeat("000",$unit-self::SIZE_POWER_LIMIT);
}

}

?>
