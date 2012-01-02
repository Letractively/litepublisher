<?php
/**
* Lite Publisher
* Copyright (C) 2010, 2012 Vladimir Yushko http://litepublisher.com/
* Dual licensed under the MIT (mit.txt)
* and GPL (gpl.txt) licenses.
**/

class tadminusernews {
  
  public static function i() {
    return getinstance(__class__);
  }
  
  public function getcontent() {
    $plugin = tusernews::i();
    $about = tplugins::getabout(tplugins::getname(__file__));
    $args = targs::i();
    $form = '';
    foreach (array('_changeposts', '_canupload', '_candeletefile', 'autosubscribe') as $name) {
      $args->$name = $plugin->data[$name];
      $args->data["\$lang.$name"] = $about[$name];
      $form .= "[checkbox=$name]";
    }
    
    foreach (array('sourcetml') as $name) {
      $args->$name = $plugin->data[$name];
      $args->data["\$lang.$name"] = $about[$name . 'label'];
      $form .= "[text=$name]";
    }
    
    $args->formtitle = $about['formtitle'];
    $html = tadminhtml::i();
    return $html->adminform($form, $args);
  }
  
  public function processform() {
    $plugin = tusernews::i();
    foreach (array('_changeposts', '_canupload', '_candeletefile', 'autosubscribe') as $name) {
      $plugin->data[$name] = isset($_POST[$name]);
    }
    foreach (array('sourcetml') as $name) {
      $plugin->data[$name] = $_POST[$name];
    }
    $plugin->save();
  }
  
}//class