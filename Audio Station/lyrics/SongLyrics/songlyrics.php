<?php

/**
 * Audio Station lyrics module from SongLyrics.com
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

const SEARCH_URL = 'http://www.songlyrics.com/index.php?section=search&searchW=%s%%2C+%s&submit=Search';
const SEARCH_REG = '#<div\s+class="serpresult">\s*<a(?:\s+[a-z]+="[^"]*")+><img(?:\s+[a-z]+="[^"]*")+ /></a>\s*<h3><a href="(?P<url>[^"]+)"(?:\s+[a-z]+="[^"]*")*>(?P<song>[^<]+) Lyrics</a></h3>\s*<div(?:\s+[a-z]+="[^"]*")+>\s*<p>by <a(?: [a-z]+="[^"]*")+>(?P<artist>[^<]+)</a> on album <a(?: [a-z]+="[^"]*")+>(?P<album>[^<]+)</a></p>\s*<p>(?P<lyrics>[^<]+)</p>\s*</div>\s*</div>#mis';
const LYRICS_REG = '#<div\s+class="pagetitle">\s*<h1>(?P<artistSong>[^<]+)\s+Lyrics</h1>\s*<p>Artist:\s*<a(?:\s+[a-z]+="[^"]*")+>(?P<artist>[^<]+)</a></p>\s*<p>Album:\s*<a(?:\s+[a-z]+="[^"]*")+>(?P<album>[^<]+)</a></p>.+<div\s+id="songLyricsDiv\-outer">\s*<p\s+id="songLyricsDiv"(?:\s+[a-z]+="[^"]*")*>\s*(?P<lyrics>.+)\s*</p>\s*</div>\s*<script\s+type="text/javascript">#mis';
const LYRICS_PRINTF = "%s\n%s\n----------------\n\n%s";

public function __construct(){}

public function getLyricsList($artist,$title,$info){
	$url = sprintf(self::SEARCH_URL,self::sanitize($artist),self::sanitize($title));
	$searchResult = file_get_contents($url);
	if ($lyrics = preg_match(self::SEARCH_REG,$searchResult,$m)){
		$info->addTrackInfoToList($m['artist'],$m['song'],$m['url'],strip_tags($m['lyrics']));
		return 1;
	}
	return 0;
}

public function getLyrics($id,$info){
	$lyricsResult = file_get_contents($id);
	if ($lyrics = preg_match(self::LYRICS_REG,$lyricsResult,$m)){
		$info->addLyrics(sprintf(self::LYRICS_PRINTF,$m['artistSong'],$m['album'],html_entity_decode(strip_tags($m['lyrics']))),$id);
		return true;
	}
	return false;
}

public static function sanitize($text){
	return urlencode(strtolower($text));
}

}

?>
