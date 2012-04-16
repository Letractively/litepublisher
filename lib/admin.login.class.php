<?php
/**
* Lite Publisher
* Copyright (C) 2010, 2012 Vladimir Yushko http://litepublisher.com/
* Dual licensed under the MIT (mit.txt)
* and GPL (gpl.txt) licenses.
**/

class tadminlogin extends tadminform {
  
  public static function i() {
    return getinstance(__class__);
  }
  
  protected function create() {
    parent::create();
    $this->basename = 'admin.loginform';
    $this->addevents('oncontent');
    $this->data['widget'] = '';
  }
  
  public function auth() {
    if (litepublisher::$options->cookieenabled) {
      if ($s = tguard::checkattack()) return $s;
      if (!litepublisher::$options->authcookie()) return litepublisher::$urlmap->redir301('/admin/login/');
    }else {
      $auth = tauthdigest::i();
      if (!$auth->Auth())  return $auth->headers();
    }
  }
  
  private function logout() {
    if (litepublisher::$options->cookieenabled) {
      if (litepublisher::$options->user) litepublisher::$options->logout();
    } else {
      $auth = tauthdigest::i();
      if ($auth->auth()) $auth->logout();
    }
    return litepublisher::$urlmap->redir301('/admin/login/');
  }
  
  public function request($arg) {
    if ($arg == 'out')   return $this->logout($arg);
    parent::request($arg);
    $this->section = 'login';
    if (!litepublisher::$options->cookieenabled) {
      $this->formresult = $this->html->h4->cookiedisabled;
      return;
    }
    if (!isset($_POST['email']) || !isset($_POST['password'])) return;
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    if (empty($email) || empty($password)) return;
    if (!litepublisher::$options->auth($email, $password)) {
      $this->formresult = $this->html->h4->error;
      return;
    }
    
    $expired = isset($_POST['remember']) ? time() + 1210000 : time() + 8*3600;
    $cookie = md5uniq();
    litepublisher::$options->setcookies($cookie, $expired);
    if (isset($_GET['backurl'])) {
      $url = $_GET['backurl'];
    } else {
      $url = '/admin/';
      if (litepublisher::$options->group != 'admin') {
        $groups = tusergroups::i();
        $url = $groups->gethome(litepublisher::$options->group);
      }
    }
    
    return litepublisher::$urlmap->redir301($url);
  }
  
  public function getcontent() {
    $args = targs::i();
    $html = $this->html;
    $lang = tlocal::admin('login');
    $args->formtitle = $lang->formhead;
    $args->email = !empty($_POST['email']) ? strip_tags($_POST['email']) : '';
    $args->password = !empty($_POST['password']) ? strip_tags($_POST['password']) : '';
    $args->remember = isset($_POST['remember']);
    $result = $this->widget;
    if (isset($_GET['backurl'])) $result = str_replace(array('&backurl=', '&amp;backurl='), 
'&amp;backurl=' . urlencode($_GET['backurl']), $result);
    $result .= $html->adminform('[text=email]
    [password=password]
    [checkbox=remember]',
    $args) .
    $html->lostpass();
    
    if (litepublisher::$options->usersenabled && litepublisher::$options->reguser) {
      $lang = tlocal::i('users');
      $args->formtitle = $lang->regform;
      $args->email = '';
      $args->name = '';
      $form = $html->adminform('[text=email] [text=name]', $args);
      $result .= str_replace('action=""', 'action="'. litepublisher::$site->url . '/admin/reguser/', $form);
    }
    $this->callevent('oncontent', array(&$result));
    return $result;
  }
  
}//class