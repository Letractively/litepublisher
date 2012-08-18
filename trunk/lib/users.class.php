<?php
/**
* Lite Publisher
* Copyright (C) 2010, 2012 Vladimir Yushko http://litepublisher.com/
* Dual licensed under the MIT (mit.txt)
* and GPL (gpl.txt) licenses.
**/

class tusers extends titems {
  public $grouptable;
  
  public static function i() {
    return getinstance(__class__);
  }
  
  protected function create() {
    $this->dbversion = true;
    parent::create();
    $this->basename = 'users';
    $this->table = 'users';
    $this->grouptable = 'usergroup';
  }
  
  public function res2items($res) {
    if (!$res) return array();
    $result = array();
    $db = litepublisher::$db;
    while ($item = $db->fetchassoc($res)) {
      $id = (int) $item['id'];
      $item['idgroups'] = tdatabase::str2array($item['idgroups']);
      $result[] = $id;
      $this->items[$id] = $item;
    }
    return $result;
  }
  
/*
  public function getitem($id) {
    if ($id == 1) return array(
    'email' =>litepublisher::$options->email,
    'name' => litepublisher::$site->author,
    'website' => litepublisher::$site->url . '/',
    'password' => litepublisher::$options->password,
    'cookie' => litepublisher::$options->cookie,
    'expired' => sqldate(litepublisher::$options->cookieexpired ),
    'status' => 'approved',
    'idgroups' => array(1)
    );
    
    return parent::getitem($id);
  }
*/  

  public function add(array $values) {
    return tusersman::i()->add($values);
  }
  
  public function edit($id, array $values) {
    return tusersman::i()->edit($id, $values);
    
  }
  
  public function setgroups($id, array $idgroups) {
    $this->items[$id]['idgroups'] = $idgroups;
    $db = $this->getdb($this->grouptable);
    $db->delete("iduser = $id");
    foreach ($idgroups as $idgroup) {
      $db->add(array(
      'iduser' => $id,
      'idgroup' => $idgroup
      ));
    }
  }
  
  public function delete($id) {
    $this->getdb($this->grouptable)->delete('iduser = ' .(int)$id);
    tuserpages::i()->delete($id);
    $this->getdb('comments')->update("status = 'deleted'", "author = $id");
    return parent::delete($id);
  }
  
  public function emailexists($email) {
    if ($email == '') return false;
    if ($email == litepublisher::$options->email) return 1;
    return $this->db->findid('email = '. dbquote($email));
  }
  
  public function getpassword($id) {
    return $id == 1 ? litepublisher::$options->password : $this->getvalue($id, 'password');
  }
  
  public function changepassword($id, $password) {
    $item = $this->getitem($id);
    $this->setvalue($id, 'password', basemd5(sprintf('%s:%s:%s', $item['email'],  litepublisher::$options->realm, $password)));
  }
  
  public function approve($id) {
    $this->setvalue($id, 'status', 'approved');
    $pages = tuserpages::i();
    if ($pages->createpage) $pages->addpage($id);
  }
  
  public function auth($email,$password) {
    $password = basemd5(sprintf('%s:%s:%s', $email,  litepublisher::$options->realm, $password));
    
    $email = dbquote($email);
    if (($a = $this->select("email = $email and password = '$password'", 'limit 1')) && (count($a) > 0)) {
      $item = $this->getitem($a[0]);
      if ($item['status'] == 'wait') $this->approve($item['id']);
      return (int) $item['id'];
    }
    return false;
  }
  
  public function authcookie($cookie) {
    $cookie = (string) $cookie;
    if (empty($cookie)) return false;
    $cookie = basemd5( $cookie . litepublisher::$secret);
    if ($cookie == basemd5(litepublisher::$secret)) return false;
    if ($id = $this->findcookie($cookie)) {
      $item = $this->getitem($id);
      if (strtotime($item['expired']) > time()) return  $id;
    }
    return false;
  }
  
  public function findcookie($cookie) {
    $cookie = dbquote($cookie);
    if (($a = $this->select('cookie = ' . $cookie, 'limit 1')) && (count($a) > 0)) {
      return (int) $a[0];
    }
    return false;
  }
  
  public function getgroupname($id) {
    $item = $this->getitem($id);
    $groups = tusergroups::i();
    return $groups->items[$item['idgroups'][0]]['name'];
  }
  
  public function clearcookie($id) {
    $this->setcookie($id, '', 0);
  }
  
  public function setcookie($id, $cookie, $expired) {
    if ($cookie != '') $cookie = basemd5($cookie . litepublisher::$secret);
    $expired = sqldate($expired);
    if (isset($this->items[$id])) {
      $this->items[$id]['cookie'] = $cookie;
      $this->items[$id]['expired'] = $expired;
    }
    
    $this->db->updateassoc(array(
    'id' => $id,
    'cookie' => $cookie,
    'expired' => $expired
    ));
  }
  
}//class