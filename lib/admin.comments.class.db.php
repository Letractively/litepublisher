<?php
/**
* Lite Publisher
* Copyright (C) 2010 Vladimir Yushko http://litepublisher.com/
* Dual licensed under the MIT (mit.txt)
* and GPL (gpl.txt) licenses.
**/

class tadminmoderator extends tadminmenu {
  private $user;
  
  public static function instance() {
    return getinstance(__class__);
  }
  
  protected function getmanager() {
    return litepublisher::$classes->commentmanager;
  }
  
  public function getcontent() {
    $result = '';
    switch ($this->name) {
      case 'comments':
      case 'hold':
      
      if ($action = $this->action) {
        $id = $this->idget();
        $comments = tcomments::instance();
        if (!$comments->itemexists($id)) return $this->notfound;
        switch($action) {
          case 'delete':
          if(!$this->confirmed) return $this->confirmdelete($id);
          $this->manager->delete($id, 0);
          $result .= $this->html->h2->successmoderated;
          break;
          
          case 'hold':
          $this->manager->setstatus($id, 0, 'hold');
          $result .= $this->moderated($id);
          break;
          
          case 'approve':
          $this->manager->setstatus($id, 0, 'approved');
          $result .= $this->moderated($id);
          break;
          
          case 'edit':
          $result .= $this->editcomment($id);
          break;
          
          case 'reply':
          $result .= $this->reply($id);
          break;
        }
      }
      
      $result .= $this->getlist($this->name);
      return $result;
      
      case 'pingback':
      if ($action = $this->action) {
        $id = $this->idget();
        $pingbacks = tpingbacks::instance();
        if (!$pingbacks->itemexists($id)) return $this->notfound;
        switch($action) {
          case 'delete':
          if(!$this->confirmed) return $this->confirmdelete($id);
          $pingbacks->delete($id);
          $result .= $this->html->h2->successmoderated;
          break;
          
          case 'hold':
          $pingbacks->setstatus($id, false);
          $result .= $this->html->h2->successmoderated;
          break;
          
          case 'approve':
          $pingbacks->setstatus($id, true);
          $result .= $this->html->h2->successmoderated;
          break;
          
          case 'edit':
          $result .= $this->editpingback($id);
          break;
        }
      }
      $result .= $this->getpingbackslist();
      return $result;
      
      case 'authors':
      if ($action = $this->action) {
        $id = $this->idget();
        switch ($action) {
          case 'delete':
          if (!$this->confirmed) return $this->confirmdeleteauthor($id);
          if (!$this->deleteauthor($id)) return $this->notfount;
          $result .= $this->html->h2->authordeleted;
          break;
          
          case 'edit':
          $result .= $this->editauthor($id);
        }
      } else {
        $result .= $this->editauthor(0);
      }
      
      $result .= $this->getauthorslist();
      return $result;
    }
    
  }
  
  private function editcomment($id) {
    $comment = new tcomment($id);
ttheme::$vars['comment'] = $comment;
    $args = targs::instance();
    $args->content = $comment->content;
    $args->adminurl =$this->adminurl . "=$id&action";
    $result = $this->html->info($args);
    $result .= $this->html->editform($args);
    return $result;
  }
  
  private function reply($id) {
    $comment = new tcomment($id);
ttheme::$vars['comment'] = $comment;
    $args = targs::instance();
    $args->adminurl =$this->adminurl . "=$id&action";
    $result = $this->html->info($args);
    $result .= $this->html->replyform();
    return $result;
  }
  
