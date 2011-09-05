<?php
/**
* Lite Publisher
* Copyright (C) 2010, 2011 Vladimir Yushko http://litepublisher.com/
* Dual licensed under the MIT (mit.txt)
* and GPL (gpl.txt) licenses.
**/

class tadminmenumanager extends tadminmenu {
  
  public static function instance($id = 0) {
    return parent::iteminstance(__class__, $id);
  }
  
  public function gethead() {
    $result = parent::gethead();
    
    $template = ttemplate::instance();
    $template->ltoptions['idpost'] = $this->idget();
    $template->ltoptions['lang'] = litepublisher::$options->language ;
  $result .= $template->getready('$("#tabs").tabs({ cache: true });');
    $ajax = tajaxmenueditor ::instance();
    return $ajax->dogethead($result);
  }
  
  public function gettitle() {
    if (($this->name == 'edit') && ($this->idget() != 0)) {
      return $this->lang->edit;
    }
    return parent::gettitle();
  }
  
  public function getcontent() {
    $result = '';
    switch ($this->name) {
      case 'menu':
      if (isset($_GET['action']) && in_array($_GET['action'], array('delete', 'setdraft', 'publish'))) {
        $result .= $this->doaction($this->idget(), $_GET['action']);
      }
      $result .= $this->getmenulist();
      return $result;
      
      case 'edit':
      case 'editfake':
      $id = tadminhtml::idparam();
      $menus = tmenus::instance();
      $parents = array(0 => '-----');
      foreach ($menus->items as $item) {
        $parents[$item['id']] = $item['title'];
      }
      
      $html = $this->html;
      $lang = tlocal::instance('menu');
      $args = targs::instance();
      $args->adminurl = $this->adminurl;
      $args->ajax = tadminhtml::getadminlink('/admin/ajaxmenueditor.htm', "id=$id&get");
      $args->editurl = tadminhtml::getadminlink('/admin/menu/edit', 'id');
      if ($id == 0) {
        $args->id = 0;
        $args->title = '';
        $args->parent = tadminhtml::array2combo($parents, 0);
        $args->order = tadminhtml::array2combo(range(0, 10), 0);
        $status = 'published';
      } else {
        if (!$menus->itemexists($id)) return $this->notfound;
        $menuitem = tmenu::instance($id);
        $args->id = $id;
        $args->title = $menuitem->title;
        $args->parent = tadminhtml::array2combo($parents, $menuitem->parent);
        $args->order = tadminhtml::array2combo(range(0, 10), $menuitem->order);
        $status = $menuitem->status;
      }
      $args->status = tadminhtml::array2combo(array(
      'draft' => $lang->draft,
      'published' => $lang->published
      ), $status);
      
      if (($this->name == 'editfake') || (($id > 0) && ($menuitem instanceof tfakemenu))) {
        $args->url = $id == 0 ? '' : $menuitem->url;
        $args->type = 'fake';
        $args->formtitle = $lang->faketitle;
        return $html->adminform(
        '[text=title]
        [text=url]
        [combo=parent]
        [combo=order]
        [combo=status]
        [hidden=type]
        [hidden=id]', $args);
      }
      
      $ajaxeditor = tajaxmenueditor::instance();
      $args->editor = $ajaxeditor->geteditor('raw', $id == 0 ? '' : $menuitem->rawcontent, true);
      $html->section = 'menu';
      return $html->form($args);
    }
  }
  
  public function processform() {
    if (!(($this->name == 'edit') || ($this->name == 'editfake'))) return '';
    extract($_POST, EXTR_SKIP);
    if (empty($title)) return '';
    $id = $this->idget();
    $menus = tmenus::instance();
    if (($id != 0) && !$menus->itemexists($id)) return $this->notfound;
    if (isset($type) && ($type == 'fake')) {
      $menuitem = tfakemenu::instance($id);
    } else  {
      $menuitem = tmenu::instance($id);
    }
    
    $menuitem->title = $title;
    $menuitem->order = (int) $order;
    $menuitem->parent = (int) $parent;
    $menuitem->status = $status == 'draft' ? 'draft' : 'published';
    if (isset($raw)) $menuitem->content = $raw;
    if (isset($idview)) $menuitem->idview = $idview;
    if (isset($url)) {
      $menuitem->url = $url;
      if (!isset($type) || ($type != 'fake')) {
        $menuitem->keywords = $keywords;
        $menuitem->description = $description;
      }
    }
    if ($id == 0) {
      $_POST['id'] = $menus->add($menuitem);
    } else  {
      $menus->edit($menuitem);
    }
    return sprintf($this->html->p->success,"<a href=\"$menuitem->link\" title=\"$menuitem->title\">$menuitem->title</a>");
  }
  
  private function getmenulist() {
    $menus = tmenus::instance();
    $args = targs::instance();
    $args->adminurl = $this->adminurl;
    $args->editurl = litepublisher::$site->url .$this->url . 'edit/' . litepublisher::$site->q . 'id';
    $html = $this->html;
    $result = $html->listhead();
    foreach ($menus->items as $id => $item) {
      $args->add($item);
      $args->link = $menus->getlink($id);
      $args->status = tlocal::get('common', $item['status']);
      $args->parent = $item['parent'] == 0 ? '---' : $menus->getlink($item['parent']);
      $result .=$html->itemlist($args);
    }
    $result .= $html->listfooter;
    return str_replace("'", '"', $result);
  }
  
  private function doaction($id, $action) {
    $menus = tmenus::instance();
    if (!$menus->itemexists($id))  return $this->notfound;
    $args = targs::instance();
    $html = $this->html;
    $h2 = $html->h2;
    $menuitem = tmenu::instance($id);
    switch ($action) {
      case 'delete' :
      if  (!$this->confirmed) {
        $args->adminurl = $this->adminurl;
        $args->id = $id;
        $args->action = 'delete';
        $args->confirm = sprintf($this->lang->confirm, tlocal::get('common', $action), $menus->getlink($id));
        return $this->html->confirmform($args);
      } else {
        $menus->delete($id);
        return $h2->confirmeddelete;
      }
      
      case 'setdraft':
      $menuitem->status = 'draft';
      $menus->edit($menuitem);
      return $h2->confirmedsetdraft;
      
      case 'publish':
      $menuitem->status = 'published';
      $menus->edit($menuitem);
      return $h2->confirmedpublish;
    }
    return '';
  }
  
}//class
?>