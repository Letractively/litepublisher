<?php
/**
* Lite Publisher
* Copyright (C) 2010, 2012 Vladimir Yushko http://litepublisher.com/
* Dual licensed under the MIT (mit.txt)
* and GPL (gpl.txt) licenses.
**/

class tcomments extends titems {
  public $rawtable;
  private $pid;
  
  public static function i($pid = 0) {
    $result = getinstance(__class__);
    if ($pid > 0) $result->pid = $pid;
    return $result;
  }
  
  protected function create() {
    $this->dbversion = true;
    parent::create();
    $this->table = 'comments';
    $this->rawtable = 'rawcomments';
    $this->basename = 'comments';
    $this->addevents('edited', 'onstatus', 'changed', 'onapproved');
    $this->pid = 0;
  }
  
  public function add($idpost, $idauthor, $content, $status, $ip) {
    if ($idauthor == 0) $this->error('Author id = 0');
    $filter = tcontentfilter::i();
    $filtered = $filter->filtercomment($content);
    
    $item = array(
    'post' => $idpost,
    'parent' => 0,
    'author' => (int) $idauthor,
    'posted' => sqldate(),
    'content' =>$filtered,
    'status' => $status
    );
    
    $id = (int) $this->db->add($item);
    $item['id'] = $id;
    $item['rawcontent'] = $content;
    $this->items[$id] = $item;
    
    $this->getdb($this->rawtable)->add(array(
    'id' => $id,
    'created' => sqldate(),
    'modified' => sqldate(),
    'ip' => $ip,
    'rawcontent' => $content,
    'hash' => basemd5($content)
    ));
    
    $this->added($id);
    $this->changed($id);
    return $id;
  }
  
  public function edit($id, $content) {
    if (!$this->itemexists($id)) return false;
    $filtered = tcontentfilter::i()->filtercomment($content);
    $this->db->setvalue($id, 'content', $filtered);
    $this->getdb($this->rawtable)->updateassoc(array(
    'id' => $id,
    'modified' => sqldate(),
    'rawcontent' => $content,
    'hash' => basemd5($content)
    ));
    
    if (isset($this->items[$id])) {
      $this->items[$id]['content'] = $filtered;
      $this->items[$id]['rawcontent'] = $content;
    }
    
    $this->edited($id);
    $this->changed($id);
    return true;
  }
  
  public function delete($id) {
if (!$this->itemexists($id)) return false;
$this->db->setvalue($id, 'status', 'deleted');
    $this->deleted($id);
    $this->changed($id);
return true;
  }

  public function setstatus($id, $status) {
    if (!in_array($status, array('approved', 'hold', 'spam')))  return false;
if (!$this->itemexists($id)) return false;
$old = $this->getvalue($id, 'status');
if ($old != $status) {
$this->setvalue($id, 'status', $status);
    $this->onstatus($id, $old, $status);
    $this->changed($id);
if (($old == 'hold') && ($status == 'approved')) $this->onapproved($id);
    return true;
}
return false;
  }
  
    public function postdeleted($idpost) {
    $this->db->update("status = 'deleted'", "post = $idpost");
  }

  public function getcomment($id) {
    return new tcomment($id);
  }
  
  public function getcount($where = '') {
    return $this->db->getcount($where);
  }
  
