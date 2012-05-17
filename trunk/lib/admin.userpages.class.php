<?php
/**
* Lite Publisher
* Copyright (C) 2010, 2012 Vladimir Yushko http://litepublisher.com/
* Dual licensed under the MIT (mit.txt)
* and GPL (gpl.txt) licenses.
**/

class tadminuserpages extends tadminmenu {
  
  public static function i($id = 0) {
    return parent::iteminstance(__class__, $id);
  }
  
  public function  gethead() {
    return parent::gethead() . tuitabs::gethead();
  }
  
  public function getiduser() {
    if (litepublisher::$options->ingroup('admin')) {
      $id = $this->idget();
    } else {
      $id = litepublisher::$options->user;
    }
    
$users = tusers::i();
    if ($users->itemexists($id) && ('approved' == $users->getvalue($id, 'status'))) return $id;
    return false;
  }
  
  public function getcontent() {
    $result = '';
    $users = tusers::i();
    $html = $this->gethtml('users');
    $lang = tlocal::admin('users');
    $args = targs::i();
    
    if (!($id= $this->getiduser())) {
      if (litepublisher::$options->group == 'admin') return $this->getuserlist();
      return $this->notfound;
    }
    
    $pages = tuserpages::i();
    $item = tusers::i()->getitem($id) + $pages->getitem($id);
    if (!isset($item['url'])) {
      $item['url'] = $item['idurl'] ? litepublisher::$urlmap->getidurl($item['idurl']) : '';
    }
    $args->add($item);
    $args->formtitle = sprintf('<a href="$site.url%s">%s</a>', $item['url'], $item['name']);
    $tabs = new tuitabs();
    $tabs->add($lang->title, '[text=name] [text=website]');
    if ('admin' == litepublisher::$options->group) {
      $tabs->add($lang->view, tadminviews::getcomboview($item['idview']));
      $tabs->add('SEO', '[text=url] [text=keywords] [text=description] [editor=head]');
    }
    $tabs->add($lang->text, '[editor=rawcontent]');
    return $html->adminform($tabs->get(), $args);
  }
  
  public function processform() {
    extract($_POST, EXTR_SKIP);
    if (!($id= $this->getiduser())) return;
    $item = array(
    'rawcontent' => trim($rawcontent),
    'content' => tcontentfilter::i()->filter($rawcontent)
    );
    
    if ('admin' == litepublisher::$options->group) {
      $item['idview'] = (int) $idview;
      $item['url'] = $url;
      $item['head'] = $head;
      $item['keywords'] = $keywords;
      $item['description'] = $description;
    }
    
    $pages = tuserpages::i();
    $pages->edit($id, $item);
    
    tusers::i()->edit($id, array(
    'name' => $name,
    'website' => tcontentfilter::clean_website($website),
    ));
  }
  
  public function getuserlist() {
    $users = tusers::i();
    $perpage = 20;
    $count = $users->count;
    $from = $this->getfrom($perpage, $count);
    if ($users->dbversion) {
      $items = $users->select('', " order by id desc limit $from, $perpage");
      if (!$items) $items = array();
    } else {
      $items = array_slice(array_keys($users->items), $from, $perpage);
    }
    
    $html = $this->gethtml('users');
    $lang = tlocal::admin('users');
    $args = new targs();
    $args->adminurl = $this->adminurl;
    $result = $html->h4->userstable;
    $result .= $html->items2table($users, $items, array(
    array('left', $lang->edit, sprintf('<a href="%s=$id">$name</a>', $this->adminurl))
    ));
    
    $theme = ttheme::i();
    $result .= $theme->getpages($this->url, litepublisher::$urlmap->page, ceil($count/$perpage));
    return $result;
  }
  
}//class