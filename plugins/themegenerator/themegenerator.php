<?php
/**
* Lite Publisher
* Copyright (C) 2010, 2011 Vladimir Yushko http://litepublisher.com/
* Dual licensed under the MIT (mit.txt)
* and GPL (gpl.txt) licenses.
**/

class tthemegenerator extends tmenu {
  public $colors;
  private $type;
  private $colorsuploaded;
  
  public static function i($id = 0) {
    return self::singleinstance(__class__);
  }
  
  protected function create() {
    parent::create();
    $this->cache = false;
    $this->type = 'midle';
    $this->data['values'] =  array();
    $this->data['selectors'] = array();
    $this->data['leftview'] = 0;
    $this->data['rightview'] = 0;
    $this->colors = array();
    $this->colorsuploaded = false;
  }
  
  public function cron() {
    tfiler::callback(array($this, 'deleteold'), litepublisher::$paths->files . 'themegen', false);
  }
  
  public function deleteold($filename) {
    if (@filectime ($filename) + 24*3600 < time()) unlink($filename);
  }
  
  public function parseselectors() {
    $this->data['selectors'] = array();
    $s = file_get_contents(dirname(__file__) . DIRECTORY_SEPARATOR . 'res' . DIRECTORY_SEPARATOR   . 'scheme.tml');
    $lines = explode("\n", str_replace(array("\r\n", "\r"), "\n", trim($s)));
    foreach ($lines as $line) {
      $line = trim($line);
      if ($line == '') continue;
      $css = explode('{', $line);
        $sel = rtrim($css[0]);
      $proplist = explode(';', trim($css[1], '{}; '));
        $props = array();
        foreach ($proplist as $v) {
          $v =trim($v, '; ');
          if ($v == '') continue;
          $prop = explode(':', $v);
          $propname = trim($prop[0]);
          $propvalue =trim($prop[1]);
          if (preg_match_all('/%%(\w*+)%%/', $propvalue, $m, PREG_SET_ORDER)) {
            foreach ($m as $item) {
              $this->data['selectors'][] = array(
              'name' => $item[1],
              'sel' => $sel,
              'propname' => $propname,
              'value' => $propvalue
              );
            }
          }
        }
      }
      $this->save();
    }
    
    public function gethead() {
      $pickerpath = litepublisher::$site->files . '/plugins/colorpicker/';
      $result =   '<link type="text/css" href="' . $pickerpath . 'css/colorpicker.css" rel="stylesheet" />';
      
      $template = ttemplate::i();
      $template->ltoptions['colors'] = $this->data['selectors'];
      $result .= $template->getjavascript($template->jsmerger_themegenerator);
      //$result .= $template->getjavascript('/plugins/colorpicker/js/colorpicker.js');
      //$result .= $template->getjavascript('/js/swfupload/swfupload.js');
      //$result .= $template->getjavascript(sprintf('/plugins/%s/themegenerator.min.js', basename(dirname(__file__))));
      
      if ($this->colorsuploaded) {
        $args = new targs();
        foreach ($this->data['colors'] as $name => $value) {
          $args->$name = $value;
        }
        $res = dirname(__file__) . DIRECTORY_SEPARATOR  . 'res' . DIRECTORY_SEPARATOR ;
        $css = strtr(file_get_contents($res . 'scheme.tml'), $args->data);
        $result .= "<style type=\"text/css\">\n$css</style>\n";
      }
      return $result;
    }
    
    public function getidview() {
      switch ($this->type) {
        case 'left':
        return$this->leftview;
        
        case 'right':
        return $this->rightview;
        
        default:
        return parent::getidview();
      }
    }
    
    public function request($arg) {
      //$this->parseselectors();
      $this->type = isset($_GET['type']) ? trim($_GET['type']) : 'midle';
      tlocal::usefile('themegenerator');
      $lang = tlocal::i('themegenerator');
      $this->colors = $lang->ini['themecolors'];
      parent::request($arg);
      if (isset($_POST['formtype']) && (($_POST['formtype'] == 'headerurl') || ($_POST['formtype'] == 'logourl'))) return $this->formresult;
    }
    
