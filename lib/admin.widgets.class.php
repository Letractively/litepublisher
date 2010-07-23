<?php
/**
* Lite Publisher
* Copyright (C) 2010 Vladimir Yushko http://litepublisher.com/
* Dual licensed under the MIT (mit.txt)
* and GPL (gpl.txt) licenses.
**/

class tadminwidgets extends tadminmenu {
  
  public static function instance($id = 0) {
    return parent::iteminstance(__class__, $id);
  }
  
  public static function getcombo($name, array $items, $selected) {
    $result = sprintf('<select name="%1$s" id="%1$s">', $name);
foreach ($items as $i => $item) {
      $result .= sprintf('<option value="%s" %s>%s</option>', $i, $i == $selected ? 'selected' : '', $item);
    }
    $result .= "</select>\n";
    return $result;
  }

public static function getsitebarnames($count) {
$result = range(1, $count);
$parser = tthemeparser::instance();
$template = ttemplate::instance();
$about = $parser->getabout($template->theme);
foreach ($result as $key => $value) {
if (isset($about["sitebar$key"])) $result[$key] = $about["sitebar$key"];
}
return $result;
}
  
public static function getsitebarsform(array $sitebars) {
$widgets = twidgets::instance();
    $html = THtmlResource ::instance();
$html->section = 'widgets';
$lang = tlocal::instance('widgets');
    $result = $html->checkallscript;
    $result .= $html->formhead();
    $args = targs::instance();
$args->adminurl = litepublisher::$options->url . '/admin/widgets/' . litepublisher::$options->q . 'idwidget';
$count = count($sitebars);
$sitebarnames = self::getsitebarnames(count($sitebars));
foreach ($sitebars as $i => $sitebar) {
$orders = range(1, count($sitebar));
foreach ($sitebar as $j => $_item) {
$id = $_item['id'];
        $args->id = $id;
$args->ajax = $_item['ajax'];
        $args->add($widgets->getitem($id));
        $args->sitebarcombo = self::getcombo("sitebar-$id", $sitebarnames, $i);
        $args->ordercombo = self::getcombo("order-$id", $orders, $j);
        $result .= $html->item($args);
      }
    }
    $result .= $html->formfooter();

//all widgets
$result .= $html->addhead();
$widgets = twidgets::instance();
foreach ($widgets->items as $id => $item) {
$args->id = $id;
$args->add($item);
$args->checked = tsitebars::getpos($sitebars, $id) ? true : false;
$result .= $html->additem($args);
}
$result .= $html->addfooter();
    return  $html->fixquote($result);
  }
  
// parse POST into sitebars array
  public static function editsitebars(array $sitebars) {
    // collect all id from checkboxes
    $items = array();
    foreach ($_POST as $key => $value) {
      if (strbegin($key, 'widgetcheck-'))$items[] = (int) $value;
    }

    foreach ($items as $id) {
    if (isset($_POST['deletewidgets']))  {
if ($pos = tsitebars::getpos($sitebars, $id)) {
list($i, $j) = $pos;
array_delete($sitebars[$i], $j);
}
} else {
$i = (int)$_POST["sitebar-$id"];
$j = (int) $_POST["order-$id"];
//var_dump($i, $j, $sitebars[0][0]);
tsitebars::setpos($sitebars, $id, $i, $j);
$sitebars[$i][$j]['ajax'] = isset($_POST["ajaxcheck-$id"]);
    }
}

return $sitebars;
    return $this->html->h2->success;
  }
  
  public function getcontent() {
$widgets = twidgets::instance();
    switch ($this->name) {
      case 'widgets':
$idwidget = isset($_GET['idwidget']) ? (int) $_GET['idwidget'] : 0;
if ($widgets->itemexists($idwidget)) {
$widget = $widgets->getwidget($idwidget);
return  $widget->admin->getcontent();
} else {
return self::getsitebarsform($widgets->sitebars);
}

case 'addcustom':
$widget = tcustomwidget::instance();
return  $widget->admin->getcontent();

case 'home':
$adminhome = tadminhomewidgets::instance();
return $adminhome->getcontent();

case 'classes':
return 'Sorry, under construction';
$result = '';
$html = $this->html;
$args = targs::instance();
$args->adminurl = litepublisher::$options->url .$this->url . litepublisher::$options->q . 'class';
foreach ($widgets->classes as $class => $items) {
$args->class = $class;
$args->name = $this->getclassname($class);
$args->count = count($items);
$result .= $html->classitem($args);
}
$args->content = $result;
return $html->classesform($args);
}
}

  public function processform() {
    litepublisher::$urlmap->clearcache();
$widgets = twidgets::instance();
    switch ($this->name) {
      case 'widgets':
$idwidget = isset($_GET['idwidget']) ? (int) $_GET['idwidget'] : 0;
if ($widgets->itemexists($idwidget)) {
$widget = $widgets->getwidget($idwidget);
return  $widget->admin->processform();
} else {
$widgets->sitebars = self::setsitebars($widgets->sitebars);
$widgets->save();
return $this->html->h2->success;
}

case 'addcustom':
$widget = tcustomwidget::instance();
return  $widget->admin->processform();

case 'home':

}
}

public static function setsitebars(array $sitebars) {
switch ($_POST['action']) {
case 'edit':
return self::editsitebars($sitebars);

case 'add':
$widgets = twidgets::instance();
    foreach ($_POST as $key => $value) {
      if (strbegin($key, 'addwidget-')){
$sitebars[0][] = array(
'id' => (int) $value,
'ajax' => false
);
}
    }
  return $sitebars;  
}
}

}//class

?>