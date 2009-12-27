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
    global $classes;
    return $classes->commentmanager;
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
            $this->manager->delete($id);
    $result .= $this->html->h2->successmoderated;
          break;
          
          case 'hold':
          $this->manager->setstatus(0, $id, 'hold');
        $result .= $this->moderated($id);
          break;
          
          case 'approve':
          $this->manager->setstatus($id, 'approved');
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
break;
      
      case 'authors':
      if (isset($_GET['action'])) {
        $id = $this->idget();
        $action = $_GET['action'];
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
  
  private function reply($id) {
    global $comment;
    $comment = new tcomment($id);
$args = targs::instance();
    $args->adminurl =$this->adminurl . "=$id&action";
$result = $this->html->info($args);
$result .= $this->html->replyform();
return $result;
  }
  
  private function getwherekind($kind) {
    switch ($kind) {
      case 'comments':
      return "status <> 'deleted' and pingback <> true";
      
      case 'hold':
      return "status = 'hold'";
      
      case 'pingback':
      return "status <> 'deleted' and pingback = true";
    }
  }
  
  private function getlist($kind) {
    global $options, $urlmap, $comment;
    $result = '';
    $comments = tcomments::instance(0);
    $perpage = 20;
    // ���������� ���������� ������������ �� ���� �������
    $status = $kind == 'hold' ? 'hold' : 'approved';
    $total = $comments->db->getcount("status = '$status'");
    $from = max(0, $total - $urlmap->page * $perpage);
    $list = $comments->getitems("status = '$status'",  $from, $perpage);
    $html = $this->html;
    $result .= sprintf($html->h2->listhead, $from, $from + count($list), $total);
    $result .= $html->checkallscript;
    $result .= $html->tableheader();
    $args = targs::instance();
    $args->adminurl = $this->adminurl;
    $comment = new tcomment(null);
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
    $result .= $theme->getpages($this->url, $urlmap->page, ceil($total/$perpage));
    return $result;
  }
  
  private function moderated($id) {
    $result = $this->html->h2->successmoderated;
$result .= $this->getinfo($id);
    return $result;
  }
  
  private function getinfo($id) {
    global $comment;
if (!isset($comment)) $comment = new tcomment($id);
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
    global $options;
    $args = targs::instance();
    $args->id = $id;
    $args->action = 'delete';
    $args->adminurl = $options->url . $this->url . $options->q . 'id';
    $args->confirm = $confirm;
    return $this->html->confirmform($args);
  }
  
  private function confirmdeleteauthor($id) {
    return $this->getconfirmform($id, $this->lang->authorconfirmdelete);
  }
  
  private function deleteauthor($uid, $action) {
    $comusers = tcomusers::instance();
    if (!$comusers->itemexists($uid)) return false;
    $manager = $this->manager;
    if (dbversion) {
      $manager->db->delete("author = $uid");
      $comusers->delete($uid);
    } else {
      $manager->lock();
      foreach ($manager->items as $id => $item) {
        if ($uid == $item['uid']) $manager->Delete($id);
      }
      $comusers->delete($uid);
      $manager->unlock();
    }
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
      $author = $comusers->getitem($id);
      $args->id = $id;
      $args->name = $author['name'];
      $args->url = $author['url'];
      $args->email = $author['email'];
      $args->subscribed = $this->getsubscribed($id);
    }
    return $this->html->authorform($args);
  }
  
  private function getauthorslist() {
    global $urlmap;
    $comusers = tcomusers::instance();
    $args = targs::instance();
    $perpage = 20;
    $total = $comusers->count;
    $from = max(0, $total - $urlmap->page * $perpage);
    if (dbversion) {
      $res = $comusers->db->query("select * from $comusers->thistable limit $from, $perpage");
      $items = $res->fetchAll(PDO::FETCH_ASSOC);
    } else {
      $items = array_slice($comusers->items, $from, $perpage, true);
    }
    $html = $this->html;
    $result = sprintf($html->h2->authorlisthead, $from, $from + count($items), $total);
    $result .= $html->authorheader();
    $args->adminurl = $this->adminurl;
    foreach ($items as $id => $item) {
      if (dbversion) {
        $args->id = $item['id'];
      } else {
        $args->id = $id;
        if (is_array($item['ip'])) $ip = implode('; ', $item['ip']);
      }
      $args->name = $item['name'];
  $args->url = $item['url'] == '' ? '' : "<a href=\"{$item['url']}\">{$item['url']}</a>";
  $args->email = $item['email'] == '' ? '' : "<a href=\"mailto:{$item['email']}\">{$item['email']}</a>";
      $result .= $html->authoritem($args);
    }
    $result .= $html->authorfooter;
    
    $theme = ttheme::instance();
    $result .= $theme->getpages($this->url, $urlmap->page, ceil($total/$perpage));
    return $result;
  }
  
  private function getsubscribed($authorid) {
    global $options, $post;
    $authorid = (int) $authorid;
    $comusers = tcomusers::instance();
    if (!$comusers->itemexists($authorid))  return '';
    $html = $this->gethtml('moderator');
    $result = $html->checkallscript;
    $manager = $this->manager;
    if (dbversion) {
      $posted = $manager->db->res2id($manager->db->query("select DISTINCT post from $manager->thistable where author = $author"));
    } else {
      $posted = array();
      foreach ($manager->items as $id => $item) {
        if ($item['uid'] == $authorid) {
          if (!in_array($item['pid'], $posted)) $posted[] =$item['pid'];
        }
      }
    }
    
    $subscribers = tsubscribers::instance();
    $subscribed = $subscribers->getposts($authorid);
    
    $args = targs::instance();
    foreach ($posted as $idpost) {
      $post = tpost::instance($idpost);
      $args->subscribed = in_array($idpost, $subscribed);
      $result .= $html->subscribeitem($args);
    }
    
    return $this->FixCheckall($result);
  }
  
  public function processform() {
    global $options, $urlmap;
    $manager = $this->manager;
    switch ($this->name) {
      case 'comments':
      case 'hold':
      case 'pingback':
      
      $action = $_REQEST['action'];
      switch ($action) {
        case 'reply':
        $email = $this->getadminemail();
        $site = $options->url . $options->home;
        $profile = tprofile::instance();
        $comusers = tccomusers ::instance();
        $authorid = $comusers->add($profile->nick, $email, $site);
        $post = tpost::instance( (int) $_POST['pid']);
        $manager->addcomment($post->id, $authorid, $_POST['content']);
        $posturl = $post->haspages ? rtrim($post->url, '/') . "/page/$post->commentpages/" : $post->url;
        @header("Location: $options->url$posturl");
        exit();
        
        case 'edit':
        $comment = $manager->getcomment($this->idget());
        $comment->content = $_POST['content'];
        break;
        
        default:
        $manager->Lock();
        foreach ($_POST as $id => $value) {
          if (!is_numeric($id))  continue;
          $id = (int) $id;
          $this->doaction($id, $action);
        }
        $manager->unlock();
        $result = $this->html->h2->successmoderated;
      }
      break;
      
      case 'authors':
      if (isset($_REQUEST['action'])  && ($_REQUEST['action'] == 'edit')) {
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
    
    $urlmap->clearcache();
    return $result;
  }
  
  private function getadminemail() {
    global $options;
    $profile = tprofile::instance();
    if ($profile->mbox!= '') return $profile->mbox;
    return $options->fromemail;
  }
  
  private function editcomment($id) {
    $comment = new tcomment($id);
    $args = targs::instance();
    $args->content = $comment->content;
    $args->adminurl =$this->adminurl . "=$id&action";
$result = $this->html->info($args);
$result .= $this->html->editform($args);
return $result;
  }
  
}//class
?>