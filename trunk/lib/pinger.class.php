<?php
/**
* Lite Publisher
* Copyright (C) 2010 Vladimir Yushko http://litepublisher.com/
* Dual licensed under the MIT (mit.txt)
* and GPL (gpl.txt) licenses.
**/

class tpinger extends tevents {
  
  public static function instance() {
    return getinstance(__class__);
  }
  
  protected function create() {
    global $paths;
    parent::create();
    $this->basename = 'pinger';
    $this->data['services'] = '';
    $this->data['enabled'] = true;
    require_once($paths['libinclude'] . 'class-IXR.php');
  }
  
  public function install() {
    global $paths;
    if (dbversion) {
      $dir = $paths['data'] . 'pingedlinks';
      @mkdir($dir, 0777);
      @chmod($dir, 0777);
    }
    if ($this->services == '') $this->services = file_get_contents($paths['libinclude'] . 'pingservices.txt');
    $posts = tposts::instance();
    $posts->lock();
    if (dbversion) $posts->deleted = $this->postdeleted;
    $posts->singlecron = $this->pingpost;
    $posts->unlock();
  }
  
  public function setenabled($value) {
    if ($value != $this->enabled) {
      $this->lock();
      $this->data['enabled'] = $value;
      if ($value) {
        $this->install();
      } else {
        tposts::unsub($this);
      }
      $this->unlock();
    }
  }
  
  public function setservices($s) {
    if ($this->services != $s) {
      $this->data['services'] = $s;
      $this->save();
    }
  }
  
  public function postdeleted($id) {
    global $paths;
    tfiler::deletemask($paths['data'] . 'pingedlinks' . DIRECTORY_SEPARATOR . "$id.*");
  }
  
  public function pingpost($id) {
    $post = tpost::instance($id);
    if ($post->status != 'published') return;
    $posturl = $post->link;
    $this->pingservices($posturl);
    
    $pinged = new tpinglinks    ($id);
    $links = $this->getlinks($post);
    foreach ($links as $link) {
      if (!in_array($link, $pinged->items)) {
        if ($this->ping($link, $posturl)) $pinged->items[] = $link;
      }
    }
    $pinged->save();
  }
  
  protected function getlinks(tpost $post) {
    $posturl = $post->link;
    $result = array();
    $punc = '.:?\-';
    $any = '\w/#~:.?+=&%@!\-' . $punc;
    
  preg_match_all("{\b http : [$any] +? (?= [$punc] * [^$any] | $)}x", $post->content, $links);
    foreach ($links[0] as $link) {
      if (in_array($link, $result)) continue;
      if ($link == $posturl) continue;
      $parts = parse_url($link);
      if ( empty($parts['query']) && (empty($parts['path']) ||($parts['path'] == '/')) ) continue;
      $result[] = $link;
    }
    return $result;
  }
  
  protected function ping($link, $posturl) {
    global $options;
    if ($ping = self::discover($link)) {
      $client = new IXR_Client($ping);
      $client->timeout = 3;
      $client->useragent .= " -- Lite Publisher/$options->version";
      $client->debug = false;
      
      if ( $client->query('pingback.ping', $posturl, $link) || ( isset($client->error->code) && 48 == $client->error->code ) ) return true;
    }
    return false;
  }
  
  public static function discover($url, $timeout_bytes = 2048) {
    global $options;
    
    $byte_count = 0;
    $contents = '';
    $headers = '';
    $pingback_str_dquote = 'rel="pingback"';
    $pingback_str_squote = 'rel=\'pingback\'';
    $x_pingback_str = 'x-pingback: ';
    $pingback_href_original_pos = 27;
    
    extract(parse_url($url), EXTR_SKIP);
    
    if ( !isset($host) ) // Not an URL. This should never happen.
    return false;
    
    $path  = ( !isset($path) ) ? '/'          : $path;
    $path .= ( isset($query) ) ? '?' . $query : '';
    $port  = ( isset($port)  ) ? $port        : 80;
    
    // Try to connect to the server at $host
    $fp = @fsockopen($host, $port, $errno, $errstr, 2);
    if ( !$fp ) // Couldn't open a connection to $host
    return false;
    
    // Send the GET request
    $request = "GET $path HTTP/1.1\r\nHost: $host\r\nUser-Agent: Lite Publisher/$options->version\r\n\r\n";
    fputs($fp, $request);
    
    // Let's check for an X-Pingback header first
    while ( !feof($fp) ) {
      $line = fgets($fp, 512);
      if ( trim($line) == '' )
      break;
      $headers .= trim($line)."\n";
      $x_pingback_header_offset = strpos(strtolower($headers), $x_pingback_str);
      if ( $x_pingback_header_offset ) {
        // We got it!
        preg_match('#x-pingback: (.+)#is', $headers, $matches);
        $pingback_server_url = trim($matches[1]);
        return $pingback_server_url;
      }
      if ( strpos(strtolower($headers), 'content-type: ') ) {
        preg_match('#content-type: (.+)#is', $headers, $matches);
        $content_type = trim($matches[1]);
      }
    }
    
    if ( preg_match('#(image|audio|video|model)/#is', $content_type) ) // Not an (x)html, sgml, or xml page, no use going further
    return false;
    
    while ( !feof($fp) ) {
      $line = fgets($fp, 1024);
      $contents .= trim($line);
      $pingback_link_offset_dquote = strpos($contents, $pingback_str_dquote);
      $pingback_link_offset_squote = strpos($contents, $pingback_str_squote);
      if ( $pingback_link_offset_dquote || $pingback_link_offset_squote ) {
        $quote = ($pingback_link_offset_dquote) ? '"' : '\'';
        $pingback_link_offset = ($quote=='"') ? $pingback_link_offset_dquote : $pingback_link_offset_squote;
        $pingback_href_pos = @strpos($contents, 'href=', $pingback_link_offset);
        $pingback_href_start = $pingback_href_pos+6;
        $pingback_href_end = @strpos($contents, $quote, $pingback_href_start);
        $pingback_server_url_len = $pingback_href_end - $pingback_href_start;
        $pingback_server_url = substr($contents, $pingback_href_start, $pingback_server_url_len);
        // We may find rel="pingback" but an incomplete pingback URL
        if ( $pingback_server_url_len > 0 ) // We got it!
        return $pingback_server_url;
      }
      $byte_count += strlen($line);
      if ( $byte_count > $timeout_bytes ) {
        // It's no use going further, there probably isn't any pingback
        // server to find in this file. (Prevents loading large files.)
        return false;
      }
    }
    
    // We didn't find anything.
    return false;
  }
  
  public function pingservices($url) {
    global $options;
    $home = $options->url . $options->home;
    $list = explode("\n", $this->services);
    foreach ($list as $service) {
      $service = trim($service);
      sleep(1);
      $client = new IXR_Client($service);
      $client->timeout = 3;
      $client->useragent .= ' -- Lite Publisher/'.$options->version;
      $client->debug = false;
      if ( !$client->query('weblogUpdates.extendedPing', $options->name, $home, $url, "$options->url/rss/") )
      $client->query('weblogUpdates.ping', $options->name, $url);
    }
  }
  
}//class

class tpinglinks extends titems {
  public $pid;
  
  public function __construct($pid) {
    $this->pid = $pid;
    parent::__construct();
  }
  
  public function getbasename() {
    if (dbversion) {
      return 'pingedlinks' . DIRECTORY_SEPARATOR . $this->pid;
    } else {
      return 'posts' . DIRECTORY_SEPARATOR . $this->pid . DIRECTORY_SEPARATOR . 'pingedlinks';
    }
  }
  
}//class

?>