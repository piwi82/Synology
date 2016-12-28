<?php

/**
 * Documentation : http://download.synology.com/download/Document/DeveloperGuide/DLM_Guide.pdf
 */

// Uncomment the following block to test the code
/*
header("Content-Type: text/plain");
class Plugin{
private $i = 0;
public function __call($name,$args){
switch ($name){
case 'addJsonResults':
	$args = json_decode($args[0],true);
case 'addResult':
case 'addRSSResults':
default:
	echo "#".($this->i ++)."\t";
	var_dump($args);
}}}
$curl = curl_init();
$t9 = new Torrent9;
$t9->prepare($curl,"the walking dead");
$return = curl_exec($curl);
curl_close($curl);
echo $t9->parse(new Plugin,$return),"\n";
/**/



/**
 * Torrent9
 */
class Torrent9{

private static $query;
private static $plugin;
private static $count = 0;
const PAGE_MAX = 5;
const PROTOCOL = "http";
const HOST = "www.torrent9.biz";	// 104.27.188.252 104.27.189.252 2400:cb00:2048:1::681b:bcfc 2400:cb00:2048:1::681b:bdfc
const URL = "%s://%s/search_torrent/%s/page-%s";
const USER_AGENT = "Mozilla/5.0 (Windows NT 6.1; WOW64; Trident/7.0; rv:11.0) like Gecko";
const REG_RESULT = '#<tr>\s*<td><i[^>]*></i>\s*<a[^>]* href="(?<page>[^"]+)[^>]*>(?<title>.+)</a></td>[^>]*<td[^>]*>(?<size>[^<]+)</td>\s*<td[^>]*><span class="seed_n?ok">(?<seed>[0-9]+).*</span></td>\s*<td[^>]*>(?<leech>[0-9]+).*</td>\s*</tr>#i';
const REG_DOWNLOAD_MATCH = '#^/torrent/(?<file>[^/]+)$#';
const REG_DOWNLOAD_REPLACE = '/get_torrent/${1}.torrent';
const REG_SIZE = '#^(?<size>[0-9]+\.[0-9]+)\s*(?<unit>[KMGTP]?o)$#i';
const SIZE_POWER_LIMIT = 2;
private static $size = array('o','ko','mo','go','to','po','eo','zo','yo');
const REG_PAGE = '#<a href="[^"]+page\-(?<page>[0-9]+)"><strong>Suiv</strong></a>#i';

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
				strip_tags($m['title']),	// title
				self::PROTOCOL."://".self::HOST.preg_replace(self::REG_DOWNLOAD_MATCH,self::REG_DOWNLOAD_REPLACE,$m['page']),	// download
				self::torrent9Size($m['size']),	// size
				"",	// datetime
				$m['page'],	// page
				self::$count,	// hash (must be unique, not mentioned in official documentation)
				(int) $m['seed'],	// seeds
				(int) $m['leech'],	// leechs
				"Torrent9"	// category
			);
			self::$count ++;
		}
	if (preg_match(self::REG_PAGE,$response,$m)>0){
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

private static function torrent9Size($size){
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
