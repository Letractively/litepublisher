<?php
/**
* Lite Publisher
* Copyright (C) 2010 Vladimir Yushko http://litepublisher.com/
* Dual licensed under the MIT (mit.txt)
* and GPL (gpl.txt) licenses.
**/

class tadminwidget extends tdata {
  public $widget;
  protected $html;
  protected $lang;
  
  protected function create() {
    //parent::instance();
    $this->html = tadminhtml ::instance();
    $this->html->section = 'widgets';
    $this->lang = tlocal::instance('widgets');
  }
  
  protected function getadminurl() {
    return litepublisher::$site->url . '/admin/views/widgets/' . litepublisher::$site->q . 'idwidget=';
  }
  
  protected function dogetcontent(twidget $widget, targs $args){
    $this->error('Not implemented');
  }
  
  protected function optionsform($widgettitle, $content) {
    $args = targs::instance();
    $args->formtitle = $widgettitle . ' ' . $this->lang->widget;
    $args->title = $widgettitle;
    $args->items = $this->html->getedit('title', $widgettitle, $this->lang->widgettitle) . $content;
    return $this->html->parsearg(ttheme::instance()->templates['content.admin.form'], $args);
  }
  
  public function getcontent(){
    return $this->optionsform(
    $this->widget->gettitle($this->widget->id),
    $this->dogetcontent($this->widget, targs::instance()));
  }
  
  public function processform()  {
    $widget = $this->widget;
    $widget->lock();
    if (isset($_POST['title'])) $widget->settitle($widget->id, $_POST['title']);
    $this->doprocessform($widget);
    $widget->unlock();
    return $this->html->h2->updated;
  }
  
  protected function doprocessform(twidget $widget)  {
    $this->error('Not implemented');
  }
  
}//class

class tadmintagswidget extends tadminwidget {
  
  public static function instance() {
    return getinstance(__class__);
  }
  
  protected function dogetcontent(twidget $widget, targs $args){
    $args->showcount = $widget->showcount;
    $args->showsubitems = $widget->showsubitems;
    $args->maxcount = $widget->maxcount;
    $args->sort = tadminhtml::array2combo(tlocal::$data['sortnametags'], $widget->sortname);
    return $this->html->parsearg('[combo=sort] [checkbox=showsubitems] [checkbox=showcount] [text=maxcount]', $args);
  }
  
  protected function doprocessform(twidget $widget)  {
    extract($_POST, EXTR_SKIP);
    $widget->maxcount = (int) $maxcount;
    $widget->showcount = isset($showcount);
    $widget->showsubitems = isset($showsubitems);
    $widget->sortname = $sort;
  }
  
}//class

class tadminmaxcount extends tadminwidget {
  
  public static function instance() {
    return getinstance(__class__);
  }
  
  protected function dogetcontent(twidget $widget, targs $args){
    $args->maxcount = $widget->maxcount;
    return $this->html->parsearg('[text=maxcount]', $args);
  }
  
  protected function doprocessform(twidget $widget)  {
    $widget->maxcount = (int) $_POST['maxcount'];
  }
  
}//class

class tadminshowcount extends tadminwidget {
  
  public static function instance() {
    return getinstance(__class__);
  }
  
  protected function dogetcontent(twidget $widget, targs $args){
    $args->showcount = $widget->showcount;
    return $this->html->parsearg('[checkbox=showcount]', $args);
  }
  
  protected function doprocessform(twidget $widget)  {
    $widget->showcount = isset($_POST['showcount']);
  }
  
}//class

class tadminorderwidget extends tadminwidget {
  
  public static function instance() {
    return getinstance(__class__);
  }
  
  protected function dogetcontent(twidget $widget, targs $args){
    $idview = tadminhtml::getparam('idview', 1);
    $view = tview::instance($idview);
    $args->sidebar = tadminhtml::array2combo(tadminwidgets::getsidebarnames($view), $widget->sidebar);
    $args->order = tadminhtml::array2combo(range(-1, 10), $widget->order + 1);
    $args->ajax = $widget->ajax;
    return $this->html->parsearg('[combo=sidebar] [combo=order] [checkbox=ajax]', $args);
  }
  
  protected function doprocessform(twidget $widget)  {
    $widget->sidebar = (int) $_POST['sidebar'];
    $widget->order = ((int) $_POST['order'] - 1);
    $widget->ajax = isset($_POST['ajax']);
  }
  
}//class

class tadmincustomwidget extends tadminwidget {
  
  public static function instance() {
    return getinstance(__class__);
  }
  
  public static function gettemplates() {
    $result = array();
    $lang = tlocal::instance('widgets');
    $result['widget'] = $lang->defaulttemplate;
    foreach (ttheme::getwidgetnames() as $name) {
      $result[$name] = $lang->$name;
    }
    return $result;
  }
  
