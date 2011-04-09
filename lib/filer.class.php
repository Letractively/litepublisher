<?php
/**
* Lite Publisher
* Copyright (C) 2010, 2011 Vladimir Yushko http://litepublisher.com/
* Dual licensed under the MIT (mit.txt)
* and GPL (gpl.txt) licenses.
**/

class tfiler {
  
  public static function callback($callback, $path, $subdir) {
    $path = rtrim($path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
    if ( $h = opendir($path)) {
      while(FALSE !== ($filename = readdir($h))) {
        if (($filename == '.') || ($filename == '..') || ($filename == '.svn')) continue;
        $file = $path. $filename;
        if (is_dir($file)) {
          if ($subdir) self::callback($callback, $file . DIRECTORY_SEPARATOR, $subdir);
        } else {
          call_user_func_array($callback, array($filename));
        }
      }
      closedir($h);
    }
  }
  
  public static function delete($path, $subdirs , $rmdir = false) {
    $path = rtrim($path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
    if ( $h = opendir($path)) {
      while(FALSE !== ($filename = readdir($h))) {
        if (($filename == '.') || ($filename == '..') || ($filename == '.svn')) continue;
        $file = $path. $filename;
        if (is_dir($file)) {
          if ($subdirs) self::delete($file . DIRECTORY_SEPARATOR, $subdirs, $rmdir);
        } else {
          if (!unlink($file)) {
            chmod($file, 0666);
            unlink($file);
          }
        }
      }
      closedir($h);
    }
    if ($rmdir && is_dir($path)) rmdir($path);
  }
  
  public static function deletemask($mask) {
    if ($list = glob($mask)) {
      foreach ($list as $filename) unlink($filename);
    }
  }
  
  public static function deletedirmask($path, $mask) {
    foreach (glob($path. $mask) as $filename) {
      if (is_dir($filename)) {
        self::deletedirmask($filename. DIRECTORY_SEPARATOR, $mask);
      } else {
        unlink($filename);
      }
    }
  }
  
  public static function getfiles($path) {
    $result = array();
    if ( $h = opendir($path)) {
      while(FALSE !== ($filename = readdir($h))) {
        if (($filename == '.') || ($filename == '..') || ($filename == '.svn')) continue;
        if (!is_dir($path . $filename)) $result[] = $filename;
      }
      closedir($h);
    }
    return $result;
  }
  
  public static function getdir($dir) {
    $result = array();
    if ($fp = opendir($dir)) {
      while (FALSE !== ($file = readdir($fp))) {
        if (is_dir($dir.$file)  && ($file != '.') && ($file != '..') && ($file != '.svn')){
          $result[] = $file;
        }
      }
    }
    return $result;
  }
  
  public static function forcedir($dir) {
    $dir = rtrim($dir, DIRECTORY_SEPARATOR);
    if (is_dir($dir)) return true;
    $up = rtrim(dirname($dir), DIRECTORY_SEPARATOR);
    if (($up != '') || ($up != '.'))  self::forcedir($up);
    if (!is_dir($dir)) mkdir($dir, 0777);
    chmod($dir, 0777);
    return is_dir($dir);
  }
  
  public static function log($s, $filename = '') {
    if ($filename == '') $filename = 'log.txt';
    self::append(date('r') . "\n$s\n\n", litepublisher::$paths->data . 'logs' . DIRECTORY_SEPARATOR . $filename);
  }
  
  public static function append($s, $filename) {
    $dir = dirname($filename);
    if (!is_dir($dir)) {
      if (!is_dir($dir))mkdir($dir, 0777);
      chmod($dir, 0777);
    }
    
    if ($fp = fopen($filename,"a+")) {
      fwrite($fp, $s);
      fclose($fp);
      chmod($filename, 0666);
    }
  }
  
  public static function get_filetime_offset() {
    $filename = litepublisher::$paths->data . md5uniq() . '.tmp';
    $t = time();
    touch($filename, $t, $t);
    clearstatcache  ();
    $t2 = filemtime($filename);
    unlink($filename);
    return $t2  - $t;
  }
  
}//class
?>