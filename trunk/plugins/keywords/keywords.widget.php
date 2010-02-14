<?php
/**
 * Lite Publisher 
 * Copyright (C) 2010 Vladimir Yushko http://litepublisher.com/
 * Dual licensed under the MIT (mit.txt) 
 * and GPL (gpl.txt) licenses.
**/

class tkeywordswidget extends tevents {
 protected $links;
 
 public static function instance() {
  return getinstance(__class__);
 }
 
 public function create() {
  parent::create();
  $this->basename = 'keywords' . DIRECTORY_SEPARATOR   . 'index';
  $this->data['count'] = 6;
$this->data['notify'] = true;
$this->data['trace'] = true;
  $this->addmap('links', array());
$this->data['title'] = '';
 }
 
public function getwidget($id, $sitebar) {
  if (litepublisher::$urlmap->adminpanel || strbegin(litepublisher::$urlmap->url, '/croncron.php')) return '';
  $filename = litepublisher::$paths->data . 'keywords' . DIRECTORY_SEPARATOR. litepublisher::$urlmap->itemrequested['id'] . ".litepublisher::$urlmap->page .php";
    if (@file_exists($filename)) {
   $links = file_get_contents($filename);
  } else {
   if (count($this->links) < $this->count) return '';
$arlinks = array_splice($this->links, 0, $this->count);
   $this->Save();

   $links = "\n<li>" . implode("</li>\n<li>", $arlinks)  . "</li>";
   file_put_contents($filename, $links);
if ($this->notify) {
$plugin = tkeywordsplugin::instance();
$plugin->added($filename, $links);
}
  }
$theme = ttheme::instance();
  return $theme->getwidget($this->title, $links, 'widget', $sitebar);
 }
 
}
?>