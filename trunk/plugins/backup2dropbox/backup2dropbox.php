<?php
/**
* Lite Publisher
* Copyright (C) 2010, 2011 Vladimir Yushko http://litepublisher.com/
* Dual licensed under the MIT (mit.txt)
* and GPL (gpl.txt) licenses.
**/

class tbackup2dropbox extends tplugin {
  
  public static function instance() {
    return getinstance(__class__);
  }
  
  protected function create() {
    parent::create();
    $this->data['idcron'] = 0;
    $this->data['email'] = '';
    $this->data['password'] = '';
    $this->data['dir'] = '/';
    $this->data['uploadfiles'] = true;
    $this->data['onlychanged'] = false;
    $this->data['posts'] = 0;
    $this->data['comments'] = 0;
  }
  
  public function send() {
    if ($this->password == '') return;
    if ($this->onlychanged) {
      if (($this->posts ==litepublisher::$classes->posts->count) && ($this->comments == litepublisher::$classes->commentmanager->count)) return;
      $this->posts =litepublisher::$classes->posts->count;
      $this->comments = litepublisher::$classes->commentmanager->count;
      $this->save();
    }
    
    $backuper = tbackuper::instance();
    $filename  = $backuper->createbackup();
    
    require_once(dirname(__file__) . DIRECTORY_SEPARATOR . 'DropboxUploader.php');
    
    $uploader = new DropboxUploader($this->email, $this->password);
    try {
      set_time_limit(600);
      $uploader->upload($filename, $this->dir);
      unlink($filename);
      //if ($this->uploadfiles)
      $this->uploadfiles($uploader, '');
    } catch (Exception $e) {
      return $e->getMessage();
    }
    return true;
  }
  
  private function uploadfiles(DropboxUploader $uploader, $dir) {
    if ($dir != '') $dir = trim($dir, '/') . '/';
    if ($list = glob(litepublisher::$paths->files . $dir . '*')) {
      foreach($list as $filename) {
        if (is_dir($filename)) {
          $base = basename($filename);
          if ($base[0] == '.') continue;
          $this->uploadfiles($uploader, $base);
        } else {
          $uploader->upload($filename, $this->dir . 'files/' . $dir);
        }
      }
    }
    
  }
  
}//class

?>