  public function getcontent() {
    $widget = $this->widget;
    $args = targs::instance();
    $id = (int) tadminhtml::getparam('idwidget', 0);
    if (isset($widget->items[$id])) {
      $item = $widget->items[$id];
      $args->mode = 'edit';
    } else {
      $id = 0;
      $args->mode = 'add';
      $item = array(
      'title' => '',
      'content' => '',
      'template' => 'widget'
      );
    }
    
    $args->idwidget = $id;
    $html= $this->html;
    $args->text = $item['content'];
    $args->template =tadminhtml::array2combo(self::gettemplates(), $item['template']);
    $result = $this->optionsform($item['title'], $html->parsearg(
    '[editor=text]
    [combo=template]
    [hidden=mode]
    [hidden=idwidget]',
    $args));
    $result .= $html->customheader();
    $args->adminurl = $this->adminurl;
    
    foreach ($widget->items as $id => $item) {
      $args->idwidget = $id;
      $args->add($item);
      $result .= $html->customitem($args);
    }
    $result .= $html->customfooter();
    return $result;
  }
  
  public function processform()  {
    $widget = $this->widget;
    if (isset($_POST['mode'])) {
      extract($_POST, EXTR_SKIP);
      switch ($mode) {
        case 'add':
        $_GET['idwidget'] = $widget->add($title, $text, $template);
        break;
        
        case 'edit':
        $id = isset($_GET['idwidget']) ? (int) $_GET['idwidget'] : 0;
        if ($id == 0) $id = isset($_POST['idwidget']) ? (int) $_POST['idwidget'] : 0;
        $widget->edit($id, $title, $text, $template);
        break;
      }
    } else {
      $widgets = twidgets::instance();
      $widgets->lock();
      $widget->lock();
      foreach ($_POST as $key => $value) {
        if (strbegin($key, 'widgetcheck-')) $widget->delete((int) $value);
      }
      $widget->unlock();
      $widgets->unlock();
    }
  }
  
}//class
class tadminlinkswidget extends tadminwidget {
  
  public static function instance() {
    return getinstance(__class__);
  }
  
  protected function dogetcontent(twidget $widget, targs $args){
    $args->redir = $widget->redir;
    return $this->html->parsearg('[checkbox=redir]', $args);
  }
  
  public function getcontent() {
    $result = parent::getcontent();
    $widget = $this->widget;
    $html= $this->html;
    $args = targs::instance();
    $id = isset($_GET['idlink']) ? (int) $_GET['idlink'] : 0;
    if (isset($widget->items[$id])) {
      $item = $widget->items[$id];
      $args->mode = 'edit';
    } else {
      $args->mode = 'add';
      $item = array(
      'url' => '',
      'title' => '',
      'anchor' => ''
      );
    }
    
    $args->add($item);
    $result .= $html->linkform($args);
    
    $args->adminurl = $this->adminurl . $_GET['idwidget'] . '&idlink';
    $result .= $html->linkstableheader ();
    foreach ($widget->items as $id => $item) {
      $args->id = $id;
      $args->add($item);
      $result .= $html->linkitem($args);
    }
    $result .= $html->linkstablefooter();
    return $result;
  }
  
  public function processform()  {
    $widget = $this->widget;
    $widget->lock();
    if (isset($_POST['delete'])) {
      foreach ($_POST as $key => $value) {
        $id = (int) $value;
        if (isset($widget->items[$id]))  $widget->delete($id);
      }
    } elseif (isset($_POST['mode'])) {
      extract($_POST, EXTR_SKIP);
      switch ($mode) {
        case 'add':
        $_GET['idlink'] = $widget->add($url, $linktitle, $anchor);
        break;
        
        case 'edit':
        $widget->edit((int) $_GET['idlink'], $url, $linktitle, $anchor);
        break;
      }
    } else {
      extract($_POST, EXTR_SKIP);
      $widget->settitle($widget->id, $title);
      $widget->redir = isset($redir);
    }
    $widget->unlock();
    return $this->html->h2->updated;
  }
  
}//class

class tadminmetawidget extends tadminwidget {
  
  public static function instance() {
    return getinstance(__class__);
  }
  
  protected function dogetcontent(twidget $widget, targs $args){
    $html = $this->html;
    $result = '';
    foreach ($widget->items as $name => $item) {
      $args->add($item);
      $args->name = $name;
      $result .= $html->metaitem($args);
    }
    return $result;
  }
  
  protected function doprocessform(twidget $widget)  {
    foreach ($widget->items as $name => $item) {
      $widget->items[$name]['enabled'] = isset($_POST[$name]);
    }
  }
  
}//class

?>