<?php
/**
* Lite Publisher
* Copyright (C) 2010, 2011 Vladimir Yushko http://litepublisher.com/
* Dual licensed under the MIT (mit.txt)
* and GPL (gpl.txt) licenses.
**/

class tadmintags extends tadminmenu {
  
  public static function i($id = 0) {
    return parent::iteminstance(__class__, $id);
  }
  
  public function gethead() {
    $result = parent::gethead();
    
    $template = ttemplate::i();
    $template->ltoptions['lang'] = litepublisher::$options->language ;
  $result .= $template->getready('$("#tabs").tabs({ cache: true });');
    $ajax = tajaxtageditor::i();
    return $ajax->dogethead($result);
  }
  
  public function getcontent() {
    $result = '';
    $istags = $this->name == 'tags';
    $tags = $istags  ? litepublisher::$classes->tags : litepublisher::$classes->categories;
    if (dbversion) $tags->loadall();
    $parents = array(0 => '-----');
    foreach ($tags->items as $id => $item) {
      $parents[$id] = $item['title'];
    }
    
    $this->basename = 'tags';
    $html = $this->html;
    $lang = tlocal::i('tags');
    $id = $this->idget();
    $args = targs::i();
    $args->id = $id;
    $args->adminurl = $this->adminurl;
    $args->ajax = tadminhtml::getadminlink('/admin/ajaxtageditor.htm', sprintf('id=%d&type=%s&get', $id, $istags  ? 'tags' : 'categories'));
    
    if (isset($_GET['action']) && ($_GET['action'] == 'delete') && $tags->itemexists($id)) {
      if  ($this->confirmed) {
        $tags->delete($id);
        $result .= $html->h2->successdeleted;
      } else {
        return $html->confirmdelete($id, $this->adminurl, $lang->confirmdelete);
      }
    }
    
    if ($id ==  0) {
      $result .= $html->togglehead();
      $result .= $html->addscript;
      $args->title = '';
      $args->parent = tadminhtml::array2combo($parents, 0);
      $args->order = tadminhtml::array2combo(range(0,9), 1);
      $result .= $html->form($args);
    } elseif ($tags->itemexists($id)) {
      $item = $tags->getitem($id);
      $args->add($item);
      $args->parent = tadminhtml::array2combo($parents, $item['parent']);
      $args->order = tadminhtml::array2combo(range(0,9), $item['customorder']);
      $result .= $html->form($args);
    }
    
    //table
    $perpage = 20;
    $count = $tags->count;
    $from = $this->getfrom($perpage, $count);
    
    $items = array_slice($tags->items, $from, $perpage);
    foreach ($items as &$item) {
      $item['parentname'] = $parents[$item['parent']];
    }
    $result .= $html->buildtable($items, array(
    array('right', $lang->count2, '$itemscount'),
    array('left', $lang->parent, '$parentname'),
    array('right', $lang->order, '$customorder'),
    array('left', $lang->title,'<a href="$link" title="$title">$title</a>'),
    array('center', $lang->edit, "<a href=\"$this->adminurl=\$id\">$lang->edit</a>"),
    array('center', $lang->delete, "<a href=\"$this->adminurl=\$id&action=delete\">$lang->delete</a>")
    ));
    $result = $html->fixquote($result);
    $theme = ttheme::i();
    $result .= $theme->getpages($this->url, litepublisher::$urlmap->page, ceil($count/$perpage));
    return $result;
  }
  
  public function processform() {
    if (empty($_POST['title'])) return '';
    extract($_POST, EXTR_SKIP);
    $istags = $this->name == 'tags';
    $tags = $istags  ? litepublisher::$classes->tags : litepublisher::$classes->categories;
    $tags->lock();
    $id = $this->idget();
    if ($id == 0) {
      $id = $tags->add((int) $parent, $title);
      if (isset($order)) $tags->setvalue($id, 'customorder', (int) $order);
      if (isset($url)) $tags->edit($id, $title, $url);
      if (isset($idview)) $tags->setvalue($id, 'idview', (int) $idview);
      if (isset($icon)) $tags->setvalue($id, 'icon', (int) $icon);
    } else {
      $item = $tags->getitem($id);
      $item['title'] = $title;
      if (isset($parent)) $item['parent'] = (int) $parent;
      if (isset($order)) $item['customorder'] = (int) $order;
      if (isset($idview)) $item['idview'] = (int) $idview;
      if (isset($icon)) $item['icon'] = (int) $icon;
      $tags->items[$id] = $item;
      if (!empty($url) && ($url != $item['url'])) $tags->edit($id, $title, $url);
      unset($item['url']);
      if (dbversion) $tags->db->updateassoc($item);
    }
    
    if (isset($raw) || isset($keywords)) {
      $item = $tags->contents->getitem($id);
      if (isset($raw)) {
        $filter = tcontentfilter::i();
        $item['rawcontent'] = $raw;
        $item['content'] = $filter->filter($raw);
      }
      if (isset($keywords)) {
        $item['keywords'] = $keywords;
        $item['description'] = $description;
      }
      $tags->contents->setitem($id, $item);
    }
    
    $tags->unlock();
    return sprintf($this->html->h2->success, $title);
  }
  
}//class


?>