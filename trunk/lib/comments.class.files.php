<?php
/**
* Lite Publisher
* Copyright (C) 2010 Vladimir Yushko http://litepublisher.com/
* Dual licensed under the MIT (mit.txt)
* and GPL (gpl.txt) licenses.
**/

class tcomments extends titems {
  public $pid;
  private $rawitems;
  private $holditems;
  private static $instances;
  
  public static function instance($pid) {
    $pid = (int) $pid;
    if (!isset(self::$instances)) self::$instances = array();
    if (isset(self::$instances[$pid]))       return self::$instances[$pid];
    $self = litepublisher::$classes->newinstance(__class__);
    self::$instances[$pid]  = $self;
    $self->pid = $pid;
    $self->load();
    return $self;
  }
  
  protected function create() {
    parent::create();
    $this->addevents('edited');
  }
  
  public function getcomment($id) {
    $result = new tcomment($this);
    $result->id = $id;
    return $result;
  }
  
  public function getbasename() {
    return 'posts'.  DIRECTORY_SEPARATOR . $this->pid . DIRECTORY_SEPARATOR . 'comments';
  }
  
  public function getraw() {
    if (!isset($this->rawitems)) {
      $this->rawitems = new trawcomments($this);
    }
    return $this->rawitems;
  }
  
  public function gethold() {
    if (!isset($this->holditems)) {
      $this->holditems = new tholdcomments($this);
    }
    return $this->holditems;
  }
  
  public function getapprovedcount() {
    return count($this->items);
  }
  
  public function add($author, $content, $status) {
    $filter = tcontentfilter::instance();
    $item  = array(
    'author' => $author,
    'posted' => time(),
    'content' => $filter->filtercomment($content)
    );
    
    if ($status == 'approved') {
      $this->items[++$this->autoid] = $item;
    } else {
      $this->hold->items[++$this->autoid] =  $item;
      $this->hold->save();
    }
    $this->save();
    
    $ip = preg_replace( '/[^0-9., ]/', '',$_SERVER['REMOTE_ADDR']);
    $this->raw->add($this->autoid, $content, $ip);
    $this->added($this->autoid);
    return $this->autoid;
  }
  
  public function edit($id, $author, $content) {
    if (isset($this->items[$id])) {
      $item = &$this->items[$id];
      $approved = true;
    } elseif (isset($this->hold->items[$id])) {
      $item = &$this->hold->items[$id];
      $approved = false;
    } else {
      return false;
    }
    
    $filter = tcontentfilter::instance();
    
    $item['author'] = $author;
    $item['content'] = $filter->filtercomment($content);
    
    if ($approved) {
      $this->save();
    } else {
      $this->hold->save();
    }
    
    $this->raw->items[$id]['content'] = $content;
    $this->raw->save();
    $this->edited($id);
    return true;
  }
  
  public function delete($id) {
    if (isset($this->items[$id])) {
      $author = $this->items[$id]['author'];
      unset($this->items[$id]);
      $this->save();
    } else {
      if (!isset($this->hold->items[$id])) return false;
      $author = $this->hold->items[$id]['author'];
      unset($this->hold->items[$id]);
      $this->hold->save();
    }
    
    $this->raw->delete($id);
    $this->deleteauthor($author);
    $this->deleted($id);
    return true;
  }
  
  public function deleteauthor($author) {
    foreach ($this->items as $id => $item) {
      if ($author == $item['author'])  return;
    }
    
    foreach ($this->hold->items as $id => $item) {
      if ($author == $item['author'])  return;
    }
    
    //������ �� �����
    $comusers = tcomusers::instance($this->pid);
    $comusers->delete($author);
  }
  
  public function setstatus($id, $status) {
    if ($status == 'approved') {
      return $this->approve($id);
    } else {
      return $this->sethold($id);
    }
  }
  
  public function sethold($id) {
    if (!isset($this->items[$id]))  return false;
    $item = $this->items[$id];
    unset($this->items[$id]);
    $this->save();
    $this->hold->items[$id] = $item;
    $this->hold->save();
    return true;
  }
  
  public function approve($id) {
    if (!isset($this->hold->items[$id]))  return false;
    $this->items[$id] = $this->hold->items[$id];
    $this->save();
    unset($this->hold->items[$id]);
    $this->hold->save();
    return true;
  }
  
