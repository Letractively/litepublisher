<?php
/**
* Lite Publisher
* Copyright (C) 2010, 2012 Vladimir Yushko http://litepublisher.com/
* Dual licensed under the MIT (mit.txt)
* and GPL (gpl.txt) licenses.
**/

class Tadminoptions extends tadminmenu {
  private $_form;
  
  public static function i($id = 0) {
    return parent::iteminstance(__class__, $id);
  }
  
  public function getautoform($name) {
    if (isset($this->_form)) return $this->_form;
    switch ($name) {
      case 'options':
      $form = new tautoform(litepublisher::$site, 'options', 'blogdescription');
      $form->add($form->fixedurl, $form->url, $form->name, $form->description, $form->keywords, $form->author);
      $form->obj = ttemplate::i();
      $form->add($form->footer('editor'));
      break;
      
      case 'rss':
      $form = new tautoform(trss::i(), 'options', 'rssoptions');
      $form->add($form->feedburner, $form->feedburnercomments, $form->template('editor'));
      break;
      
      case 'comments':
      $form = new tautoform(litepublisher::$options, 'options', 'commentform');
      $form->add($form->filtercommentstatus, $form->commentsapproved, $form->checkduplicate, $form->defaultsubscribe, $form->commentsdisabled, $form->autocmtform, $form->commentsenabled, $form->pingenabled,
      $form->commentpages, $form->commentsperpage, $form->comments_invert_order);
      $form->obj = litepublisher::$classes->commentmanager;
      $form->add($form->sendnotification, $form->hidelink,  $form->redir, $form->nofollow);
      $form->addeditor(tsubscribers::i(), 'locklist');
      break;
      
      case 'ping':
      $form = new tautoform(tpinger::i(), 'options', 'optionsping');
      $form->add($form->enabled, $form->services('editor'));
      break;
      
      case 'robots':
      $form = new tautoform(trobotstxt::i(), 'options', 'editrobot');
      $form->add($form->text('editor'));
      break;
      
      case 'notfound404':
      $form = new tautoform(tnotfound404::i(), 'options', 'edit404');
      $form->add($form->notify, $form->text('editor'));
      break;
      
      
      default:
      return false;
    }
    $this->_form = $form;
    return $form;
  }
  
  public function gethead() {
    $result = parent::gethead();
    switch ($this->name) {
      case 'home':
      $template = ttemplate::i();
    $result .= $template->getready('$("#tabs").tabs({ cache: true });');
      break;
    }
    return $result;
  }
  
