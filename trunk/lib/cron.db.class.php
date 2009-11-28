<?php
/**
 * Lite Publisher 
 * Copyright (C) 2010 Vladimir Yushko http://litepublisher.com/
 * Dual licensed under the MIT (mit.txt) 
 * and GPL (gpl.txt) licenses.
**/

class tcron extends tevents {
  public $disableadd;
  
  public static function instance() {
    return getinstance(__class__);
  }
  
  protected function create() {
    parent::create();
$this->table = 'cron';
    $this->basename = 'cron' . DIRECTORY_SEPARATOR . 'index';
    $this->data['url'] = '';
    $this->data['path'] = '';
    $this->cache = false;
    $this->disableadd = false;
  }
  
  public function getpath() {
    global $paths;
    if (($this->data['path'] != '') && is_dir($this->data['path'])) {
      return  $this->data['path'];
    }
    return  $paths['data'];
  }
  
 public function request($arg) {
    if ($fh = @fopen($this->path .'cron.lok', 'w')) {
      flock($fh, LOCK_EX);
      ignore_user_abort(true);
      set_time_limit(60*20);
      $this->sendexceptions();
      $this->log("started loop");
      $this->execute();
      flock($fh, LOCK_UN);
      fclose($fh);
      $this->log("finished loop");
      $this->pop();
      return 'Ok';
    }
    return 'locked';
  }
  
  public function execute() {
    global $options;
    @ob_end_flush ();
    echo "<pre>\n";
while ($item = $this->db->getassoc("date <= now() order by date asc limit 1")) {
extract($item);
$arg = unserialize($arg);
$this->log("task started:\n{$class}->{$func}");

    if ($class == '' ) {
      if (function_exists($func)) {
      try {
        $func($arg);
      } catch (Exception $e) {
        $options->handexception($e);
      }
} else {
$this->db->iddelete($id);
continue;
}
    } elseif (class_exists($class)) {
      try {
        $obj = getinstance($class);
        $obj->$func($arg);
      } catch (Exception $e) {
        $options->handexception($e);
      }
} else {
$this->db->iddelete($id);
continue;
    }
if ($type == 'single') {
$this->db->iddelete($id);
} else {
$date = sqldate(strtotime("+1 $type"));
$this->db->setvalue($id, 'date', $date);
  }
}
}
  
  public function add($type, $class, $func, $arg = null) {
    if ($this->disableadd) return false;
$id = $this->db->add(array(
'date' => sqldate(),
'type' => $type,
'class' =>  $class, 
'func' => $func, 
'arg' => serialize($arg)
));

   if (($type == 'single') && !defined('cronpinged')) {
      define('cronpinged', true);
      register_shutdown_function('TCron::SelfPing');
    }
return $id;
  }
  
  public function delete($id) {
$this->db->iddelete($id);
  }
  
  public function deleteclass($class) {
$this->db->delete("class = '$class'");
  }
  
  public static function SelfPing() {
global $options;
try {
    $self = getinstance(__class__);

    $cronfile =$self->dir .  'crontime.txt';
    @file_put_contents($cronfile, ' ');
    @chmod($cronfile, 0666);
    
    $self->ping();
    } catch (Exception $e) {
      $options->handexception($e);
    }
  }
  
  public function ping() {
    global $options, $domain;
    $this->AddToChain($domain, $options->subdir . $this->url);
    $this->PingHost($domain, $options->subdir . $this->url);
  }
  
  private function PingHost($host, $path) {
    //$this->log("pinged host $host$path");
    if (		$socket = @fsockopen( $host, 80, $errno, $errstr, 0.10)) {
      fputs( $socket, "GET $path HTTP/1.0\r\nHost: $host\r\n\r\n");
    }
  }
  
  private function pop() {
    global $domain;
    $host = $domain;
    $filename = $this->path .'cronchain.php';
    if(!tfiler::unserialize($filename, $list))  return;
    if (isset($list[$host]))  unset($list[$host]);
    $item = array_splice($list, 0, 1);
    tfiler::serialize($filename, $list);
    if ($item) {
      $this->PingHost(key($item), $item[key($item)]);
    }
  }
  
  private function AddToChain($host, $path) {
    $filename = $this->path .'cronchain.php';
    if(!tfiler::unserialize($filename, $list)) {
      $list = array();
    }
    if (!isset($list[$host])) {
      $list[$host] = $path;
      tfiler::serialize($filename, $list);
    }
  }
  
  public function sendexceptions() {
    global $paths, $options;
    //���������, ���� ���� ����� ������ ����� ���� �����, �� ��� �������� �� �����
    $filename = $paths['data'] . 'logs' . DIRECTORY_SEPARATOR . 'exceptionsmail.log';
    $time = @filectime ($filename);
    if (($time === false) || ($time + 3600 > time())) return;
    $s = file_get_contents($filename);
    @unlink($filename);
    TMailer::SendAttachmentToAdmin("[error] $options->name", "See attachment", 'errors.txt', $s);
  }
  
  public function log($s) {
    echo date('r') . "\n$s\n\n";
    flush();
    if (defined('debug')) tfiler::log($s, 'cron.log');
  }
  
}//class

?>