  public function select($where, $limit) {
    if ($where != '') $where .= ' and ';
    $table = $this->thistable;
    $authors = litepublisher::$db->users;
    $res = litepublisher::$db->query("select $table.*, $authors.name, $authors.email, $authors.website, $authors.trust from $table, $authors
    where $where $authors.id = $table.author $limit");
    
    return $this->res2items($res);
  }
  
  public function getraw() {
    return $this->getdb($this->rawtable);
  }
  
  public function getapprovedcount() {
    return $this->db->getcount("post = $this->pid and status = 'approved'");
  }
  
  //uses in import functions
  public function insert($idauthor, $content, $ip, $posted, $status) {
    $filtered = tcontentfilter::i()->filtercomment($content);
    $item = array(
    'post' => $this->pid,
    'parent' => 0,
    'author' => $idauthor,
    'posted' => sqldate($posted),
    'content' =>$filtered,
    'status' => $status
    );
    
    $id =$this->db->add($item);
    $item['rawcontent'] = $content;
    $this->items[$id] = $item;
    
    $this->getdb($this->rawtable)->add(array(
    'id' => $id,
    'created' => sqldate($posted),
    'modified' => sqldate(),
    'ip' => $ip,
    'rawcontent' => $content,
    'hash' => basemd5($content)
    ));
    
    return $id;
  }
  
  public function getmoderator() {
    return litepublisher::$options->ingroup('moderator');
  }
  
  public function getcontent() {
    $result = $this->getcontentwhere('approved', '');
    if (!$this->moderator) return $result;
    $post = tpost::i($this->pid);
    $theme = $post->theme;
    tlocal::usefile('admin');
    $args = new targs();
    if ($post->commentpages == litepublisher::$urlmap->page) {
      $result .= $this->getcontentwhere('hold', '');
    } else {
      //add empty list of hold comments
      $args->comment = '';
      $result .= $theme->parsearg($theme->templates['content.post.templatecomments.holdcomments'], $args);
    }
    
    return $result;
    
    /*
    $args->comments = $result;
    return $theme->parsearg($theme->templates['content.post.templatecomments.moderateform'], $args);
    */
  }
  
  public function getholdcontent($idauthor) {
    if (litepublisher::$options->admincookie) return '';
    return $this->getcontentwhere('hold', "and $this->thistable.author = $idauthor");
  }
  
  private function getcontentwhere($status, $whereauthor) {
    $result = '';
    $post = tpost::i($this->pid);
    $theme = $post->theme;
    if ($status == 'approved') {
      if (litepublisher::$options->commentpages ) {
        $page = litepublisher::$urlmap->page;
        if (litepublisher::$options->comments_invert_order) $page = max(0, $post->commentpages  - $page) + 1;
        $count = litepublisher::$options->commentsperpage;
        $from = ($page - 1) * $count;
      } else {
        $from = 0;
        $count = $post->commentscount;
      }
    } else {
      $from = 0;
      $count = litepublisher::$options->commentsperpage;
    }
    
    $table = $this->thistable;
    $items = $this->select("$table.post = $this->pid $whereauthor  and $table.status = '$status'",
    "order by $table.posted asc limit $from, $count");
    
    $args = targs::i();
    $args->from = $from;
    $comment = new tcomment(0);
    ttheme::$vars['comment'] = $comment;
    $lang = tlocal::i('comment');
    if ($ismoder = $this->moderator) {
      tlocal::usefile('admin');
      $moderate =$theme->templates['content.post.templatecomments.comments.comment.moderate'];
    } else {
      $moderate = '';
    }
    
    $tml = strtr($theme->templates['content.post.templatecomments.comments.comment'], array(
    '$moderate' => $moderate,
    '$quotebuttons' => $post->comstatus != 'closed' ? $theme->templates['content.post.templatecomments.comments.comment.quotebuttons'] : ''
    ));
    
    $index = $from;
    $class1 = $theme->templates['content.post.templatecomments.comments.comment.class1'];
    $class2 = $theme->templates['content.post.templatecomments.comments.comment.class2'];
    foreach ($items as $id) {
      $comment->id = $id;
      $args->index = ++$index;
      $args->class = ($index % 2) == 0 ? $class1 : $class2;
      $result .= $theme->parsearg($tml, $args);
    }
    unset(ttheme::$vars['comment']);
    if (!$ismoder) {
      if ($result == '') return '';
    }
    
    if ($status == 'hold') {
      $tml = $theme->templates['content.post.templatecomments.holdcomments'];
      $tml .= ttemplate::i()->getjavascript('$template.jsmerger_moderate');
    } else {
      $tml = $theme->templates['content.post.templatecomments.comments'];
    }
    
    $args->from = $from + 1;
    $args->comment = $result;
    return $theme->parsearg($tml, $args);
  }
  
}//class

class tcomment extends tdata {
  private static $md5 = array();
  
