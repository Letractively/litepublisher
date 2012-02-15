<?php
/**
* Lite Publisher
* Copyright (C) 2010, 2012 Vladimir Yushko http://litepublisher.com/
* Dual licensed under the MIT (mit.txt)
* and GPL (gpl.txt) licenses.
**/

class tajaxposteditor  extends tevents {
  public $idpost;
  private $isauthor;
  
  public static function i() {
    return getinstance(__class__);
  }
  
  protected function create() {
    parent::create();
    $this->basename = 'ajaxposteditor';
    $this->addevents('onhead', 'oneditor');
    $this->data['head'] = '';
    $this->data['visual'] = '';
    //'/plugins/tiny_mce/init.js';
    //'/plugins/ckeditor/init.js';
    $this->data['ajaxvisual'] = true;
  }
  
  public function dogethead($head) {
    $template = ttemplate::i();
    $template->ltoptions['upload_button_text'] = tlocal::i()->upload;
    $head .= $this->head;
    if ($this->visual) {
      if ($this->ajaxvisual) {
        $head .= $template->getready('$("a[rel~=\'loadvisual\']").one("click", function() {
          $("#loadvisual").remove();
          $.getScript("' . litepublisher::$site->files . $this->visual . '");
          return false;
        });');
      } else {
        $head .= $template->getjavascript($this->visual);
      }
    }
    
