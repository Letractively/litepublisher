<?php
/**
* Lite Publisher
* Copyright (C) 2010 Vladimir Yushko http://litepublisher.com/
* Dual licensed under the MIT (mit.txt)
* and GPL (gpl.txt) licenses.
**/

interface ipingbacks {
  public function doadd($url, $title);
  public function setstatus($id, $approve);
  public function getcontent();
  public function exists($url);
  public function import($url, $title, $posted, $ip, $status);
}

class tabstractpingbacks extends titems {
  public $pid;
  
  public function add($url, $title) {
    $filter = tcontentfilter::instance();
    $title = $filter->gettitle($title);
    $id = $this->doadd($url, $title);
    $this->added($id);
    $this->sendmail($id);
    return $id;
  }
  
  public function hold($id) {
    return $this->setstatus($id, false);
  }
  
  public function approve($id) {
    return $this->setstatus($id, true);
  }
  
  private function sendmail($id) {
    $item = $this->getitem($id);
    $args = targs::instance();
    $args->add($item);
    $args->id = $id;
    $status = dbversion ? $item['status'] : ($item['approved'] ? 'approved' : 'hold');
    $args->localstatus = tlocal::$data['commentstatus'][$status];
  $args->adminurl = litepublisher::$site->url . '/admin/comments/pingback/'. litepublisher::$site->q . "id=$id&post={$item['post']}&action";
    $post = tpost::instance($item['post']);
    $args->posttitle =$post->title;
    $args->postlink = $post->link;
    
    $mailtemplate = tmailtemplate::instance('comments');
    $subject = $mailtemplate->pingbacksubj($args);
    $body = $mailtemplate->pingbackbody($args);
    
    tmailer::sendmail(litepublisher::$options->name, litepublisher::$options->fromemail,
    'admin', litepublisher::$options->email,  $subject, $body);
    
  }
  
}//class
?>