<?php
/**
* Lite Publisher
* Copyright (C) 2010, 2012 Vladimir Yushko http://litepublisher.com/
* Dual licensed under the MIT (mit.txt)
* and GPL (gpl.txt) licenses.
**/

class tadminuseroptions extends tadminmenu {
  
  public static function i($id = 0) {
    return parent::iteminstance(__class__, $id);
  }
  
  public function getcontent() {
    $result = '';
    $html = $this->html;
    $lang = tlocal::i('users');
    $args = targs::i();
      $args->formtitle = $lang->useroptions;

    $pages = tuserpages::i();
      $args->createpage = $pages->createpage;
      $args->lite = $pages->lite;

    $groups = tusergroups::i();
      $g = array();
      foreach ($groups->items as $id => $item) {
      $g[$item['name']]  = $item['title'];
      }
      
      $args->defaultgroup =tadminhtml::array2combo($g, $groups->defaultgroup);

      $linkgen = tlinkgenerator::i();
      $args->linkschema = $linkgen->data['user'];
      
      return $html->adminform(
      '[combo=defaultgroup]
      [checkbox=createpage]
      [checkbox=lite]
      [text=linkschema]',
      $args);
    }
    
  public function processform() {
    $pages = tuserpages::i();
      $pages->createpage = isset($_POST['createpage']);
      $pages->lite = isset($_POST['lite']);
      $pages->save();

    $groups = tusergroups::i();      
      $groups->defaultgroup = $_POST['defaultgroup'];
      $groups->save();
      
      $linkgen = tlinkgenerator::i();
      $linkgen->data['user'] = $_POST['linkschema'];
      $linkgen->save();
    }
    
}//class