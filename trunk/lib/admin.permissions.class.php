<?php
/**
* Lite Publisher
* Copyright (C) 2010, 2012 Vladimir Yushko http://litepublisher.com/
* Dual licensed under the MIT (mit.txt)
* and GPL (gpl.txt) licenses.
**/

class tadminperms extends tadminmenu {
  
  public static function i($id = 0) {
    return parent::iteminstance(__class__, $id);
  }
  
  public static function getpermform($url) {
    $html = tadminhtml ::i();
    $html->section = 'perms';
    $lang = tlocal::i('perms');
    $args = targs::i();
    $args->url = litepublisher::$site->url . $url;
    $args->items = self::getcombo(tadminhtml::getparam('idperm', 1));
    return $html->comboform($args);
  }
  
  public static function getcomboperm($idperm, $name = 'idperm') {
    $lang = tlocal::i('perms');
    $theme = ttheme::i();
    return strtr($theme->templates['content.admin.combo'], array(
    '$lang.$name' => $lang->perm,
    '$name' => $name,
    '$value' => self::getcombo($idperm)
    ));
  }
  
  public static function getcombo($idperm) {
      $result = sprintf('<option value="0" %s>%s</option>', $idperm == 0 ? 'selected="selected"' : '', tlocal::get('perms', 'nolimits'));
    $perms = tperms::i();
    foreach ($perms->items as $id => $item) {
      $result .= sprintf('<option value="%d" %s>%s</option>', $id,
      $idperm == $id ? 'selected="selected"' : '', $item['name']);
    }
    return $result;
  }
  
    public function getcontent() {
    $result = '';
    $perms = tperms::i();
    $html = $this->html;
    $lang = tlocal::i('perms');
    $args = targs::i();
if (!($action = $this->action)) $action = 'perms';
    switch ($action) {
      case 'perms':
$args->editurl = $this->link . litepublisher::$site->q . 'action=edit&id';
foreach ($perms->items as $id => $item) {
if ($id == 1) continue;
$args->add($item);
$result .= $html->item($args);
}

$result = '<form name="deleteform" method="post" action="">' .
$html->gettable($html->tablehead, $result) .
'<p>'.
$html->getsubmit('delete').
'</p></form>';

foreach ($perms->classes as $class => $name) {
$args->class = $class;
$args->name = $name;
$result .= 
}

}

return
}

  public function processform() {
    $result = '';
    switch ($this->name) {