<?php
/**
 * Lite Publisher 
 * Copyright (C) 2010 Vladimir Yushko http://litepublisher.com/
 * Dual licensed under the MIT (mit.txt) 
 * and GPL (gpl.txt) licenses.
**/


function tpostcontentpluginInstall($self) {
$posts = tposts::instance();
$posts->lock();
  $posts->beforecontent = $self->beforecontent;
$posts->aftercontent = $self->aftercontent;
$posts->unlock();
 }
 
function tpostcontentpluginUninstall($self) {
tposts::unsub($self);
 }

?>