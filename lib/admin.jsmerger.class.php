<?php
/**
* Lite Publisher
* Copyright (C) 2010, 2011 Vladimir Yushko http://litepublisher.com/
* Dual licensed under the MIT (mit.txt)
* and GPL (gpl.txt) licenses.
**/

class tadminjsmerger extends tadminmenu {
  public static function i($id = 0) {
    return parent::iteminstance(__class__, $id);
  }
  
  public function  gethead() {
    return parent::gethead() . tuitabs::gethead();
  }
  
  public function getcontent() {
    $jsmerger = tjsmerger::i();
    $tabs = new tuitabs();
    $html = $this->html;
    $lang = tlocal::i('views');
    $args = targs::i();
    $args->formtitle= $lang->jsmergertitle;
    foreach ($jsmerger->items as $section => $items) {
      $tab = new tuitabs();
      $tab->add($lang->files, $html->getinput('editor',
      $section . '_files', tadminhtml::specchars(implode("\n", $items['files'])), $lang->files));
      $tabtext = new tuitabs();
      foreach ($items['texts'] as $key => $text) {
        $tabtext->add($key, $html->getinput('editor',
        $section . '_text_' . $key, tadminhtml::specchars($text), $key));
      }
      $tab->add($lang->text, $tabtext->get());
      $tabs->add($section, $tab->get());
    }
    
    return  $html->adminform($tabs->get(), $args);
  }
  
  public function processform() {
    $jsmerger = tjsmerger::i();
    $jsmerger->lock();
    //$jsmerger->items = array();
    //$jsmerger->install();
    foreach (array_keys($jsmerger->items) as $section) {
      $keys = array_keys($jsmerger->items[$section]['texts']);
      $jsmerger->setfiles($section, $_POST[$section . '_files']);
      foreach ($keys as $key) {
        $jsmerger->addtext($section, $key, $_POST[$section . '_text_' . $key]);
      }
    }
    $jsmerger->unlock();
  }
  
}//class