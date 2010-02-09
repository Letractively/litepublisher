<?php
/**
 * Lite Publisher 
 * Copyright (C) 2010 Vladimir Yushko http://litepublisher.com/
 * Dual licensed under the MIT (mit.txt) 
 * and GPL (gpl.txt) licenses.
**/

class tkeywordsevents extends tkeywordsplugin {
 
 public static function instance() {
  return getinstance(__class__);
 }
 
 public function parseref($url) {
if (strbegin($url, '/admin/') || strbegin($url, '/croncron.php')) return;
  $ref=  isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
  if (empty($ref)) return;
  $urlarray = parse_url($ref);
  if ( $urlarray['scheme'] !== 'http' )  return;
  $host = $urlarray['host'];
  if (($host == 'search.msn.com')  || is_int(strpos($host, '.google.'))) {
   parse_str($urlarray['query']);
   $keywords=$q;
   if (isset($ie) && ($ie == 'windows-1251')) {
    $keywords= @iconv("windows-1251", "utf-8", $keywords);
   }
  } elseif ($host == 'www.rambler.ru') {
   parse_str($urlarray['query']);
   $keywords= @iconv("windows-1251", "utf-8", $words);
  } elseif ($host == 'www.yandex.ru') {
   parse_str($urlarray['query']);
   $keywords = $text;
  } else {
return;
}

  $keywords = trim($keywords);
  if (empty($keywords)) return;
  
  $c = substr_count($keywords, chr(208));
  if (($c < 3) && $this->hasru($keywords)) {
   $keywords= @iconv('windows-1251', 'utf-8', $keywords);
  }
  
  $keywords = trim($keywords);
  if (empty($keywords)) return;
  
  $keywords = htmlspecialchars($keywords, ENT_QUOTES);

  $link =" <a href=\"http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]\">$keywords</a>";
  if (in_array($link, $this->links)) return;
  $this->links[] = $link;
  $this->save();
 }
 
private function hasru($s) {
 return preg_match('/[�-��-�]{1,}/', $s);
 }
 
}//class

?>