  /*
  public function sort() {
    $Result[$id] = $item['posted'];
    asort($Result);
    return  array_keys($Result);
  }
  
  public function insert($author, $content, $ip, $posted, $status) {
    $filter = tcontentfilter::instance();
    $item  = array(
    'author' => $author,
    'posted' => $posted,
    'content' => $filter->filtercomment($content)
    );
    
    if ($status == 'approved') {
      $this->items[++$this->autoid] = $item;
    } else {
      $this->hold->items[++$this->autoid] =  $item;
      $this->hold->save();
    }
    $this->save();
    
    $this->raw->add($this->autoid, $content, $ip);
    return $this->autoid;
  }
  
  */
  
  public function getholdcontent($idauthor) {
    if (litepublisher::$options->admincookie) return '';
    return $this->hold->dogetcontent(true, $idauthor);
  }
  
  public function getmoderator() {
    if (!litepublisher::$options->admincookie) return false;
    if (litepublisher::$options->group == 'admin') return true;
    $groups = tusergroups::instance();
    return $groups->hasrigt(litepublisher::$options->group, 'moderator');
  }
  
  public function getcontent() {
    $result = $this->dogetcontent(false, 0);
    if (!$this->moderator) return $result;
    $theme = ttheme::instance();
    tlocal::loadlang('admin');
    $lang = tlocal::instance('comment');
    $result .= $theme->parse($theme->content->post->templatecomments->comments->hold);
    $post = tpost::instance($this->pid);
    if ($post->commentpages == litepublisher::$urlmap->page) {
      $result .= $this->hold->dogetcontent(true, 0);
    } else {
      //�������� ������ ������ �����������
      $commentsid = $theme->content->post->templatecomments->comments->commentsid;
      $tml = $theme->content->post->templatecomments->comments->__tostring();
      $tml = str_replace("id=\"$commentsid\"", "id=\"hold$commentsid\"", $tml);
      $tml = str_replace('<a name="comments"', '<a name="holdcomments"', $tml);
      $result .= sprintf($tml, '', 1);
    }
    $args = targs::instance();
    $args->comments = $result;
    $result = $theme->parsearg($theme->content->post->templatecomments->moderateform, $args);
    return $result;
  }
  
  public function dogetcontent($hold, $idauthor) {
    $result = '';
    $from = 0;
    $items = array_keys($this->items);
    if (!$hold) {
      if (litepublisher::$options->commentpages ) {
        $from = (litepublisher::$urlmap->page - 1) * litepublisher::$options->commentsperpage;
        $items = array_slice($items, $from, litepublisher::$options->commentsperpage, true);
      }
    }
    
    $ismoder = $this->moderator;
    $theme = ttheme::instance();
    if (count($items) > 0) {
      $args = targs::instance();
      $args->from = $from;
      $comment = new TComment($this);
      ttheme::$vars['comment'] = $comment;
      if ($hold) $comment->status = 'hold';
      $lang = tlocal::instance('comment');
      
      if ($ismoder) {
        tlocal::loadlang('admin');
        $moderate =$theme->content->post->templatecomments->comments->comment->moderate;
      } else {
        $moderate = '';
      }
      $tml = str_replace('$moderate', $moderate, $theme->content->post->templatecomments->comments->comment);
      
      $i = 1;
      $class1 = $theme->content->post->templatecomments->comments->comment->class1;
      $class2 = $theme->content->post->templatecomments->comments->comment->class2;
      foreach ($items as $id) {
        //��������� � ����� ����� ���������� � ����������� ��������
        if (!litepublisher::$options->admincookie && $hold) {
          if ($idauthor != $this->items[$id]['author']) continue;
        }
        $comment->id = $id;
        $args->class = (++$i % 2) == 0 ? $class1 : $class2;
        $result .= $theme->parsearg($tml, $args);
      }
    }//if count
    $tml = $theme->content->post->templatecomments->comments->__tostring();
    if ($hold) {
      $tml = str_replace('<a name="comments"', '<a name="holdcomments"', $tml);
      $commentsid = $theme->content->post->templatecomments->comments->commentsid;
      $tml = str_replace("id=\"$commentsid\"", "id=\"hold$commentsid\"", $tml);
    }
    
    if (!$ismoder) {
      if ($result == '') return '';
    }
    return sprintf($tml, $result, $from + 1);
  }
  
}//class