  private function getlist($kind) {
    $result = '';
    $comments = tcomments::instance(0);
    $perpage = 20;
    // ���������� ���������� ������������ �� ���� �������
    $status = $kind == 'hold' ? 'hold' : 'approved';
    $total = $comments->db->getcount("status = '$status'");
    $from = max(0, $total - litepublisher::$urlmap->page * $perpage);
    $list = $comments->getitems("status = '$status'
    order by $comments->thistable.posted asc limit $from, $perpage");
    
    $html = $this->html;
    $result .= sprintf($html->h2->listhead, $from, $from + count($list), $total);
    $result .= $html->checkallscript;
    $result .= $html->tableheader();
    $args = targs::instance();
    $args->adminurl = $this->adminurl;
    $comment = new tcomment(null);
ttheme::$vars['comment'] = $comment;
    foreach ($list as $data) {
      $comment->data = $data;
      $args->id = $comment->id;
      $args->excerpt = tcontentfilter::getexcerpt($comment->content, 120);
      $args->onhold = $comment->status == 'hold';
      $args->email = $comment->email == '' ? '' : "<a href='mailto:$comment->email'>$comment->email</a>";
      $args->website =$comment->website == '' ? '' : "<a href='$comment->website'>$comment->website</a>";
      $result .=$html->itemlist($args);
    }
    $result .= $html->tablefooter();
    $result = $this->FixCheckall($result);
    
    $theme = ttheme::instance();
    $result .= $theme->getpages($this->url, litepublisher::$urlmap->page, ceil($total/$perpage));
    return $result;
  }
  
  private function getpingbackslist() {
    $result = '';
    $pingbacks = tpingbacks::instance();
    $perpage = 20;
    $total = $pingbacks->getcount();
    $from = max(0, $total - litepublisher::$urlmap->page * $perpage);
    $items = $pingbacks->db->getitems("status <> 'deleted' order by posted limit $from, $perpage");
    $html = $this->html;
    $result .= sprintf($html->h2->pingbackhead, $from, $from + count($items), $total);
    $result .= $html->checkallscript;
    $result .= $html->pingbackheader();
    $args = targs::instance();
    $args->adminurl = $this->adminurl;
    foreach ($items as $item) {
      $args->add($item);
      
      $args->idpost = $item['post'];
      unset($args->data['$post']);
      $args->website = sprintf("<a href='%s'>%s</a>", $item['url']);
      $args->localstatus = tlocal::$data['commentstatus'][$item['status']];
      $args->date = tlocal::date(strtotime($item['posted']));
      $post = tpost::instance($item['post']);
ttheme::$vars['post'] = $post;
      $args->posttitle =$post->title;
      $args->postlink = $post->link;
      $result .=$html->pingbackitem($args);
    }
    $result .= $html->tablefooter();
    $result = $this->FixCheckall($result);
    
    $theme = ttheme::instance();
    $result .= $theme->getpages($this->url, litepublisher::$urlmap->page, ceil($total/$perpage));
    return $result;
  }
  
  private function editpingback($id) {
    $pingbacks = tpingbacks::instance();
    $args = targs::instance();
    $args->add($pingbacks->getitem($id));
    return $this->html->pingbackedit($args);
  }
  
  private function moderated($id) {
    $result = $this->html->h2->successmoderated;
    $result .= $this->getinfo($id);
    return $result;
  }
  
  private function getinfo($id) {
if (!isset(ttheme::$vars['comment'])) ttheme::$vars['comment'] = new tcomment($id);
    $args = targs::instance();
    $args->adminurl =$this->adminurl . "=$id&action";
    return $this->html->info($args);
  }
  
  private function confirmdelete($id) {
    $result = $this->getconfirmform($id, $this->lang->confirmdelete);
    $result .= $this->getinfo($id);
    return $result;
  }
  
  private function getconfirmform($id, $confirm) {
    $args = targs::instance();
    $args->id = $id;
    $args->action = 'delete';
    $args->adminurl = litepublisher::$options->url . $this->url . litepublisher::$options->q . 'id';
    $args->confirm = $confirm;
    return $this->html->confirmform($args);
  }
  
  private function confirmdeleteauthor($id) {
    return $this->getconfirmform($id, $this->lang->authorconfirmdelete);
  }
  
  private function deleteauthor($uid) {
    $comusers = tcomusers::instance();
    if (!$comusers->itemexists($uid)) return false;
    $comments = tcomments::instance();
    $comments->db->delete("author = $uid");
    $comusers->delete($uid);
    return true;
  }
  
  private function editauthor($id) {
    $args = targs::instance();
    if ($id == 0) {
      $args->id = 0;
      $args->name = '';
      $args->email = '';
      $args->url = '';
      $args->subscribed = '';
    } else {
      $comusers = tcomusers::instance();
      if (!$comusers->itemexists($id)) return $this->notfound;
      $args->add($comusers->getitem($id));
      $args->subscribed = $this->getsubscribed($id);
    }
    return $this->html->authorform($args);
  }
  
  private function getauthorslist() {
    $comusers = tcomusers::instance();
    $args = targs::instance();
    $perpage = 20;
    $total = $comusers->count;
    $from = max(0, $total - litepublisher::$urlmap->page * $perpage);
    $res = $comusers->db->query("select * from $comusers->thistable limit $from, $perpage");
    $items = $res->fetchAll(PDO::FETCH_ASSOC);
    $html = $this->html;
    $result = sprintf($html->h2->authorlisthead, $from, $from + count($items), $total);
    $result .= $html->authorheader();
    $args->adminurl = $this->adminurl;
    foreach ($items as $id => $item) {
      $args->add($item);
      $result .= $html->authoritem($args);
    }
    $result .= $html->authorfooter;
    
    $theme = ttheme::instance();
    $result .= $theme->getpages($this->url, litepublisher::$urlmap->page, ceil($total/$perpage));
    return $result;
  }
  
  private function getsubscribed($authorid) {
$db = litepublisher::$db;
    $authorid = (int) $authorid;
    $comusers = tcomusers::instance();
    if (!$comusers->itemexists($authorid))  return '';
    $html = $this->gethtml('moderator');
    $result = $html->checkallscript;
    $res = $db->query("select $db->posts.id as id, $db->posts.title as title, $db->urlmap.url as url
    from $db->posts, $db->urlmap
    where $db->posts.id in (select DISTINCT $db->comments.post from $db->comments where author = $authorid)
    and $db->urlmap.id = $db->posts.idurl
    order by $db->posts.posted desc");
    $items = $res->fetchAll(PDO::FETCH_ASSOC);        $args = targs::instance();
    
    $subscribers = tsubscribers::instance();
    $subscribed = $subscribers->getposts($authorid);
    
    foreach ($items as $item) {
      $args->add($item);
      $args->subscribed = in_array($item['id'], $subscribed);
      $result .= $html->subscribeitem($args);
    }
    
    return $this->FixCheckall($result);
  }
  
  public function processform() {
    switch ($this->name) {
      case 'comments':
      case 'hold':
      
      if (isset($_REQUEST['action'])) {
        switch ($_REQUEST['action']) {
          case 'reply':
          $post = tpost::instance( (int) $_REQUEST['post']);
          $this->manager->reply($this->idget(), $post->id, $_POST['content']);
          @header("Location: litepublisher::$options->url$post->lastcommenturl");
          exit();
          
          case 'edit':
          $comments = tcomments::instance();
          $comment = $comments->getcomment($this->idget);
          $comment->content = $_POST['content'];
          break;
        }
      } else {
        $manager = $this->manager;
        $status = isset($_POST['approve']) ? 'approve' : (isset($_POST['hold']) ? 'hold' : 'delete');
        foreach ($_POST as $id => $value) {
          if (!is_numeric($id))  continue;
          $id = (int) $id;
          if ($status == 'delete') {
            $manager->delete($id);
          } else {
            $manager->setstatus($id, 0, $status);
          }
        }
      }
      $result = $this->html->h2->successmoderated;
      break;
      
      case 'pingback':
      $pingbacks = tpingbacks::instance();
      if (isset($_REQUEST['action']) && $_REQUEST['action'] == 'edit') {
        extract($_POST);
        $pingbacks->edit($this->idget(), $title, $url);
      } else {
        $status = isset($_POST['approve']) ? 'approve' : (isset($_POST['hold']) ? 'hold' : 'delete');
        foreach ($_POST as $id => $value) {
          if (!is_numeric($id))  continue;
          $id = (int) $id;
          if ($status == 'delete') {
            $pingbacks->delete($id);
          } else {
            $pingbacks->setstatus($id, $status == 'approve');
          }
        }
      }
      $result = $this->html->h2->successmoderated;
      break;
      
      case 'authors':
      if (isset($_REQUEST['action']) && $_REQUEST['action'] == 'edit') {
        $id = $this->idget();
        $comusers = tcomusers::instance();
        if (!$comusers->itemexists($id)) return $this->notfound;
        $comusers->edit($id, $_POST['name'], $_POST['url'], $_POST['email'], $_POST['ip']);
        $subscribers = tsubscribers::instance();
        $subscribed = $subscribers->getposts($id);
        $checked = array();
        foreach ($_POST as $idpost => $value) {
          if (!is_numeric($idpost))  continue;
          $checked [] = $idpost;
        }
        $unsub = array_diff($subscribed, $checked);
        if (count($unsub) > 0) {
          $subscribers->lock();
          foreach ($unsub as $idpost) {
            $subscribers->delete($idpost, $id);
          }
          $subscribers->unlock();
        }
        
        $result =  $html->h2->authoredited;
      }
      break;
    }
    
    litepublisher::$urlmap->clearcache();
    return $result;
  }
  
}//class
?>