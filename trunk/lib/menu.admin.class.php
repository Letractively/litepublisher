<?php
/**
* Lite Publisher
* Copyright (C) 2010 Vladimir Yushko http://litepublisher.com/
* Dual licensed under the MIT (mit.txt)
* and GPL (gpl.txt) licenses.
**/

class tadminmenus extends tmenus {
  
  public static function instance() {
    return getinstance(__class__);
  }
  
  protected function create() {
    parent::create();
    $this->basename = 'adminmenu';
    tadminmenu::$ownerprops = array_merge(tadminmenu::$ownerprops, array('name', 'group'));
  }
  
  
  private function getadmintitle($name) {
    if (isset(tlocal::$data[$name]['title'])) {
      return tlocal::$data[$name]['title'];
    } elseif (isset(tlocal::$data['names'][$name])) {
      return tlocal::$data['names'][$name];
    } elseif (isset(tlocal::$data['default'][$name])) {
      return tlocal::$data['default'][$name];
    } elseif (isset(tlocal::$data['common'][$name])) {
      return tlocal::$data['common'][$name];
    } else {
      return $name;
    }
  }
  
  public function createitem($parent, $name, $group, $class) {
    $title = $this->getadmintitle($name);
    $url = $parent == 0 ? "/admin/$name/" : $this->items[$parent]['url'] . "$name/";
    return $this->additem(array(
    'parent' => $parent,
    'url' => $url,
    'title' => $title,
    'name' => $name,
    'class' => $class,
    'group' => $group
    ));
  }
  
  public function hasright($group) {
    $groups = tusergroups::instance();
    return $groups->hasright(litepublisher::$options->group, $group);
  }
  
  protected function getchilds($id) {
    if ($id == 0) {
      $result = array();
      foreach ($this->tree as $iditem => $items) {
        if ($this->hasright($this->items[$iditem]['group']))
        $result[] = $iditem;
      }
      return $result;
    }
    
    $parents = array($id);
    $parent = $this->items[$id]['parent'];
    while ($parent != 0) {
      array_unshift ($parents, $parent);
      $parent = $this->items[$parent]['parent'];
    }
    
    $tree = $this->tree;
    foreach ($parents as $parent) {
      foreach ($tree as $iditem => $items) {
        if ($iditem == $parent) {
          $tree = $items;
          break;
        }
      }
    }
    return array_keys($tree);
  }
  
  public function getmenu($hover) {
    if (count($this->tree) == 0) return '';
    $theme = ttheme::instance();
    if ($hover) {
      $result = $this->getsubmenu($this->tree);
      return sprintf($theme->menu, $result);
    }
    
    $result = '';
    $tml = $theme->menu->item;
    foreach ($this->tree as $id => $items) {
      $item = $this->items[$id];
      if ($this->hasright($item['group'])) $result .= sprintf($tml, litepublisher::$options->url. $item['url'], $item['title'], '');
    }
    $result = sprintf($theme->menu, $result);
    return $result;
  }
  
  private function getsubmenu(&$tree) {
    $result = '';
    $theme = ttheme::instance();
    $tml = $theme->menu->item;
    foreach ($tree as $id => $items) {
      $item = $this->items[$id];
      if ($this->hasright($item['group'])) {
        $subitems = count($items) == 0 ? '' : sprintf($theme->menu->submenu, $this->getsubmenu($items));
        $result .= sprintf($tml,litepublisher::$options->url . $item['url'], $item['title'], $subitems);
      }
    }
    return $result;
  }
  
}//class

class tadminmenu  extends tmenu {
  public $arg;
  
  public static function getinstancename() {
    return 'adminmenu';
  }
  
  public static function getowner() {
    return tadminmenus::instance();
  }
  
  protected function create() {
    parent::create();
    $this->cache = false;
  }
  
public function load() { return true; }
public function save() { return true; }
  
  public function auth() {
    $auth = tauthdigest::instance();
    if (litepublisher::$options->cookieenabled) {
      if ($s = $auth->checkattack()) return $s;
      if (!litepublisher::$options->authcookie()) return litepublisher::$urlmap->redir301('/admin/login/');
    }
    elseif (!$auth->Auth())  return $auth->headers();
    
    if (litepublisher::$options->group != 'admin') {
      $groups = tusergroups::instance();
      if (!$groups->hasright(litepublisher::$options->group, $this->group)) return 404;
    }
  }
  
  public function request($id) {
    if (is_null($id)) $id = $this->owner->class2id(get_class($this));
    $this->data['id'] = (int)$id;
    if ($id > 0) {
      $this->basename =  $this->parent == 0 ? $this->name : $this->owner->items[$this->parent]['name'];
    }
    
    if ($s = $this->auth()) return $s;
    tlocal::loadlang('admin');
    $this->arg = litepublisher::$urlmap->argtree;
    $this->doprocessform();
  }
  
  public function idget() {
    return !empty($_GET['id']) ? (int) $_GET['id'] : (!empty($_POST['id']) ? (int)$_POST['id'] : 0);
  }
  
  public function getaction() {
    if (isset($_REQUEST['action']))  return $_REQUEST['action'];
    return false;
  }
  
  public function gethtml($name = '') {
    $result = THtmlResource ::instance();
    if ($name == '') $name = $this->basename;
    if (!isset($result->ini[$name])) {
      $name = $this->owner->items[$this->parent]['name'];
    }
    
    $result->section = $name;
    $lang = tlocal::instance($name);
    return $result;
  }
  
  public function getlang() {
    return tlocal::instance($this->name);
  }
  
  public function getconfirmed() {
    return isset($_REQUEST['confirm']) && ($_REQUEST['confirm'] == 1);
  }
  
  public function getnotfound() {
    return $this->html->h2->notfound;
  }
  
  public function getadminurl() {
    return litepublisher::$options->url .$this->url . litepublisher::$options->q . 'id';
  }
  
  public function getfrom($perpage, $count) {
    if (litepublisher::$urlmap->page <= 1) return 0;
    return min($count, (litepublisher::$urlmap->page - 1) * $perpage);
  }
  
}//class
?>