    /*
    public function getcontent() {
      $result = '';
      tlocal::usefile('themegenerator');
      $lang = tlocal::i('themegenerator');
      
      $tml = '<p>
      <input type="button" name="colorbutton-$name" id="colorbutton-$name" rel="$name" value="' . $lang->selectcolor . '" />
      <input type="hidden" name="color_$name" id="text-color-$name" value="$value" />
      <strong>$label</strong></p>';
      
      $theme = tview::i($this->idview)->theme;
      $args = new targs();
      $a = new targs;
      foreach ($this->colors as $name => $value) {
        $args->name = $name;
        $args->value = $value;
        $args->label = $lang->$name;
        $a->$name = $theme->parsearg($tml, $args);
      }
      
      $a->headerurl = $this->colors['headerurl'];
      $a->logourl = $this->colors['logourl'];
      
      $form = file_get_contents(dirname(__file__) . DIRECTORY_SEPARATOR  . 'res' . DIRECTORY_SEPARATOR  . 'form.tml');
      $result .= $theme->parsearg($form, $a);
      return $theme->simple($result);
    }
    */
    
    public function setcolor($name, $value) {
      if (isset($this->colors[$name])) {
        $value = trim($value);
        if (strend($name, 'url') || preg_match('/^[0-9a-zA-Z]*+$/', $value)) {
          $this->colors[$name] = $value;
        }
      }
    }
    
    public function processform() {
      switch ($_POST['formtype']) {
        case 'colors':
        
        foreach ($_POST as $name => $value) {
          if (strbegin($name, 'color_')) {
            $name = substr($name, strlen('color_'));
            $this->setcolor($name, $value);
          }
        }
        $this->sendfile();
        break;
        
        case 'uploadcolors':
        if (isset($_FILES['filename'])) {
          if (isset($_FILES['filename']['error']) && $_FILES['filename']['error'] > 0) {
            $lang = tlocal::admin('uploaderrors');
            return sprintf('<h4>%s</h4>', $lang->__get($_FILES['filename']['error']));
          } elseif (!is_uploaded_file($_FILES['filename']['tmp_name'])) {
            return sprintf('<h4>%s</h4>', $lng['attack']);
          } else {
            $this->colorsuploaded = true;
            $colors = parse_ini_file($_FILES['filename']['tmp_name']);
            foreach ($colors as $name => $value) {
              $this->setcolor($name, $value);
            }
          }
        }
        break;
        
        case 'headerurl':
        if (!isset($_FILES['Filedata']) || !is_uploaded_file($_FILES['Filedata']['tmp_name']) ||
        $_FILES['Filedata']['error'] != 0) return 403;
        
        if ($result = $this->imageresize($_FILES['Filedata']['name'], $_FILES['Filedata']['tmp_name'], $this->colors['headerwidth'], $this->colors['headerheight'])) {
          return turlmap::htmlheader(false) . $result;
        }
        return 403;
        
        case 'logourl':
        if (!isset($_FILES['Filedata']) || !is_uploaded_file($_FILES['Filedata']['tmp_name']) ||
        $_FILES['Filedata']['error'] != 0) return 403;
        
        if ($result = $this->uploadlogo($_FILES['Filedata']['name'], $_FILES['Filedata']['tmp_name'], $this->colors['logowidth'], $this->colors['logoheight'])) {
          return turlmap::htmlheader(false) . $result;
        }
        return 403;
      }
      
      return '';
    }
    
    public function sendfile() {
      $u = time();
      $path = "themes/generator$u/";
      
      require_once(litepublisher::$paths->libinclude . 'zip.lib.php');
      $zip = new zipfile();
      
      $themedir = litepublisher::$paths->themes . 'generator' . DIRECTORY_SEPARATOR;
      $args = new targs();
      $colors = "[themecolors]\n";
      foreach ($this->colors as $name => $value) {
        $colors .= "$name = \"$value\"\n";
        $args->$name = $value;
      }
      foreach (array('headerurl', 'logourl') as $name) {
        if (strbegin($this->colors[$name], 'http://')) {
          $basename = substr($this->colors[$name], strrpos($this->colors[$name], '/') + 1);
          $filename = litepublisher::$paths->files . 'themegen' . DIRECTORY_SEPARATOR . $basename;
          $zip->addFile(file_get_contents($filename), $path . 'images/' . $basename);
          $args->$name = 'images/' . $basename;
        }
      }
      
      $res = dirname(__file__) . DIRECTORY_SEPARATOR  . 'res' . DIRECTORY_SEPARATOR ;
      $css = strtr(file_get_contents($res . 'scheme.tml'), $args->data);
      
      $zip->addFile($colors, $path . 'colors.ini');
      
      $filelist = tfiler::getfiles($themedir);
      foreach ($filelist as $filename) {
        $content = file_get_contents($themedir . $filename);
        switch ($filename) {
          case 'style.css':
          $content .= $css;
          break;
          
          case 'about.ini':
          $content = str_replace('name = generator', "name = generator$u", $content);
          break;
        }
        
        $zip->addFile($content, $path . $filename);
      }
      
      $themedir .= 'images' . DIRECTORY_SEPARATOR ;
      $filelist = tfiler::getfiles($themedir);
      foreach ($filelist as $filename) {
        $zip->addFile(file_get_contents($themedir . $filename), $path . 'images/' . $filename);
      }
      
      $result = $zip->file();
      
      if (ob_get_level()) @ob_end_clean ();
      header('HTTP/1.1 200 OK', true, 200);
      header('Content-type: application/octet-stream');
      header('Content-Disposition: attachment; filename=generator.theme.' . $u . '.zip');
      header('Content-Length: ' .strlen($result));
      header('Last-Modified: ' . date('r'));
      Header( 'Cache-Control: no-cache, must-revalidate');
      Header( 'Pragma: no-cache');
      
      echo $result;
      exit();
    }
    
