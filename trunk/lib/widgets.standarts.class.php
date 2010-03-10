<?php
/**
* Lite Publisher
* Copyright (C) 2010 Vladimir Yushko http://litepublisher.com/
* Dual licensed under the MIT (mit.txt)
* and GPL (gpl.txt) licenses.
**/

class tstdwidgets extends titems {
  public $disableajax;
  
  public static function instance() {
    return getinstance(__class__);
  }
  
  protected function create() {
    parent::create();
    $this->basename = 'widgets.standarts';
    $this->dbversion = false;
    $this->disableajax = false;
    $this->data['names'] = array('categories', 'archives', 'links', 'friends', 'tags', 'posts', 'comments', 'meta');
    $this->data['meta'] = '';
  }
  
  public function add($name, $ajax, $sitebar) {
    if (isset($this->items[$name])) return $this->error("widget  $name already exists");
    if ($name == 'comments') {
      $manager = tcommentmanager::instance();
      $comwidget = tcommentswidget::instance();
      $manager->changed = $comwidget->changed;
    }
    $widgets = twidgets::instance();
    $id = $widgets->add($this->class, 'echo', $sitebar, -1);
    $this->items[$name] = array(
    'id' => $id,
    'ajax' => $ajax,
    'title' => $this->gettitle($name)
    );
    $this->save();
    return $id;
  }
  
  public function setajax($name, $ajax) {
    if (isset($this->items[$name]) && ($this->items[$name]['ajax'] != $ajax)) {
      $this->items[$name]['ajax'] = $ajax;
      $this->save();
    }
  }
  
  public function delete($name) {
    if (!isset($this->items[$name])) return;
    $widgets = twidgets::instance();
    $widgets->delete($this->items[$name]['id']);
    unset($this->items[$name]);
    $this->save();
  }
  
  public function expire($name) {
    if (!isset($this->items[$name])) return;
    $widgets = twidgets::instance();
    $widgets->itemexpired($this->items[$name]['id']);
  }
  
  public function widgetdeleted($id) {
    if ($name = $this->getname($id)) {
      unset($this->items[$name]);
      $this->save();
      
      if ($name == 'comments') {
        $manager = tcommentmanager::instance();
        $comwidget = tcommentswidget::instance();
        $manager->unsubscribeclass($comwidget);
      }
    }
  }
  
  public function gettitle($name) {
    return tlocal::$data['stdwidgetnames'][$name];
  }
  
  public function getname($id) {
    foreach ($this->items as $name => $item) {
      if ($id == $item['id']) return $name;
    }
    return false;
  }
  
  public function xmlrpcgetwidget($name) {
    if (!isset($this->items[$name])) throw new Exception('Widget not found.', 404);
    $result = $this->getcontent($name);
    //fix for javascript xmlrpc
    if ($result == '') return 'false';
    return $result;
  }
  
  public function getwidget($id, $sitebar) {
    if (!($name = $this->getname($id))) return '';
    if (litepublisher::$options->icondisabled) {
      $icon = '';
    } else {
      $icons = ticons::instance();
      $icon = $icons->geticon($name);
    }
    
    $result = '';
    $title = $this->items[$name]['title'];
    if ($this->items[$name]['ajax'] && !$this->disableajax) {
      $theme = ttheme::instance();
      if (isset($theme->sitebars->array[$sitebar][$name]['id'])) {
        $id = $theme->sitebars->array[$sitebar][$name]['id'];
        $id = "'$id'";
      } else {
        $id = 'this';
      }
      $title = "<a onclick=\"widgets.load(this, '$name', $id)\">$title</a>";
      $content = '';
    } elseif ($name == 'comments') {
      $content = $this->getcommentswidget($id);
    } else {
      $content = $this->getcontent($name);
    }
    
    $theme = ttheme::instance();
    $result .= $theme->getwidget($icon . $title, $content, $name, $sitebar);
    return $result;
  }
  
  public function getwidgetcontent($id) {
    if ($name = $this->getname($id)) {
      return $this->getcontent($name);
    }
    return '';
  }
  
  private function getinstance($name) {
    switch ($name) {
      case 'links':
      return tlinkswidget::instance();
      
      case 'comments':
      return tcommentswidget::instance();
      
      case 'friends':
      return tfoaf::instance();
      
      default:
      return litepublisher::$classes->$name;
    }
  }
  
  public function getcontent($name) {
    if ($name == 'meta') return $this->meta;
    $id = isset($this->items[$name]) ? $this->items[$name]['id'] : $name;
    $widgets = twidgets::instance();
    $file = $widgets->getcachefile($id);
    if (file_exists($file)) return file_get_contents($file);
    
    $instance = $this->getinstance($name);
    $result = $instance->getwidgetcontent($id, $widgets->findsitebar($id));
    file_put_contents($file, $result);
    @chmod($file, 0666);
    return $result;
  }
  
  protected function setmeta($s) {
    if ($this->meta != $s) {
      $this->data['meta'] = $s;
      $this->save();
    }
  }
  
  private function getcommentswidget($id) {
    $widgets = twidgets::instance();
    $filename = $widgets->getcachefilename($id);
    $file = litepublisher::$paths->cache . $filename;
    if (!@file_exists($file)) {
      $this->getcontent('comments');
    }
    return "\n<?php @include(litepublisher::\$paths->cache . '$filename'); ?>\n";
  }
  
}//class
?>