<?php
class TDatabase extends PDO {
  public $result;
  public $sql;
  public $table;
  public $prefix;
  public $history;
  
  public function __construct() {
    global $options;
$dbconfig = $options->dbconfig;
    $this->table = '';
    $this->prefix =  $dbconfig['prefix'];
    $this->sql = '';
    $this->history = array();
    
    try {
parent::__construct("{$dbconfig['driver']}:host={$dbconfig['host']};dbname={$dbconfig['dbname']}", $dbconfig['login'], $dbconfig['password'],
array(PDO::ATTR_ERRMODE => PDO::ERRMODE_WARNING)
);
    } catch (Exception $e) {
      die($e->getMessage());
    }
    $this->exec('SET NAMES utf8');
$timezone = date('Z') / 3600;
if ($timezone > 0) $timezone = "+$timezone";
    $this->exec("SET time_zone = '$timezone:00'");
  }
  
  public function query($sql, $mode = null) {
    $this->sql = $sql;
    if (defined('debug')) $this->history[] = $sql;
    if (is_object ($this->result))  {
      $this->result->closeCursor();
    }
    $this->result = parent::query($sql, $mode);
    return $this->result;
  }
  
  public function exec($sql) {
    $this->sql = $sql;
    if (defined('debug')) $this->history[] = $sql;
    if (is_object($this->result)) $this->result->closeCursor();
    $this->result = parent::exec($sql);
    return $this->result;
  }
  
  public function SelectTableWhere($table, $where) {
    return $this->query("SELECT * FROM $this->prefix$table WHERE ($where)");
  }
  
  public function select($where) {
    return $this->query("SELECT * FROM $this->prefix$this->table WHERE  $where");
  }
  
  public function getassoc($where) {
    if ($res = $this->select($where)) {
      return $res->fetch(PDO::FETCH_ASSOC);
    }
    return false;
  }
  
  public function update($values, $where) {
    return $this->exec("update $this->prefix$this->table set " . $values  ." where $where");
  }
  
  public function idupdate($id, $values) {
    return $this->update($values, "id = $id");
  }
  
  public function UpdateAssoc($a) {
    $list = array();
    foreach ($a As $name => $value) {
      if ($name == 'id') continue;
      $list[] = "$Name = " . $this->quote($value);
    }
    
    return $this->update(implode(', ', $list), 'id = '. $a['id']);
  }
  
  public function UpdateProps($obj, $props) {
    $list = array();
    foreach ($props  As $name) {
      if ($name == 'id') continue;
      $list[] = "$Name = " . $this->quote($obj->$name);
    }
    
    return $this->update(implode(', ', $list), "id = $obj->id");
  }
  
  public function InsertRow($row) {
    $this->exec("INSERT INTO $this->prefix$this->table $row");
    return $this->lastInsertId();
  }
  
  public function InsertAssoc(&$a) {
    $Names =implode(', ', array_keys($a));
    $vals = array();
    foreach( $a as $name => $val) {
      if ($name == 'id') continue;
      $vals[] = $this->quote($val);
    }
    $Values = implode(', ', $vals);
    
    $this->exec("INSERT INTO $this->prefix$this->table ($Names) values (" . $Values . ')');
    return $this->lastInsertId();
  }
  
  public function add($a) {
    if  ($this->IdExists($a['id'])) {
      return $this->UpdateAssoc($a);
    } else {
      return $this->InsertAssoc($a);
    }
  }
  
  public function getcount($where = '') {
    $sql = "SELECT COUNT(*) as count FROM $this->prefix$this->table";
    if ($where != '') $sql .= ' where '. $where;
    if ($res = $this->query($sql)) {
      $r = $res->fetch(PDO::FETCH_ASSOC);
      return $r['count'];
    }
    return false;
  }
  
  public function delete($where) {
    return $this->exec("delete from $this->prefix$this->table where $where");
  }
  
  public function idvalue($id, $name) {
    if ($res = $this->query("select $name from $this->prefix$this->table where id = $id limit 1")) {
      $r = $res->fetch(PDO::FETCH_ASSOC);
      return $r[$name];
    }
    return false;
  }
  
  public function res2array($res) {
    $result = array();
    while ($row = $res->fetch(PDO::FETCH_NUM)) {
      $result[] = $row[0];
    }
    return $result;
  }
  
}//class
?>