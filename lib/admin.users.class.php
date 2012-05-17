<?php
/**
* Lite Publisher
* Copyright (C) 2010, 2012 Vladimir Yushko http://litepublisher.com/
* Dual licensed under the MIT (mit.txt)
* and GPL (gpl.txt) licenses.
**/

class tadminusers extends tadminmenu {
  
  public static function i($id = 0) {
    return parent::iteminstance(__class__, $id);
  }
  
  public function  gethead() {
    return parent::gethead() . tuitabs::gethead();
  }
  
  public function getcontent() {
    $result = '';
    $users = tusers::i();
    $groups = tusergroups::i();
    
    $html = $this->html;
    $lang = tlocal::i('users');
    $args = targs::i();
    
    $id = $this->idget();
    switch ($this->action) {
      case 'edit':
      if (!$users->itemexists($id)) {
        $result .= $this->notfound();
      } else {
        $statuses = array();
        foreach (array('approved', 'hold','comuser') as $name) {
          $statuses[$name] = $lang->$name;
        }
        
        $item = $users->getitem($id);
        $args->add($item);
        $args->registered = tuserpages::i()->getvalue($id, 'registered');
        $args->formtitle = $item['name'];
        $args->status = tadminhtml::array2combo($statuses, $item['status']);
        
        $tabs = new tuitabs();
        $tabs->add($lang->login, '[text=email] [password=password]');
        $tabs->add($lang->groups, '[combo=status]' .
        tadmingroups::getgroups($item['idgroups']));
        $tabs->add('Cookie', '[text=cookie] [text=expired] [text=registered] [text=trust]');
        
        $result .= $html->adminform($tabs->get(), $args);
      }
      break;
      
      case 'delete':
      $result .= $html->confirm_delete($users, $this->adminurl);
      break;
      
      default:
      $args->formtitle = $lang->newuser;
      $args->email = '';
      $args->action = 'add';
      
      $tabs = new tuitabs();
      $tabs->add($lang->login, '[text=email] [password=password] [text=name] [hidden=action]');
      $tabs->add($lang->groups, tadmingroups::getgroups(array()));
      
      $result .= $html->adminform($tabs->get(), $args);
          }
    
    //table
    $perpage = 20;
    $count = $users->count;
    $from = $this->getfrom($perpage, $count);
    if ($users->dbversion) {
      $idgroup = (int) tadminhtml::getparam('idgroup', 0);
      $grouptable = litepublisher::$db->prefix . $users->grouptable;
      $where = $groups->itemexists($idgroup) ? "$users->thistable.id in (select iduser from $grouptable where idgroup = $idgroup)" : '';
      $items = $users->select($where, " order by id desc limit $from, $perpage");
      if (!$items) $items = array();
    } else {
      $items = array_slice(array_keys($users->items), $from, $perpage);
    }
    
    $args->adminurl = $this->adminurl;
    $args->formtitle = $lang->userstable;
    $args->table = $html->items2table($users, $items, array(
    $html->get_table_checkbox('user'),
    array('left', $lang->edit, sprintf('<a href="%s=$id&action=edit">$name</a>', $this->adminurl)),
    $html->get_table_item('status'),
    array('left', $lang->page, sprintf('<a href="%s">%s</a>', tadminhtml::getadminlink('/admin/users/pages/', 'id=$id'), $lang->page)),
    $html->get_table_link('delete', $this->adminurl)
    ));
    
    $result .= $html->deletetable($args);
    $result = $html->fixquote($result);
    
    $theme = ttheme::i();
    $result .= $theme->getpages($this->url, litepublisher::$urlmap->page, ceil($count/$perpage));
    return $result;
  }
  
  public function processform() {
    $users = tusers::i();
    $groups = tusergroups::i();
    
    if (isset($_POST['delete'])) {
      $users->lock();
      foreach ($_POST as $key => $value) {
        if (!is_numeric($value)) continue;
        $id = (int) $value;
        $users->delete($id);
      }
      $users->unlock();
      return;
    }
    switch ($this->action) {
      case 'add':
      $_POST['idgroups'] = tadminhtml::check2array('idgroup-');
      if ($id = $users->add($_POST)) {
        litepublisher::$urlmap->redir("$this->adminurl=$id&action=edit");
      } else {
        return $this->html->h2->invalidregdata;
      }
      break;
      
      case 'edit':
      $id = $this->idget();
      if (!$users->itemexists($id)) return;
      $_POST['idgroups'] = tadminhtml::check2array('idgroup-');
      if (!$users->edit($id, $_POST))return $this->notfound;
      break;
    }
  }
  
}//class