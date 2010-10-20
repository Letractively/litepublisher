<?php
/**
* Lite Publisher
* Copyright (C) 2010 Vladimir Yushko http://litepublisher.com/
* Dual licensed under the MIT (mit.txt)
* and GPL (gpl.txt) licenses.
**/

if (version_compare(PHP_VERSION, '5.1', '<')) {
  die('Lite Publisher requires PHP 5.2 or later. You are using PHP ' . PHP_VERSION) ;
}

class litepublisher {
  public static $db;
public static $storage;
  public static $classes;
  public static $options;
public static $site;
  public static $urlmap;
  public static $paths;
  public static $_paths;
  public static $domain;
  public static $debug = false;
  public static $secret = '8r7j7hbt8iik//pt7hUy5/e/7FQvVBoh7/Zt8sCg8+ibVBUt7rQ';
  public static $microtime;
  
  public static function init() {
    if (!preg_match('/(www\.)?([\w\.\-]+)(:\d*)?/', strtolower(trim($_SERVER['HTTP_HOST'])) , $domain)) die('cant resolve domain name');
    self::$domain = $domain[2];
    
    $home = dirname(__file__) . DIRECTORY_SEPARATOR;
    $storage = $home . 'storage' . DIRECTORY_SEPARATOR;
    self::$_paths = array(
    'home' => $home,
    'lib' => $home .'lib'. DIRECTORY_SEPARATOR,
    'libinclude' => $home .'lib'. DIRECTORY_SEPARATOR . 'include'. DIRECTORY_SEPARATOR,
    'languages' => $home .'lib'. DIRECTORY_SEPARATOR . 'languages'. DIRECTORY_SEPARATOR,
    'storage' => $storage,
    'data' => $storage . 'data'. DIRECTORY_SEPARATOR,
    'cache' => $storage . 'cache'. DIRECTORY_SEPARATOR,
    'backup' => $storage . 'backup' . DIRECTORY_SEPARATOR,
    'plugins' =>  $home . 'plugins' . DIRECTORY_SEPARATOR,
    'themes' => $home . 'themes'. DIRECTORY_SEPARATOR,
    'files' => $home . 'files' . DIRECTORY_SEPARATOR,
    'js' => $home . 'js' . DIRECTORY_SEPARATOR
    );
    
    self::$paths = new tpaths();
    self::$microtime = microtime(true);
  }
  
}

class tpaths {
public function __get($name) { return litepublisher::$_paths[$name]; }
public function __set($name, $value) { litepublisher::$_paths[$name] = $value; }
}

try {
  litepublisher::init();
  require_once(litepublisher::$paths->lib . 'kernel.php');
  litepublisher::$classes = tclasses::instance();
  litepublisher::$options = toptions::instance();
  if (!litepublisher::$options->installed) require_once(litepublisher::$paths->lib .'install' . DIRECTORY_SEPARATOR . 'install.php');
  if (dbversion) litepublisher::$db = new tdatabase();
  litepublisher::$options->admincookie = litepublisher::$options->cookieenabled && litepublisher::$options->authcookie();
  litepublisher::$urlmap = turlmap::instance();
  if (!defined('litepublisher_mode')) {
    litepublisher::$urlmap->request(strtolower($_SERVER['HTTP_HOST']), $_SERVER['REQUEST_URI']);
  }
  
} catch (Exception $e) {
  echo $e->GetMessage();
}
litepublisher::$options->savemodified();
litepublisher::$options->showerrors();
?>