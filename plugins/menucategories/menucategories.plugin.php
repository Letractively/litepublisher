<?php
/**
* Lite Publisher
* Copyright (C) 2010, 2011 Vladimir Yushko http://litepublisher.com/
* Dual licensed under the MIT (mit.txt)
* and GPL (gpl.txt) licenses.
**/

class tcategoriesmenu extends tplugin {
public $tree;
public $exitems;
  
  public static function instance() {
    return getinstance(__class__);
  }
  
  protected function create() {
    parent::create();
$this->addmap('tree', array());
$this->addmap('exitems', array());
  }
  
  public function ongetmenu() {
if (litepublisher::$urlmap->adminpanel) return;
$template = ttemplate::instance();
    $current = $template->context instanceof tcategories ? $template->context->id : 0;
    $filename = litepublisher::$paths->cache . $template->view->theme->name . '.' . $current . '.catmenu.php';
    if (file_exists($filename)) return file_get_contents($filename);
        $result = $this->getmenu($template->hover, $current);
    file_put_contents($filename, $result);
    @chmod($filename, 0666);
    return $result;
}

  public function getmenu($hover, $current) {
    $result = '';
$categories = tcategories::instance();
$categories->loadall();
//$this->buildtree();
//var_dump($this->tree);
    if (count($this->tree) > 0) {
      $theme = ttheme::instance();
      if ($hover) {
        $items = $this->getsubmenu($this->tree, $current);
      } else {
        $items = '';
        $tml = $theme->templates['menu.item'];
        $args = targs::instance();
        $args->submenu = '';
        foreach ($this->tree as $id => $subitems) {
          if ($this->exclude($id)) continue;
          $args->add($categories->items[$id]);
          $items .= $current == $id ? $theme->parsearg($theme->templates['menu.current'], $args) : $theme->parsearg($tml, $args);
        }
      }
      
      $result = str_replace('$item', $items, $theme->templates['menu']);
    }
    return $result;
  }
  
  public function exclude($id) {
    return in_array($id, $this->exitems);
  }

  private function getsubmenu(&$tree, $current) {
    $result = '';
$categories = tcategories::instance();
    $theme = ttheme::instance();
    $tml = $theme->templates['menu.item'];
    $tml_submenu = $theme->templates['menu.item.submenu'];
    $args = targs::instance();
    foreach ($tree as $id => $items) {
      if ($this->exclude($id)) continue;
      $submenu = count($items) == 0 ? '' :  str_replace('$items', $this->getsubmenu($items, $current), $tml_submenu);
      $args->submenu = $submenu;
      $args->add($categories->items[$id]);
      $result .= $theme->parsearg($current == $id ?  $theme->templates['menu.current'] : $tml, $args);
    }
    return $result;
  }
  
  public function buildtree() {
$categories = tcategories::instance();
$categories->loadall();
    $this->tree = $this->getsubtree(0);
var_dump($this->exitems );
$this->exitems = array_intersect(array_keys($categories->items), $this->exitems);
$this->save();
  }
  
  private function getsubtree($parent) {
    $result = array();
$categories = tcategories::instance();
    // first step is a find all childs and sort them
    $sort= array();
    foreach ($categories->items as $id => $item) {
      if ($item['parent'] == $parent) {
        $sort[$id] = (int) $item['customorder'];
      }
    }
    arsort($sort, SORT_NUMERIC);
    $sort = array_reverse($sort, true);
    
    foreach ($sort as $id => $order) {
      $result[$id]  = $this->getsubtree($id);
    }
    return $result;
  }
  
}//class
