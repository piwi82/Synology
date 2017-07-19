<?php

/**
 * Torrent9 BitTorrent search module for Synology Download Station
 * Documentation : http://download.synology.com/download/Document/DeveloperGuide/DLM_Guide.pdf
 * Base URL : http://www.torrent9.cc/search_torrent/the-walking-dead.html
 * Page URL : http://www.torrent9.cc/torrent/the+walking+dead/page-1 (n = p-1)
 * Download : http://www.torrent9.cc/get_torrent/the-walking-dead-s07e08-proper-vostfr-hdtv.torrent
 */
class Torrent9{

private static $query;
private static $plugin;
private static $count = 0;
const PROTOCOL = 'http';
const HOST = 'www.torrent9.cc';
const URL = '%s://%s/search_torrent/%s/page-%s';
const USER_AGENT = 'Mozilla/5.0 (Windows NT 6.1; WOW64; Trident/7.0; rv:11.0) like Gecko';
const REG_RESULT = '#<tr>\s*<td><i[^>]*></i>\s*<a[^>]* href="(?<page>[^"]+)[^>]*>(?<title>.+)</a></td>[^>]*<td[^>]*>(?<size>[^<]+)</td>\s*<td[^>]*><span class="seed_n?ok">(?<seed>[0-9]+).*</span></td>\s*<td[^>]*>(?<leech>[0-9]+).*</td>\s*</tr>#i';
const REG_DOWNLOAD_MATCH = '#^/torrent/(?<file>[^/]+)$#';
const REG_DOWNLOAD_REPLACE = '/get_torrent/${1}.torrent';
const REG_SIZE = '#^(?<size>[0-9]+\.[0-9]+)\s*(?<unit>[KMGTP]?o)$#i';
const SIZE_POWER_LIMIT = 2;
const SIZE = ['o','ko','mo','go','to','po','eo','zo','yo'];
const REG_PAGE = '#<a href="[^"]+page\-(?<page>[0-9]+)"><strong>Suiv</strong></a>#i';

public function __construct(){}

/**
 * Prepare HTTP request
 */
public function prepare($curl,$query){
	self::$query = preg_replace('#\s+#','+',trim($query));
	$this->_prepare($curl,0);
}
private function _prepare($curl,$page){
	curl_setopt($curl,CURLOPT_USERAGENT,self::USER_AGENT);
	curl_setopt($curl,CURLOPT_URL,sprintf(self::URL,self::PROTOCOL,self::HOST,self::$query,$page));
	curl_setopt($curl,CURLOPT_RETURNTRANSFER,true);
	curl_setopt($curl,CURLOPT_TIMEOUT,30);
	curl_setopt($curl,CURLOPT_FOLLOWLOCATION,true);
}

/**
 * Parse HTTP response body
 */
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
				"",	// datetime (not provided in Torrent9 search results)
				$m['page'],	// page
				self::$count,	// hash (must be unique, not mentioned in official documentation)
				(int) $m['seed'],	// seeds
				(int) $m['leech'],	// leechs
				"Torrent9"	// category
			);
			self::$count ++;
		}
	if (preg_match(self::REG_PAGE,$response,$m)>0){
		$curl = curl_init();
		$this->_prepare($curl,$m['page']);
		$response = curl_exec($curl);
		curl_close($curl);
		return $this->_parse($response);
	}
	return self::$count;
}

/**
 * Converts Torrent9 size to byte size
 */
private static function torrent9Size($size){
	if (preg_match(self::REG_SIZE,strtolower($size),$m)!==1)
		return 0;
	$unit = array_search(strtolower($m['unit']),self::SIZE);
	return $unit===false ? 0 : round(bcmul($m['size'],bcpow(2,10*$unit)));
}

}

?>