    $this->callevent('onhead', array(&$head));
    return $head;
  }
  
  protected static function error403() {
    return '<?php header(\'HTTP/1.1 403 Forbidden\', true, 403); ?>' . turlmap::htmlheader(false) . 'Forbidden';
  }
  
  public function getviewicon($idview, $icon) {
    $result = tadminviews::getcomboview($idview);
    if ($icons = tadminicons::getradio($icon)) {
      $html = tadminhtml ::i();
      if ($html->section == '') $html->section = 'editor';
      $result .= $html->h2->icons;
      $result .= $icons;
    }
    return $result;
  }
  
  public static function auth() {
    if (!litepublisher::$options->cookieenabled) return self::error403();
    if (!litepublisher::$options->authcookie()) return self::error403();
    if (litepublisher::$options->group != 'admin') {
      $groups = tusergroups::i();
      if (!$groups->hasright(litepublisher::$options->group, 'author')) return self::error403();
    }
  }
  
  public function request($arg) {
    //tfiler::log(var_export($_GET, true) . var_export($_POST, true) . var_export($_FILES, true));
    if (isset($_GET['get']) && ($_GET['get'] == 'upload')) {
      if (empty($_POST['admincookie'])) return self::error403();
      if ( 'POST' != $_SERVER['REQUEST_METHOD'] ) {
        return "<?php
        header('Allow: POST');
        header('HTTP/1.1 405 Method Not Allowed', true, 405);
        header('Content-Type: text/plain');
        ?>";
      }
      $_COOKIE['admin'] = $_POST['admincookie'];
    }
    if ($err = self::auth()) return $err;
    $this->idpost = tadminhtml::idparam();
    $this->isauthor = 'author' == litepublisher::$options->group;
    if ($this->idpost > 0) {
      $posts = tposts::i();
      if (!$posts->itemexists($this->idpost)) return self::error403();
      $groupname = litepublisher::$options->group;
      if ($groupname != 'admin') {
        $groups = tusergroups::i();
        if (!$groups->hasright($groupname, 'editor') and  $groups->hasright($groupname, 'author')) {
          $this->isauthor = true;
          $post = tpost::i($this->idpost);
          if (litepublisher::$options->user != $post->author) return self::error403();
        }
      }
    }
    return $this->getcontent();
  }
  
  private function getfiletemplates($id, $idpost, $li_id) {
    $replace = array(
    //'<li>' => sprintf('<li><input type="checkbox" name="%1$s" id="%1$s" value="$id">', $li_id),
    '$id'=> $id,
    '$post.id'=> $idpost
    );
    
    $checkbox = sprintf('><input type="checkbox" name="%1$s" id="%1$s" value="$id" />', $li_id);
    
    $theme = ttheme::i();
    $types = $theme->reg('/^content\.post\.filelist/');
    $a = array();
    foreach ($types as $name => $val) {
      $val = strtr($val, $replace);
      $name = substr($name, strrpos($name, '.') + 1);
      if ($name == 'filelist') {
        $name = '';
      } elseif (substr($name, -1)  != 's') {
        // chicks if not an items
        $val =substr_replace($val, $checkbox, strpos($val, '>'), 1);
      }
      $a[$name] = $val;
    }
    return new tarray2prop ($a);
  }
  
  public function getcontent() {
    $theme = tview::i(tviews::i()->defaults['admin'])->theme;
    $html = tadminhtml ::i();
    $html->section = 'editor';
    $lang = tlocal::i('editor');
    $post = tpost::i($this->idpost);
    ttheme::$vars['post'] = $post;
    
    switch ($_GET['get']) {
      case 'tags':
      $result = $html->getedit('tags', $post->tagnames, $lang->tags);
      $lang->section = 'editor';
      $result .= $html->h4->addtags;
      $items = array();
      $tags = $post->factory->tags;
      $list = $tags->getsorted(-1, 'name', 0);
      foreach ($list as $id ) {
        $items[] = '<a href="" rel="tagtopost">' . $tags->items[$id]['title'] . "</a>";
      }
      $result .= sprintf('<p>%s</p>', implode(', ', $items));
      break;
      
      case 'posted':
      $args = targs::i();
      $args->date = $post->posted != 0 ?date('d.m.Y', $post->posted) : '';
      $args->time  = $post->posted != 0 ?date('H:i', $post->posted) : '';
      $result = $html->datepicker($args);
      break;
      
      case 'status':
$args = new targs();
$args->commentsenabled = $post->commentsenabled;
$args->pingenabled = $post->pingenabled;
      $args->status= tadminhtml::array2combo(array(
      'published' => $lang->published,
      'draft' => $lang->draft
      ), $post->status);

$args->perms = tadminperms::getcombo($post->idperm);
$args->password = $post->password;
      $result = $html->parsearg(
'[checkbox=commentsenabled]
 [checkbox=pingenabled]
[combo=status]
$perms
[password=password]
<p>$lang.notepassword</p>', 
$args);

      break;
      
      case 'view':
      $result = $this->getviewicon($post->idview, $post->icon);
      break;
      
      case 'seo':
      $form = new tautoform($post, 'editor', 'editor');
      $form->add($form->url, $form->title2, $form->keywords, $form->description);
      $result = $form->getcontent();
      $result .= tadminhtml::i()->getinput('editor', 'head', $post->data['head'], tlocal::i()->head);
      break;
      
      case 'files':
      $args = targs::i();
      $args->ajax = tadminhtml::getadminlink('/admin/ajaxposteditor.htm', "id=$post->id&get");
      $files = tfiles::i();
      if (count($post->files) == 0) {
        $args->currentfiles = '<ul></ul>';
      } else {
        $templates = $this->getfiletemplates('curfile-$id', 'curpost-$post.id', 'currentfile-$id');
        $args->currentfiles = $files->getlist($post->files, $templates);
      }
      
      if (dbversion) {
        $sql = "parent =0 and media <> 'icon'";
        $sql .= litepublisher::$options->user <= 1 ? '' : ' and author = ' . litepublisher::$options->user;
        $count = $files->db->getcount($sql);
      } else {
        $list= array();
        $uid = litepublisher::$options->user;
        foreach ($files->items as $id => $item) {
          if (($item['parent'] != 0) || ($item['media'] == 'icon')) continue;
          if ($uid > 1 && $uid != $item['author']) continue;
          $list[] = $id;
        }
        $count = count($list);
      }
      
      $pages = '';
      $perpage = 10;
      $count = ceil($count/$perpage);
      for ($i =1; $i <= $count; $i++) {
        $args->index = $i;
        $pages .= $html->pageindex($args);
      }
      
      $args->pages = $pages;
      $args->files = implode(',', $post->files);
      $result = $html->browser($args);
      break;
      
      case 'filepage':
      $page = tadminhtml::getparam('page', 1);
      $page = max(1, $page);
      
      $perpage = 10;
      $files = tfiles::i();
      if (dbversion) {
        $sql = "parent =0 and media <> 'icon'";
        $sql .= litepublisher::$options->user <= 1 ? '' : ' and author = ' . litepublisher::$options->user;
        $count = $files->db->getcount($sql);
        $pagescount = ceil($count/$perpage);
        $page = min($page, $pagescount);
        $from = ($page -1)  * $perpage;
        $list = $files->select($sql, " order by posted desc limit $from, $perpage");
        if (!$list) $list = array();
      } else {
        $list= array();
        $uid = litepublisher::$options->user;
        foreach ($files->items as $id => $item) {
          if (($item['parent'] != 0) || ($item['media'] == 'icon')) continue;
          if ($uid > 1 && $uid != $item['author']) continue;
          $list[] = $id;
        }
        $count = count($list);
        $pagescount = ceil($count/$perpage);
        $page = min($page, $pagescount);
        $from = ($page -1)  * $perpage;
        $list = array_slice($list, $from, $perpage);
      }
      
      if (count($list) == 0) return '';
      
      $args = targs::i();
      $args->ajax = tadminhtml::getadminlink('/admin/ajaxposteditor.htm', "id=$post->id&get");
      $args->page = $page;
      $templates = $this->getfiletemplates('pagefile-$id', 'pagepost-$post.id', 'itemfilepage-$id');
      $files = tfiles::i();
      $result = $files->getlist($list, $templates);
      $result .= $html->page($args);
      break;
      
      case 'upload':
      if (!isset($_FILES['Filedata']) || !is_uploaded_file($_FILES['Filedata']['tmp_name']) ||
      $_FILES['Filedata']['error'] != 0) return self::error403();
      if ($this->isauthor && ($r = tauthor_rights::i()->canupload())) return $r;
      
      $parser = tmediaparser::i();
      $id = $parser->uploadfile($_FILES['Filedata']['name'], $_FILES['Filedata']['tmp_name'], '', '', '', false);
      $templates = $this->getfiletemplates('uploaded-$id', 'new-post-$post.id', 'newfile-$id');
      $files = tfiles::i();
      $result = $files->getlist(array($id), $templates);
      break;
      
      case 'contenttabs':
      $args = targs::i();
      $args->ajax = tadminhtml::getadminlink('/admin/ajaxposteditor.htm', "id=$post->id&get");
      $result = $html->contenttabs($args);
      break;
      
      case 'excerpt':
      $result = $this->geteditor('excerpt', $post->excerpt, false);
      break;
      
      case 'rss':
      $result = $this->geteditor('rss', $post->rss, false);
      break;
      
      case 'more':
      $result = $html->getedit('more', $post->moretitle, $lang->more);
      break;
      
      case 'filtered':
      $result = $this->geteditor('filtered', $post->filtered, false);
      break;
      
      case 'upd':
      $result = $this->geteditor('upd', '', false);
      break;
      
      default:
      $result = var_export($_GET, true);
    }
    //tfiler::log($result);
    return turlmap::htmlheader(false) . $result;
  }
  
  public function geteditor($name, $value, $visual) {
    $html = tadminhtml ::i();
    $hsect = $html->section;
    $html->section = 'editor';
    $lang = tlocal::i();
    $lsect = $lang->section;
    $lang->section = 'editor';
    $title = $lang->$name;
    if ($visual && $this->ajaxvisual && $this->visual) $title .= $html->loadvisual();
    $result = $html->getinput('editor', $name, tadminhtml::specchars($value), $title);
    $lang->section = $lsect;
    $html->section = $hsect;
    return $result;
  }
  
  public function getraweditor($value) {
    $html = tadminhtml ::i();
    if ($html->section == '') $html->section = 'editor';
    $lang = tlocal::i();
    if ($lang->section == '') $lang->section = 'editor';
    $title = $lang->raw;
    if ($this->ajaxvisual && $this->visual) $title .= $html->loadvisual();
    $title .= $html->loadcontenttabs();
    return $html->getinput('editor', 'raw', tadminhtml::specchars($value), $title);
  }
  
}//class


?>