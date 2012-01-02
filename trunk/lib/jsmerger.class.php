<?php
/**
* Lite Publisher
* Copyright (C) 2010, 2012 Vladimir Yushko http://litepublisher.com/
* Dual licensed under the MIT (mit.txt)
* and GPL (gpl.txt) licenses.
**/

class tfilemerger extends titems {
  
  public static function i() {
    return getinstance(__class__);
  }
  
  protected function create() {
    $this->dbversion = false;
    parent::create();
    $this->basename = 'jsmerger';
    $this->data['revision'] = 0;
  }
  
  public function save() {
    if ($this->lockcount > 0) return;
    $this->data['revision']++;
    parent::save();
    $this->merge();
  }
  
  public function normfilename($filename) {
    $filename = trim($filename);
    if (strbegin($filename,litepublisher::$paths->home)) $filename = substr($filename, strlen(litepublisher::$paths->home));
    if (empty($filename)) return false;
    $filename = str_replace(DIRECTORY_SEPARATOR, '/', $filename);
    $filename = '/' . ltrim($filename, '/');
    return $filename;
  }
  
  public function add($section, $filename) {
    if (!($filename = $this->normfilename($filename))) return false;
    if (!isset($this->items[$section])) {
      $this->items[$section] = array(
      'files' => array($filename),
      'texts' => array()
      );
    } else {
      if (in_array($filename, $this->items[$section]['files'])) return false;
      $this->items[$section]['files'][] = $filename;
    }
    $this->save();
    return count($this->items[$section]['files']) - 1;
  }
  
  public function deletefile($section, $filename) {
    if (!isset($this->items[$section])) return false;
    if (!($filename = $this->normfilename($filename))) return false;
    if (false === ($i = array_search($filename, $this->items[$section]['files']))) return false;
    array_delete($this->items[$section]['files'], $i);
    $this->save();
  }
  
  public function setfiles($section, $s) {
    $this->lock();
    if (isset($this->items[$section])) {
      $this->items[$section]['files'] = array();
    } else {
      $this->items[$section] = array(
      'files' => array(),
      'texts' => array()
      );
    }
    
    $a = explode("\n", trim($s));
    foreach ($a as $filename) {
      $this->add($section, trim($filename));
    }
    $this->unlock();
  }
  
  public function addtext($section, $key, $s) {
    $s = trim($s);
    if (empty($s)) return false;
    if (!isset($this->items[$section])) {
      $this->items[$section] = array(
      'files' => array(),
      'texts' => array($key => $s)
      );
    } else {
      if (in_array($s, $this->items[$section]['texts'])) return false;
      $this->items[$section]['texts'][$key] = $s;
    }
    $this->save();
    return count($this->items[$section]['texts']) - 1;
  }
  
  public function deletetext($section, $key) {
    if (!isset($this->items[$section]['texts'][$key])) return;
    unset($this->items[$section]['texts'][$key]);
    $this->save();
    return true;
  }
  
  public function getfilename($section, $revision) {
    return sprintf('/files/js/%s.%s.js', $section, $revision);
  }
  
  public function readfile($filename) {
    $result = file_get_contents($filename);
    if ($result === false) $this->error(sprintf('Error read %s file', $filename));
    return $result;
  }
  
  public function merge() {
    $home = rtrim(litepublisher::$paths->home, DIRECTORY_SEPARATOR);
    /*
    $theme = ttheme::i();
    $template = ttemplate::i();
    */
    $theme = getinstance('ttheme');
    $template = getinstance('ttemplate');
    
    foreach ($this->items as $section => $items) {
      $s = '';
      foreach ($items['files'] as $filename) {
        $filename = $theme->parse($filename);
        $filename = $home . str_replace('/', DIRECTORY_SEPARATOR, $filename);
        if (file_exists($filename)) {
          $s .= $this->readfile($filename);
          $s .= "\n"; //prevent comments
        }
      }
      $s .= implode("\n", $items['texts']);
      $savefile =  $this->getfilename($section, $this->revision);
      $realfile= $home . str_replace('/',DIRECTORY_SEPARATOR, $savefile);
      file_put_contents($realfile, $s);
      @chmod($realfile, 0666);
      $template->data[$this->basename . '_' . $section] = $savefile;
    }
    $template->save();
    litepublisher::$urlmap->clearcache();
    foreach (array_keys($this->items) as $section) {
      $old = $home . str_replace('/',DIRECTORY_SEPARATOR, $this->getfilename($section, $this->revision - 1));
      if (file_exists($old)) @unlink($old);
    }
  }
  
}//class

class tjsmerger extends tfilemerger {
  
  public static function i() {
    return getinstance(__class__);
  }
  
  public function addlang($section, $key, array $lang) {
    return $this->addtext($section, $key,
  "var lang;\nif (lang == undefined) lang = {};\n" .
    sprintf('lang.%s = %s;', $section, json_encode($lang)));
  }
  
  
  public function onupdated() {
    $this->save();
  }
  
}//class