<?php
/**
* Lite Publisher
* Copyright (C) 2010 Vladimir Yushko http://litepublisher.com/
* Dual licensed under the MIT (mit.txt)
* and GPL (gpl.txt) licenses.
**/

class titem extends tdata {
  public static $instances;
  //public $id;
  
  public static function iteminstance($class, $id = 0) {
    $name = call_user_func_array(array($class, 'getinstancename'), array());
    //echo "$name:$class:$id<br>\n";
    if (!isset(self::$instances)) self::$instances = array();
    if (isset(self::$instances[$name][$id]))     return self::$instances[$name][$id];
    $self = litepublisher::$classes->newitem($name, $class, $id);
    return $self->loaddata($id);
  }
  
  public function loaddata($id) {
    $this->data['id'] = $id;
    if ($id != 0) {
      if (!$this->load()) {
        $this->free();
        return false;
      }
      self::$instances[$this->instancename][$id] = $this;
    }
    return $this;
  }
  
  public function free() {
    unset(self::$instances[$this->instancename][$this->id]);
  }
  
  public function __construct() {
    parent::__construct();
    $this->data['id'] = 0;
  }
  
  public function __set($name, $value) {
    if (parent::__set($name, $value)) return true;
    return  $this->Error("Field $name not exists in class " . get_class($this));
  }
  
  public function setid($id) {
    if ($id != $this->id) {
      self::$instances[$this->instancename][$id] = $this;
      if (isset(   self::$instances[$this->instancename][$this->id])) unset(self::$instances[$this->instancename][$this->id]);
      $this->data['id'] = $id;
    }
  }
  
  public function request($id) {
    if ($id != $this->id) {
      $this->setid($id);
      if (!$this->load()) return 404;
    }
  }
  
  public static function deletedir($dir) {
    if (!@file_exists($dir)) return false;
    tfiler::delete($dir, true, true);
    @rmdir($dir);
  }
  
}

?>