  public function __construct($id = 0) {
    if (!isset($id)) return false;
    parent::__construct();
    $this->table = 'comments';
    $id = (int) $id;
    if ($id > 0) $this->setid($id);
  }
  
  public function setid($id) {
    $comments = tcomments::i();
    $this->data = $comments->getitem($id);
  }
  
  public function save() {
    extract($this->data, EXTR_SKIP);
    $this->db->UpdateAssoc(compact('id', 'post', 'author', 'parent', 'posted', 'status', 'content'));
    
    $this->getdb($this->rawtable)->UpdateAssoc(array(
    'id' => $id,
    'modified' => sqldate(),
    'rawcontent' => $rawcontent,
    'hash' => basemd5($rawcontent)
    ));
  }
  
  public function getauthorlink() {
    $name = $this->data['name'];
    $website = $this->data['website'];
    if ($website == '')  return $name;
    $manager = tcommentmanager::i();
    if ($manager->hidelink || ($this->trust <= $manager->trustlevel)) return $name;
    $rel = $manager->nofollow ? 'rel="nofollow"' : '';
    if ($manager->redir) {
      return sprintf('<a %s href="%s/comusers.htm%sid=%d">%s</a>',$rel,
      litepublisher::$site->url, litepublisher::$site->q, $this->author, $name);
    } else {
      if (!strbegin($website, 'http://')) $website = 'http://' . $website;
      return sprintf('<a class="url fn" %s href="%s">%s</a>',
      $rel,$website, $name);
    }
  }
  
  public function getdate() {
    $theme = ttheme::i();
    return tlocal::date($this->posted, $theme->templates['content.post.templatecomments.comments.comment.date']);
  }
  
  public function Getlocalstatus() {
    return tlocal::get('commentstatus', $this->status);
  }
  
  public function getposted() {
    return strtotime($this->data['posted']);
  }
  
  public function setposted($date) {
    $this->data['posted'] = sqldate($date);
  }
  
  public function  gettime() {
    return date('H:i', $this->posted);
  }
  
  public function geturl() {
    $post = tpost::i($this->post);
    return $post->link . "#comment-$this->id";
  }
  
  public function getposttitle() {
    $post = tpost::i($this->post);
    return $post->title;
  }
  
  public function getrawcontent() {
    if (isset($this->data['rawcontent'])) return $this->data['rawcontent'];
    $comments = tcomments::i($this->post);
    return $comments->raw->getvalue($this->id, 'rawcontent');
  }
  
  public function setrawcontent($s) {
    $this->data['rawcontent'] = $s;
    $filter = tcontentfilter::i();
    $this->data['content'] = $filter->filtercomment($s);
  }
  
  public function getip() {
    if (isset($this->data['ip'])) return $this->data['ip'];
    $comments = tcomments::i($this->post);
    return $comments->raw->getvalue($this->id, 'ip');
  }
  
  public function getmd5email() {
    $email = $this->data['email'];
    if ($email) {
      if (isset(self::$md5[$email])) return self::$md5[$email];
      $md5 = md5($email);
      self::$md5[$email] = $md5;
      return $md5;
    }
    return '';
  }
  
  public function getgravatar() {
    if ($md5email = $this->getmd5email()) {
      return sprintf('<img class="avatar photo" src="http://www.gravatar.com/avatar/%s?s=32&amp;r=g&amp;d=wavatar" title="%2$s" alt="%2$s"/>', $md5email, $this->name);
    } else {
      return '';
    }
  }
  
}//class