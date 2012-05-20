<?php
/**
* Lite Publisher
* Copyright (C) 2010, 2012 Vladimir Yushko http://litepublisher.com/
* Dual licensed under the MIT (mit.txt)
* and GPL (gpl.txt) licenses.
**/

function tregservicesInstall($self) {
  $dir = litepublisher::$paths->data . 'regservices';
  @mkdir($dir, 0777);
  @chmod($dir, 0777);
  
  $about = tplugins::getabout(tplugins::getname(__file__));
  $self->lock();
  $self->widget_title  = sprintf('<h4>%s</h4>', $about['widget_title']);
  $name = basename(dirname(__file__));
  litepublisher::$classes->add('tregservice', 'service.class.php', $name);
  litepublisher::$classes->add('tregserviceuser', 'service.class.php', $name);
  litepublisher::$classes->add('tgoogleregservice', 'google.service.php', $name);
  litepublisher::$classes->add('tfacebookregservice', 'facebook.service.php', $name);
  litepublisher::$classes->add('ttwitterregservice', 'twitter.service.php', $name);
  litepublisher::$classes->add('tmailruregservice', 'mailru.service.php', $name);
  litepublisher::$classes->add('tyandexregservice', 'yandex.service.php', $name);
  litepublisher::$classes->add('tvkontakteregservice', 'vkontakte.service.php', $name);
  
  litepublisher::$classes->add('toauth', 'oauth.class.php', $name);

  $self->add(tgoogleregservice::i());
  $self->add(tfacebookregservice::i());
  $self->add(ttwitterregservice::i());
  $self->add(tmailruregservice::i());
  $self->add(tyandexregservice::i());
  $self->add(tvkontakteregservice::i());
  
  $self->unlock();
  
  tusers::i()->deleted = tregserviceuser::i()->delete;
  if (dbversion) {
    $names =implode("', '", array_keys($self->items));
    tdbmanager::i()->createtable('regservices',
    "id int unsigned NOT NULL default 0,
    service enum('$names') default 'google',
    uid varchar(128) NOT NULL default '',
    
    key `id` (`id`),
    KEY (`service`, `uid`)
    ");
  }
  
  litepublisher::$urlmap->addget($self->url, get_class($self));
tcommentform::i()->oncomuser = $self->oncomuser;
  litepublisher::$urlmap->clearcache();
}

function tregservicesUninstall($self) {
tcommentform::i()->unbind($self);
  turlmap::unsub($self);
  foreach ($self->items as $id => $classname) {
    litepublisher::$classes->delete($classname);
  }
  
  litepublisher::$classes->delete('tregserviceuser');
  litepublisher::$classes->delete('toauth');
  
  tfiler::delete(litepublisher::$paths->data . 'regservices', true, true);
  
    tusers::i()->unbind('tregserviceuser');
    tdbmanager::i()->deletetable('regservices');
}