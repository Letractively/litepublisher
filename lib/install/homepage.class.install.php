<?php
/**
* Lite Publisher
* Copyright (C) 2010, 2011 Vladimir Yushko http://litepublisher.com/
* Dual licensed under the MIT (mit.txt)
* and GPL (gpl.txt) licenses.
**/

function thomepageInstall($self) {
  litepublisher::$site->home = '/';
  $menus = tmenus::i();
  $menus->lock();
  $self->url = '/';
  $self->title = tlocal::get('default', 'home');
  $self->idview = tviews::i()->add(tlocal::get('names', 'home'));
  $homeview = tview::i($self->idview);
  $homeview->disableajax = true;
  $homeview->save();
  
  $menus->idhome = $menus->add($self);
  $menus->unlock();
}

function thomepageUninstall($self) {
  turlmap::unsub($self);
  $menus = tmenus::i();
  $menus->lock();
  unset($menus->items[$menus->idhome]);
  $menus->sort();
  $menus->unlock();
}

?>