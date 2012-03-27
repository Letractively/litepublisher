<?php
/**
* Lite Publisher
* Copyright (C) 2010, 2012 Vladimir Yushko http://litepublisher.com/
* Dual licensed under the MIT (mit.txt)
* and GPL (gpl.txt) licenses.
**/

class ttickets extends tposts {
public $cats;
  
  public static function i() {
    return getinstance(__class__);
  }
  
  protected function create() {
    parent::create();
    $this->childtable = 'tickets';
$this->addmap('cats', array());
  }
  
  public function newpost() {
    return tticket::i();
  }
  
  public function createpoll() {
    $lang = tlocal::admin('tickets');
    $items = explode(',', $lang->pollitems);
    $polls = tpolls::i();
    return $polls->add('', 'opened', 'button', $items);
  }

  public function filtercats(tpost $post) {
$cats = array_intersect($post->categories, $this->cats);
if (count($cats) == 0) {
$cats = array($this->cats[0]);
} elseif (count($cats) > 1) {
$cats = array($cats[0]);
}
$post->categories = $cats;
}
  
  public function add(tpost $post) {
$this->filtercats($post);
    $post->poll = $this->createpoll();
    $post->updatefiltered();
    //$post->status = 'draft';
    $id = parent::add($post);
    $this->notify($post);
    return $id;
  }
  
  private function notify(tticket $ticket) {
    ttheme::$vars['ticket'] = $ticket;
    $args = targs::i();
    $args->adminurl = litepublisher::$site->url . '/admin/tickets/editor/'. litepublisher::$site->q . 'id=' . $ticket->id;
    $mailtemplate = tmailtemplate::i('tickets');
    $subject = $mailtemplate->subject($args);
    $body = $mailtemplate->body($args);
    tmailer::sendtoadmin($subject, $body);
  }
  
  public function edit(tpost $post) {
$this->filtercats($post);
    $post->updatefiltered();
    return parent::edit($post);
  }
  
  public function postsdeleted(array $items) {
    $deleted = implode(',', $items);
    $db = $this->getdb($this->childtable);
    $idpolls = $db->res2id($db->query("select poll from $db->prefix$this->childtable where (id in ($deleted)) and (poll  > 0)"));
    if (count ($idpolls) > 0) {
      $polls = tpolls::i();
      foreach ($idpolls as $idpoll)       $pols->delete($idpoll);
    }
  }
  
  public function hasright($who, $group) {
    return ($who == 'ticket') &&($group == 'author');
  }
  
  public function onexclude($id) {
    if (litepublisher::$options->group == 'ticket') {
      $admin = tadminmenus::i();
      return $admin->items[$id]['url'] == '/admin/posts/';
    }
    return false;
  }
  
}//class