  public function getcontent() {
    if ($form = $this->getautoform($this->name)) return $form->getform();
    $options = litepublisher::$options;
    $template = ttemplate::i();
    ttheme::$vars['template'] = $template;
    $result = '';
    $args = targs::i();
    
    switch ($this->name) {
      case 'home':
      $home = thomepage::i();
      $args->hideposts = $home->hideposts;
      $args->invertorder = $home->invertorder;
      $args->image = $home->image;
      $args->idhome =  $home->id;
      $menus = tmenus::i();
      $args->homemenu =  $menus->home;
      
      $args->includecats = tposteditor::getcategories($home->includecats);
      $args->excludecats = str_replace('category-', 'exclude_category-',
      tposteditor::getcategories($home->excludecats));
      $args->formtitle = '';
      break;
      
      case 'mail':
      ttheme::$vars['subscribers'] = tsubscribers::i();
      ttheme::$vars['mailer'] = TSMTPMailer ::i();
      $args->mailerchecked = $options->mailer == 'smtp';
      break;
      
      case 'view':
      $filter = tcontentfilter::i();
      $args->usefilter = $filter->usefilter;
      $args->automore = $filter->automore;
      $args->automorelength = $filter->automorelength;
      $args->autolinks = $filter->autolinks;
      $args->commentautolinks = $filter->commentautolinks;
      $args->icondisabled = $options->icondisabled;
      $args->hidefilesonpage = $options->hidefilesonpage;
      
      $themeparser = tthemeparser::i();
      $args->replacelang = $themeparser->replacelang;
      
      $parser = tmediaparser::i();
      $args->enablepreview = $parser->enablepreview;
      $args->ratio = $parser->ratio;
      $args->previewwidth = $parser->previewwidth;
      $args->previewheight = $parser->previewheight;
      
      $args->video_width = litepublisher::$site->video_width;
      $args->video_height = litepublisher::$site->video_height;
      break;
      
      case 'links':
      $linkgen = tlinkgenerator::i();
      ttheme::$vars['linkgen'] = $linkgen;
      $args->urlencode = $linkgen->urlencode;
      break;
      
      case 'cache':
      $args->cache = $options->cache;
      $args->admincache = $options->admincache;
      $args->ob_cache = $options->ob_cache;
      $args->compress = $options->compress;
      break;
      
      case 'catstags':
      case 'lite': //old version suports
      $args->litearch= litepublisher::$classes->archives->lite;
      $cats = litepublisher::$classes->categories;
      $args->litecats= $cats->lite;
      $args->parentcats = $cats->includeparents;
      $args->childcats = $cats->includechilds;
      $tags = litepublisher::$classes->tags;
      $args->litetags = $tags->lite;
      $args->parenttags = $tags->includeparents;
      $args->childtags = $tags->includechilds;
      $lang = tlocal::i('options');
      $args->formtitle = $lang->catstags;
      $html = $this->html;
      return $html->adminform('[checkbox=litearch]
      [checkbox=litecats] [checkbox=parentcats] [checkbox=childcats]
      [checkbox=litetags] [checkbox=parenttags] [checkbox=childtags]', $args) .
      $html->p->notecatstags;
      
      case 'secure':
      $auth = tauthdigest::i();
      $args->echoexception = $options->echoexception;
      $args->cookie = $options->cookieenabled;
      $args->usersenabled = $options->usersenabled;
      $args->reguser = $options->reguser;
      $args->parsepost = $options->parsepost;
      $args->show_draft_post = $options->show_draft_post ;
      
      $args->xxxcheck = $auth->xxxcheck;
      $filter = tcontentfilter::i();
      $args->phpcode = $filter->phpcode;
      $args->removephp = tthemeparser::i()->removephp;
      $args->useshell = tupdater::i()->useshell;
      $backuper = tbackuper::i();
      $args->filertype = tadminhtml::array2combo(array(
      'auto' => 'auto',
      'file' => 'file',
      'ftp' => 'ftp',
      'ftpsocket' => 'ftpsocket',
      //'ssh2' => 'ssh2'
      ), $backuper->filertype);
      break;
    }
    
  $result  = $this->html->{$this->name}($args);
    return $this->html->fixquote($result);
  }
  
  public function processform() {
    if ($form = $this->getautoform($this->name)) return $form->processform();
    extract($_POST, EXTR_SKIP);
    $options = litepublisher::$options;
    
    switch ($this->name) {
      case 'home':
      $home = thomepage::i();
      $home->lock();
      $home->image = $image;
      $home->hideposts = isset($hideposts);
      $home->invertorder = isset($invertorder);
      $home->includecats = tadminhtml::check2array('category-');
      $home->excludecats = tadminhtml::check2array('exclude_category-');
      $home->unlock();
      
      $menus = tmenus::i();
      $menus->home = isset($homemenu);
      $menus->save();
      break;
      
      case 'mail':
      $options->lock();
      if(!empty($email)) $options->email = $email;
      if(!empty($fromemail)) $options->fromemail = $fromemail;
      $options->mailer = empty($mailer) ? '': 'smtp';
      $options->unlock();
      if (!empty($subscribeemail)) {
        $subscribe = tsubscribers::i();
        $subscribe->fromemail = $subscribeemail;
        $subscribe->save();
      }
      
      $mailer = TSMTPMailer ::i();
      $mailer->lock();
      $mailer->host = $host;
      $mailer->login = $login;
      $mailer->password = $password;
      $mailer->port= (int) $port;
      $mailer->unlock();
      break;
      
      case 'view':
      $options->icondisabled = isset($icondisabled);
      if (!empty($perpage)) $options->perpage = (int) $perpage;
      $options->hidefilesonpage = isset($hidefilesonpage);
      $filter = tcontentfilter::i();
      $filter->usefilter = isset($usefilter);
      $filter->automore = isset($automore);
      $filter->automorelength = (int) $automorelength;
      $filter->autolinks = isset($autolinks);
      $filter->commentautolinks = isset($commentautolinks);
      $filter->save();
      
      $replacelang  = isset($replacelang );
      $themeparser = tthemeparser::i();
      if ($replacelang != $themeparser->replacelang) {
        $themeparser->replacelang = $replacelang;
        $themeparser->save();
      }
      
      $parser = tmediaparser::i();
      $parser->enablepreview = isset($enablepreview);
      $parser->ratio = isset($ratio);
      $parser->previewwidth = $previewwidth;
      $parser->previewheight = $previewheight;
      $parser->save();
      
      litepublisher::$site->video_width = $video_width;
      litepublisher::$site->video_height = $video_height;
      break;
      
      case 'links':
      $linkgen = tlinkgenerator::i();
      $linkgen->urlencode = isset($urlencode);
      if (!empty($post)) $linkgen->post = $post;
      if (!empty($menu)) $linkgen->menu = $menu;
      if (!empty($category)) $linkgen->category = $category;
      if (!empty($tag)) $linkgen->tag = $tag;
      if (!empty($archive)) $linkgen->archive = $archive;
      $linkgen->save();
      break;
      
      
      case 'cache':
      if (isset($clearcache)) {
        ttheme::clearcache();
      } else {
        $options->lock();
        $options->cache = isset($cache );
        $options->admincache = isset($admincache );
        if (!empty($cacheexpired)) $options->expiredcache = (int) $cacheexpired;
        $options->ob_cache = isset($ob_cache);
        $options->compress = isset($compress);
        $options->unlock();
      }
      break;
      
      case 'lite':
      case 'catstags':
      litepublisher::$classes->archives->lite = isset($litearch);
      $cats = litepublisher::$classes->categories;
      $cats->lite = isset($litecats);
      $cats->includeparents = isset($parentcats);
      $cats->includechilds = isset($childcats);
      $cats->save();
      $tags = litepublisher::$classes->tags;
      $tags->lite = isset($litetags);
      $tags->includeparents = isset($parenttags);
      $tags->includechilds = isset($childtags);
      $tags->save();
      break;
      
      case 'secure':
      if (isset($_POST['oldpassword'])) {
        $h2 = $this->html->h2;
        if ($oldpassword == '') return $h2->badpassword;
        if (($newpassword == '') || ($newpassword != $repassword))  return $h2->difpassword;
        if (!$options->auth($options->login, $oldpassword)) return $h2->badpassword;
        $options->changepassword($newpassword);
        $auth = tauthdigest::i();
        $auth->logout();
        return $h2->passwordchanged;
      } else {
        $options->echoexception = isset($echoexception);
        $options->cookieenabled = isset($cookie);
        $options->reguser = isset($reguser);
        $this->usersenabled = isset($usersenabled);
        $options->parsepost = isset($parsepost);
        $options->show_draft_post  = isset($show_draft_post);
        
        $auth = tauthdigest::i();
        $auth->xxxcheck = isset($xxxcheck);
        $auth->save();
        $filter = tcontentfilter::i();
        $filter->phpcode = isset($phpcode);
        $filter->save();
        
        $parser = tthemeparser::i();
        $parser->removephp =isset($removephp );
        $parser->save();
        
        $backuper = tbackuper::i();
        if ($backuper->filertype != $filertype) {
          $backuper->filertype = $filertype;
          $backuper->save();
        }
        
        $useshell = isset($useshell);
        $updater = tupdater::i();
        if ($useshell !== $updater->useshell) {
          $updater->useshell = $useshell;
          $updater->save();
        }
      }
      break;
    }
    
    return '';
  }
  
  public function setusersenabled($value) {
    if (litepublisher::$options->usersenabled == $value) return;
    litepublisher::$options->usersenabled = $value;
    $menus = tadminmenus::i();
    $menus->lock();
    if ($value) {
      $id = $menus->createitem(0, 'users', 'author', 'tadminusers');
      $menus->createitem($id, 'groups', 'admin', 'tadminusergroups');
      $menus->createitem($id, 'options', 'admin', 'tadminusers');
    } else {
      $menus->deletetree($menus->url2id('/admin/users/'));
    }
    $menus->unlock();
  }
  
}//class