class tholdcomments extends tcomments {
  public $owner;
  public $idauthor;
  
  public static function instance($pid) {
    $owner = tcomments::instance($pid);
    return $owner->hold;
  }
  
  public function __construct($owner) {
    $this->owner = $owner;
    parent::__construct();
    $this->pid = $owner->pid;
  }
  
  public function getbasename() {
    return $this->owner->getbasename() . '.hold';
  }
  
  public function delete($id) {
    if (!isset($this->items[$id])) return false;
    $author= $this->items[$id]['author'];
    unset($this->items[$id]);
    $this->save();
    $this->owner->raw->delete($id);
    $this->owner->deleteauthor($author);
    $this->deleted($id);
  }
  
}//class

class trawcomments extends titems {
  public $owner;
  
  public function getbasename() {
    return 'posts'.  DIRECTORY_SEPARATOR . $this->owner->pid . DIRECTORY_SEPARATOR . 'comments.raw';
  }
  
  public function __construct($owner) {
    $this->owner = $owner;
    parent::__construct();
  }
  
  public function add($id, $content, $ip) {
    $this->items[$id] = array(
    'content' => $content,
    'ip' => $ip
    );
    $this->save();
  }
  
}//class

//wrapper for simple acces to single comment
class TComment {
  public $id;
  public $owner;
  public $status;
  
  public function __construct($owner = null) {
    $this->owner = $owner;
    $this->status = 'approved';
  }
  
  public function __get($name) {
    if (method_exists($this,$get = "get$name")) {
      return  $this->$get();
    }
    return $this->owner->items[$this->id][$name];
  }
  
  public function __set($name, $value) {
    if ($name == 'content') {
      $this->setcontent($value);
    } else {
      $this->owner->items[$this->id][$name] = $value;
    }
  }
  
  public function save() {
    $this->owner->save();
  }
  
  private function setcontent($value) {
    $filter = tcontentfilter::instance();
    $this->owner->items[$this->id]['content'] = $filter->filtercomment($value);
    $this->save();
    $this->owner->raw->items[$this->id]['content'] =  $value;
    $this->owner->raw->save();
  }
  
  private function getauthoritem() {
    $comusers = tcomusers::instance($this->owner->pid);
    return  $comusers->getitem($this->author);
  }
  
  public function getname() {
    return $this->authoritem['name'];
  }
  
  public function getemail() {
    return $this->authoritem['email'];
  }
  
  public function getwebsite() {
    return $this->authoritem['url'];
  }
  
  public function getauthorlink() {
    $idpost = $this->owner->pid;
    $comusers = tcomusers::instance($idpost);
    $item = $comusers->getitem($this->author);
    $name = $item['name'];
    $url = $item['url'];
    $manager = tcommentmanager::instance();
    if ($manager->hidelink || empty($url)) return $name;
    $rel = $manager->nofollow ? 'rel="nofollow noindex"' : '';
    if ($manager->redir) {
      return "<a $rel href=\"" . litepublisher::$options->url . "/comusers.htm" . litepublisher::$options->q . "id=$this->author&post=$idpost\">$name</a>";
    } else {
      return "<a $rel href=\"$url\">$name</a>";
    }
  }
  
  public function getdate() {
    $theme = ttheme::instance();
    return TLocal::date($this->posted, $theme->comment->dateformat);
  }
  
  public function getlocalstatus() {
    return tlocal::$data['commentstatus'][$this->status];
  }
  
  public function  gettime() {
    return date('H:i', $this->posted);
  }
  
  public function geturl() {
    $post = tpost::instance($this->owner->pid);
    return "$post->link#comment-$this->id";
  }
  
  public function getposttitle() {
    $post = tpost::instance($this->owner->pid);
    return $post->title;
  }
  
  public function getrawcontent() {
    return $this->owner->raw->items[$this->id]['content'];
  }
  
  public function getip() {
    return $this->owner->raw->items[$this->id]['ip'];
  }
  
}//class

?>