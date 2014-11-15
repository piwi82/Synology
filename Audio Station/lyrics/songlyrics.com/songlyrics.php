<?php

/**
 * Documentation : http://ukdl.synology.com/download/Document/DeveloperGuide/AS_Guide.pdf
 */

// Uncomment the following block to test the code

/*class Info{
public $id;
public function __construct(){}
public function addTrackInfoToList($artist,$song,$id,$partialLyrics){
	$this->id = $id;
}
public function addLyrics($lyrics,$id){
	var_dump($lyrics);
}}
$info = new Info;
$songLyrics = new SongLyrics;
$songLyrics->getLyricsList("2NE1","I am the Best",$info);
var_dump($songLyrics->getLyrics($info->id,$info));/**/

 
class SongLyrics{

const URL_SEARCH = 'http://www.songlyrics.com/index.php?section=search&searchW=%s%%2C+%s&submit=Search';
const REG_SEARCH = '#<div\s+class="serpresult">\s*<a(?:\s+[a-z]+="[^"]*")+><img(?:\s+[a-z]+="[^"]*")+ /></a>\s*<h3><a href="(?P<url>[^"]+)"(?:\s+[a-z]+="[^"]*")*>(?P<song>[^<]+) Lyrics</a></h3>\s*<div(?:\s+[a-z]+="[^"]*")+>\s*<p>by <a(?: [a-z]+="[^"]*")+>(?P<artist>[^<]+)</a> on album <a(?: [a-z]+="[^"]*")+>(?P<album>[^<]+)</a></p>\s*<p>(?P<lyrics>[^<]+)</p>\s*</div>\s*</div>#mis';
const URL_LYRICS = 'http://www.songlyrics.com/index.php?section=search&searchW=%s%%2C+%s&submit=Search';
const REG_LYRICS = '#<div\s+id="songLyricsDiv\-outer">\s*<p\s+id="songLyricsDiv"(?:\s+[a-z]+="[^"]*")*>\s*(?P<lyrics>.+)\s*</p>\s*</div>\s*<script\s+type="text/javascript">#mis';

public function __construct(){}

public function getLyricsList($artist,$title,$info){
	$url = sprintf(self::URL_SEARCH,self::sanitize($artist),self::sanitize($title));
	$searchResult = file_get_contents($url);
	if ($lyrics = preg_match(self::REG_SEARCH,$searchResult,$m)){
		$info->addTrackInfoToList($m['artist'],$m['song'],$m['url'],strip_tags($m['lyrics']));
		return 1;
	}
	return 0;
}

public function getLyrics($id,$info){
	$lyricsResult = file_get_contents($id);
	if ($lyrics = preg_match(self::REG_LYRICS,$lyricsResult,$m)){
		$info->addLyrics(strip_tags($m['lyrics']),$id);
		return true;
	}
	return false;
}

public static function sanitize($text){
	return urlencode(strtolower($text));
}

}

?>
