<?php
/**
* Lite Publisher
* Copyright (C) 2010, 2011 Vladimir Yushko http://litepublisher.com/
* Dual licensed under the MIT (mit.txt)
* and GPL (gpl.txt) licenses.
**/

class tadminthemes extends tadminmenu {
  
  public static function instance($id = 0) {
    return parent::iteminstance(__class__, $id);
  }
  
  public static function getthemes() {
    $html = tadminhtml::instance();
    $html->section = 'themes';
    return sprintf('<ul>%s</ul>', self::getlist($html->item, ''));
  }
  
  public static function getlist($tml, $selected) {
    $result = '';
    $html = tadminhtml::instance();
    $html->section = 'themes';
    $args = targs::instance();
    $list =    tfiler::getdir(litepublisher::$paths->themes);
    sort($list);
    $args->editurl = tadminhtml::getadminlink('/admin/views/edittheme/', 'theme');
    $args->filesurl = tadminhtml::getadminlink('/admin/views/themefiles/', 'theme');
    $parser = tthemeparser::instance();
    foreach ($list as $name) {
      if ($about = $parser->getabout($name)) {
        $about['name'] = $name;
        $args->add($about);
        $args->checked = $name == $selected;
        $result .= $html->parsearg($tml, $args);
      }
    }
    return  $result;
  }
  
  public function getcontent() {
    $result = tadminviews::getviewform('/admin/views/themes/');
    $idview = tadminhtml::getparam('idview', 1);
    $view = tview::instance($idview);
    $html = $this->gethtml('themes');;
    $args = targs::instance();
    $args->idview = $idview;
    $theme = $view->theme;
    
    $result .= $html->formheader($args);
    $result .= self::getlist($html->radioitem, $theme->name);
    $result .= $html->formfooter();
    return $html->fixquote($result);
  }
  
  public function processform() {
    $result = '';
    $idview = tadminhtml::getparam('idview', 1);
    $view = tview::instance($idview);
    
    if  (isset($_POST['reparse'])) {
      $parser = tthemeparser::instance();
      try {
        $parser->reparse($view->theme->name);
      } catch (Exception $e) {
        $result = $e->getMessage();
      }
    } else {
      if (empty($_POST['selection']))   return '';
      try {
        $view->themename = $_POST['selection'];
        $result = $this->html->h2->success;
      } catch (Exception $e) {
        $view->themename = 'default';
        $result = $e->getMessage();
      }
    }
    ttheme::clearcache();
    return $result;
  }
  
}//class
?>