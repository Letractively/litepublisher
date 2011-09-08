<?php
/**
* Lite Publisher
* Copyright (C) 2010, 2011 Vladimir Yushko http://litepublisher.com/
* Dual licensed under the MIT (mit.txt)
* and GPL (gpl.txt) licenses.
**/

function tsitemapInstall($self) {
  $cron = tcron::instance();
  $cron->addnightly(get_class($self),  'Cron', null);
  
  $urlmap = turlmap::instance();
  $urlmap->add('/sitemap.xml', get_class($self), 'xml');
  $urlmap->add('/sitemap.htm', get_class($self), null);
  
  $robots = trobotstxt ::instance();
  array_splice($robots->items, 1, 0, "Sitemap: " . litepublisher::$site->url . "/sitemap.xml");
  $robots->save();
  
  $self->add('/sitemap.htm', 4);
  $self->createfiles();
  
  $meta = tmetawidget::instance();
  $meta->add('sitemap', '/sitemap.htm', tlocal::get('default', 'sitemap'));
}

function tsitemapUninstall($self) {
  turlmap::unsub($self);
  $cron = tcron::instance();
  $cron->deleteclass($self);
  $meta = tmetawidget::instance();
  $meta->delete('sitemap');
}

?>