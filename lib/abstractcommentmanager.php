<?php

abstract class TAbstractCommentManager extends TItems {
//template
abstract   public function GetWidgetContent($id);
abstract   public function PostDeleted($postid);

//manager
  abstract   public function getcomment($id);
abstract   public function add($postid, $name, $email, $url, $content);
abstract   public function addcomment($postid, $author, $content);
abstract   public function addpingback(&$post, $url, $title);
abstract   public function delete($id);
abstract   public function setstatus($id, $value);
abstract   public function Getholditems();


  protected function create() {
    parent::create();
$this->table = 'comments';
$this->rawtable = 'rawcomments';
    $this->basename = 'commentmanager';
    $this->AddEvents('edited', 'changed', 'approved');
  }

  protected function CreateStatus($authorid, $content) {
    global $options, $classes;
    if ($options->DefaultCommentStatus == 'approved') return 'approved';
    if ($classes->spamfilter->AuthorHasApproved($authorid)) return  'approved';
    return 'hold';
  }
  
   
 protected function DoAdded($id) {
    $this->DoChanged($this->items[$id]['pid']);
    $this->CommentAdded($id);
    $this->Added($id);
  }
  
  public function DoChanged($postid) {
    ttemplate::WidgetExpired($this);
    
    $post = tpost::instance($postid);
    $urlmap = turlmap::instance();
    $urlmap->setexpired($post->idurl);
    
    $this->changed($postid);
  }
  
  public function CommentAdded($id) {
    global $options;
    if (!$this->options->SendNotification) return;
    $comment = $this->getcomment($id);
    $html = THtmlResource::instance();
    $html->section = 'moderator';
    $lang = tlocal::instance();
    eval('$subject = "' . $html->subject . '";');
    eval('$body = "'. $html->body . '";');
    tmailer::sendmail($options->name, $options->fromemail,
    'admin', $options->email,  $subject, $body);
  }
  
}//class

?>