    public function imageresize($name, $filename, $x, $y) {
      if (!($source = tmediaparser::readimage($filename))) return false;
      $sourcex = imagesx($source);
      $sourcey = imagesy($source);
      if (($x >= $sourcex) && ($y >= $sourcey)) {
        if (!($result = tmediaparser::move_uploaded($name, $filename, 'themegen'))) return false;
        @chmod(litepublisher::$paths->files . str_replace('/', DIRECTORY_SEPARATOR, $result), 0666);
        return litepublisher::$site->files . '/files/' . $result;
      }
      
      $result = tmediaparser::prepare_filename($name, 'themegen');
      $realfilename = litepublisher::$paths->files . str_replace('/', DIRECTORY_SEPARATOR, $result);
      
      $ratio = $sourcex / $sourcey;
      if ($x/$y > $ratio) {
        $x = $y *$ratio;
      } else {
        $y = $x /$ratio;
      }
      
      $dest = imagecreatetruecolor($x, $y);
      imagecopyresampled($dest, $source, 0, 0, 0, 0, $x, $y, $sourcex, $sourcey);
      switch (substr($result, strrpos($result, '.')+ 1)) {
        case 'jpg':
        imagejpeg($dest, $realfilename, 100);
        break;
        
        case 'png':
        imagepng($dest, $realfilename);
        break;
        
        case 'gif':
        imagegif($dest, $realfilename);
        break;
        
        default:
        $realfilename .= '.jpg';
        $result .= '.jpg';
        imagejpeg($dest, $realfilename, 100);
      }
      
      imagedestroy($dest);
      imagedestroy($source);
      
      @chmod($realfilename, 0666);
      return litepublisher::$site->files . '/files/'. $result;
    }
    
    public function uploadlogo($name, $filename, $x, $y) {
      if (!($source = tmediaparser::readimage($filename))) return false;
      $sourcex = imagesx($source);
      $sourcey = imagesy($source);
      if (($x == $sourcex) && ($y == $sourcey)) {
        if (!($result = tmediaparser::move_uploaded($name, $filename, 'themegen'))) return false;
        @chmod(litepublisher::$paths->files . str_replace('/', DIRECTORY_SEPARATOR, $result), 0666);
        return litepublisher::$site->files . '/files/' . $result;
      }
      
      $result = tmediaparser::prepare_filename($name, 'themegen');
      $realfilename = litepublisher::$paths->files . str_replace('/', DIRECTORY_SEPARATOR, $result);
      
      $dest = imagecreatetruecolor($x, $y);
      imagecopyresampled($dest, $source, 0, 0, 0, 0, $x, $y, $sourcex, $sourcey);
      
      if ('png' != substr($result, strrpos($result, '.')+ 1)){
        $index = imagecolorexact($dest, 255, 255, 255);
        if ($index == -1) {
          $index = imagecolorallocate($dest, 255, 255, 255);
        }
        imagecolortransparent($dest, $index);
      }
      
      imagepng($dest, $realfilename);
      
      imagedestroy($dest);
      imagedestroy($source);
      
      @chmod($realfilename, 0666);
      return litepublisher::$site->files . '/files/'. $result;
    }
    
  }//class