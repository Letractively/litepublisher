<?php
/**
* Lite Publisher
* Copyright (C) 2010, 2012 Vladimir Yushko http://litepublisher.com/
* Dual licensed under the MIT (mit.txt)
* and GPL (gpl.txt) licenses.
**/

/* to prevent recurse call */
function installoptions($language) {
  $options = toptions::i();
  $options->lock();
  if (dbversion) {
    $usehost = $_REQUEST['usehost'] == '1';
    $options->data['dbconfig'] = array(
    'driver' => 'mysql',
    'host' => $usehost ? $_REQUEST['dbhost'] : 'localhost',
    'port' => $usehost ? (int) $_REQUEST['dbport'] : 0,
    'dbname' => $_REQUEST['dbname'],
    'login' => $_REQUEST['dblogin'],
    'password' => base64_encode(str_rot13 ($_REQUEST['dbpassword'])),
    'prefix' => $_REQUEST['dbprefix']
    );
    try {
      litepublisher::$db= new tdatabase();
    } catch (Exception $e) {
      die($e->GetMessage());
    }
    /*
    $db = litepublisher::$db;
    $list = $db->res2array($db->query("show tables from " . $options->dbconfig['dbname']));
    foreach ($list as $row) {
      $db->exec("DROP TABLE IF EXISTS ". $row[0]);
    }
    */
  }
  
  $options->language = $language;
  
  $options->dateformat = '';
  $options->login = 'admin';
  $options->password = '';
  $options->realm = 'Admin panel';
  $password = md5uniq();
  $options->changepassword($password);
  $options->cookieenabled = true;
  $options->cookie = '';
  $options->cookieexpired = 0;
  
  $options->email = 'yarrowsoft@gmail.com';
  $options->mailer = '';
  $options->data['cache'] = true;
  $options->expiredcache= 3600;
  $options->admincache = false;
  $options->ob_cache = true;
  $options->compress = false;
  $options->filetime_offset = tfiler::get_filetime_offset();
  $options->data['perpage'] = 10;
  $options->filtercommentstatus = true;
  $options->DefaultCommentStatus = 'approved';
  $options->commentsdisabled = false;
  $options->commentsenabled = true;
  $options->pingenabled = true;
  $options->commentpages = true;
  $options->commentsperpage = 100;
  $options->checkduplicate = true;
  $options->defaultsubscribe = true;
  $options->autocmtform = true;
  $options->comments_invert_order = false;
  
  $versions = strtoarray(file_get_contents(dirname(__file__) . DIRECTORY_SEPARATOR . 'versions.txt'));
  $options->version = $versions[0];
  $options->echoexception = true;
  $options->parsepost = true;
  $options->hidefilesonpage = false;
  $options->show_draft_post = false;
  $options->usersenabled = false;
  $options->reguser = false;
  $options->icondisabled = false;
  $options->crontime = time();
$options->show_file_perm = false;
  $options->unlock();
  return $password;
}