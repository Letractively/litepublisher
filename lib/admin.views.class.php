<?php
/**
* Lite Publisher
* Copyright (C) 2010, 2011 Vladimir Yushko http://litepublisher.com/
* Dual licensed under the MIT (mit.txt)
* and GPL (gpl.txt) licenses.
**/

class tadminviews extends tadminmenu {
  private $_adminoptionsform;
  
  public static function instance($id = 0) {
    return parent::iteminstance(__class__, $id);
  }
  
  public static function getviewform($url) {
    $html = tadminhtml ::instance();
    $html->section = 'views';
    $lang = tlocal::instance('views');
    $args = targs::instance();
    $args->url = litepublisher::$site->url . $url;
    $args->items = self::getcombo(tadminhtml::getparam('idview', 1));
    return $html->comboform($args);
  }
  
  public static function getcomboview($idview, $name = 'idview') {
    $lang = tlocal::instance('views');
    $theme = ttheme::instance();
    return strtr($theme->templates['content.admin.combo'], array(
    '$lang.$name' => $lang->view,
    '$name' => $name,
    '$value' => self::getcombo($idview)
    ));
  }
  
  public static function getcombo($idview) {
    $result = '';
    $views = tviews::instance();
    foreach ($views->items as $id => $item) {
      $result .= sprintf('<option value="%d" %s>%s</option>', $id,
      $idview == $id ? 'selected="selected"' : '', $item['name']);
    }
    return $result;
  }
  
  private function get_custom($idview) {
    $view = tview::instance($idview);
    if (count($view->custom) == 0) return '';
    $result = '';
    $html = $this->html;
    $customadmin = $view->theme->templates['customadmin'];
    foreach ($view->data['custom'] as $name => $value) {
      switch ($customadmin[$name]['type']) {
        case 'text':
        case 'editor':
        $value = tadminhtml::specchars($value);
        break;
        
        case 'checkbox':
        $value = $value ? 'checked="checked"' : '';
        break;
        
        case 'combo':
        $value = tadminhtml  ::array2combo($customadmin[$name]['values'], $value);
        break;
      }
      
      $result .= $html->getinput(
      $customadmin[$name]['type'],
    "custom_{$idview}_$name",
      $value,
      tadminhtml::specchars($customadmin[$name]['title'])
      );
    }
    return $result;
  }
  
  private function set_custom($idview) {
    $view = tview::instance($idview);
    if (count($view->custom) == 0) return;
    $customadmin = $view->theme->templates['customadmin'];
    foreach ($view->data['custom'] as $name => $value) {
      switch ($customadmin[$name]['type']) {
        case 'checkbox':
      $view->data['custom'][$name] = isset($_POST["custom_{$idview}_$name"]);
        break;
        
        default:
      $view->data['custom'][$name] = $_POST["custom_{$idview}_$name"];
        break;
      }
    }
  }
  
  public function getspecclasses() {
    return array('thomepage', 'tarchives', 'tnotfound404', 'tsitemap');
  }
  
  public function gethead() {
    $result = parent::gethead();
    $template = ttemplate::instance();
    switch ($this->name) {
      case 'views':
      $template->ltoptions[] = sprintf('allviews: [%s]', implode(',', array_keys(tviews::instance()->items)));
    $result .= $template->getloadjavascript('"$site.files/js/litepublisher/admin.views.min.js", function() {init_views();}' );
      /*
      $result .= '<script type="text/javascript" src="$site.files/js/litepublisher/admin.views.js"></script>
      <script type="text/javascript" >
      init_views();
      </script>';
      */
      break;
      
      
      case 'spec':
    $result .= $template->getready('$("#tabs").tabs({ cache: true });');
      break;
    }
    return $result;
  }
  
  private function get_view_sidebars($idview) {
    $view = tview::instance($idview);
    $widgets = twidgets::instance();
    $html = $this->html;
    $html->section = 'views';
    $lang = tlocal::instance('views');
    $args = targs::instance();
    $args->idview = $idview;
    $args->adminurl = tadminhtml::getadminlink('/admin/views/widgets/', 'idwidget');
    $view_sidebars = '';
    $widgetoptions = '';
    $count = count($view->sidebars);
    $sidebarnames = range(1, 3);
    $parser = tthemeparser::instance();
    $about = $parser->getabout($view->theme->name);
    foreach ($sidebarnames as $key => $value) {
      if (isset($about["sidebar$key"])) $sidebarnames[$key] = $about["sidebar$key"];
    }
    
    if (($idview > 1) && !$view->customsidebar) $view = tview::instance(1);
    foreach ($view->sidebars as $index => $sidebar) {
      $args->index = $index;
      $widgetlist = '';
      $idwidgets = array();
      foreach ($sidebar as $_item) {
        $id = $_item['id'];
        $idwidgets[] = $id;
        $widget = $widgets->getitem($id);
        $args->id = $id;
        $args->ajax = $_item['ajax'] ? true : false;
        $args->inline = $_item['ajax'] === 'inline';
        $args->disabled = ($widget['cache'] == 'cache') || ($widget['cache'] == 'nocache') ? '' : 'disabled="disabled"';
        $args->add($widget);
        $widgetlist .= $html->widgetitem($args);
        $widgetoptions .= $html->widgetoption($args);
      }
      $args->sidebarname = $sidebarnames[$index];
      $args->items = $widgetlist;
      $args->idwidgets = implode(',', $idwidgets);
      $view_sidebars .= $html->view_sidebar($args);
    }
    
    $args->view_sidebars = $view_sidebars;
    $args->widgetoptions = $widgetoptions;
    $args->id = $idview;
    return $html->view_sidebars($args);
  }
  
  private function get_view_theme($idview) {
    $view = tview::instance($idview);
    $lang = tlocal::instance('themes');
    return str_replace('theme_idview', 'theme_' . $idview,
    tadminthemes::getlist($this->html->radiotheme, $view->theme->name));
  }
  
  public function getcontent() {
    $result = '';
    $views = tviews::instance();
    $html = $this->html;
    $lang = tlocal::instance('views');
    $args = targs::instance();
    switch ($this->name) {
      case 'views':
      $items = '';
      $content = '';
      foreach ($views->items as $id => $itemview) {
        $args->add($itemview);
        $items .= $html->itemview($args);
        $args->view_sidebars = $this->get_view_sidebars($id);
        $args->view_theme = $this->get_view_theme($id);
        $html->section = 'views';
        $args->view_custom = $this->get_custom($id);
        $content .= $html->viewtab($args);
      }
      $lang->section = 'views';
      $args->items = $items;
      $args->content = $content;
      
      $widgetlist = '';
      $widgets = twidgets::instance();
      foreach ($widgets->items as $id => $item) {
        $args->id = $id;
        $args->add($item);
        $widgetlist .= $html->addwidget($args);
      }
      $args->widgetlist=  $widgetlist;
      $result = $html->allviews($args);
      break;
      
      case 'addview':
      $args->formtitle = $lang->addview;
      $result .= $html->adminform('[text=name]', $args);
      break;
      case 'spec':
      $items = '';
      $content = '';
      foreach (self::getspecclasses() as $classname) {
        $obj = getinstance($classname);
        $args->classname = $classname;
        $name = substr($classname, 1);
      $args->title = $lang->{$name};
        $inputs = self::getcomboview($obj->idview, "idview-$classname");
        if (isset($obj->data['keywords'])) $inputs .= $html->getedit("keywords-$classname", $obj->keywords, $lang->keywords);
        if (isset($obj->data['description'])) $inputs .= $html->getedit("description-$classname", $obj->description, $lang->description);
        $args->inputs = $inputs;
        $items .= $html->spectab($args);
        $content .=$html->specform($args);
      }
      
      $args->items = $items;
      $args->content = $content;
      $args->formtitle = $lang->defaults;
      $result .= $html->adminform($html->spectabs, $args);
      break;
      
      case 'group':
      $args->formname = 'posts';
      $args->formtitle = $lang->viewposts;
      $args->items = self::getcomboview($views->defaults['post'], 'postview');
      $result .= $html->groupform($args);
      
      $args->formname = 'menus';
      $args->formtitle = $lang->viewmenus;
      $args->items = self::getcomboview($views->defaults['menu'], 'menuview');
      $result .= $html->groupform($args);
      
      $args->formname = 'themes';
      $args->formtitle = $lang->themeviews;
      $view = tview::instance();
      $list =    tfiler::getdir(litepublisher::$paths->themes);
      sort($list);
      $themes = array_combine($list, $list);
      $args->items = $html->getcombo('themeview', tadminhtml::array2combo($themes, $view->themename), $lang->themename);
      $result .= $html->groupform($args);
      break;
      
      case 'defaults':
      $items = '';
      $theme = ttheme::instance();
      $tml = $theme->templates['content.admin.combo'];
      foreach ($views->defaults as $name => $id) {
        $args->name = $name;
        $args->value = self::getcombo($id);
        $args->data['$lang.$name'] = $lang->$name;
        $items .= $theme->parsearg($tml, $args);
      }
      $args->items = $items;
      $args->formtitle = $lang->defaultsform;
      $result .= $theme->parsearg($theme->content->admin->form, $args);
      break;
      
      case 'headers':
      $template = ttemplate::instance();
      $args->heads = $template->heads;
      $args->formtitle = $lang->headstitle;
      $result = $html->adminform('[editor=heads]', $args);
      break;
      
      case 'admin':
      return $this->adminoptionsform->getform();;
    }
    
    return $html->fixquote($result);
  }
  
  public function processform() {
    $result = '';
    switch ($this->name) {
      case 'views':
      /*
      echo "<pre>\n";
      var_dump($_POST);
      echo "\n</pre>\n";
      */
      $views = tviews::instance();
      switch ($this->action) {
        case 'delete':
        $idview = (int) $_POST['action_value'];
        if (($idview > 1) && $views->itemexists($idview)) $views->delete($idview);
        break;
        
        case 'widgets':
        $views->lock();
        $widgets = twidgets::instance();
        foreach ($views->items as $id => $item) {
          $view = tview::instance($id);
          if ($id > 1) {
            $view->customsidebar = isset($_POST["customsidebar_$id"]);
            $view->disableajax = isset($_POST["disableajax_$id"]);
          }
          $view->name = trim($_POST["name_$id"]);
          $view->themename = trim($_POST["theme_$id"]);
          $this->set_custom($id);
          if (($id == 1) || $view->customsidebar) {
            foreach (range(0, 2) as $index) {
              $view->sidebars[$index] = array();
            $sidebar = explode(',', trim($_POST["widgets_{$id}_$index"]));
              foreach($sidebar as $idwidget) {
                $idwidget = (int) trim($idwidget);
                if ($widgets->itemexists($idwidget)) {
                  $view->sidebars[$index][] = array(
                  'id' => $idwidget,
              'ajax' =>isset($_POST["inline_{$id}_$idwidget"]) ? 'inline' : isset($_POST["ajax_{$id}_$idwidget"])
                  );
                }
              }
            }
          }
        }
        $views->unlock();
        break;
      }
      break;
      
      case 'addview':
      $name = trim($_POST['name']);
      if ($name != '') {
        $views = tviews::instance();
        $id = $views->add($name);
      }
      break;
      case 'spec':
      foreach (self::getspecclasses() as $classname) {
        $obj = getinstance($classname);
        $obj->lock();
        $obj->setidview($_POST["idview-$classname"]);
        if (isset($obj->data['keywords'])) $obj->keywords = $_POST["keywords-$classname"];
        if (isset($obj->data['description '])) $obj->description = $_POST["description-$classname"];
        $obj->unlock();
      }
      break;
      
      case 'group':
      //find action
      foreach ($_POST as $name => $value) {
        if (strbegin($name, 'action_')) {
          $action = substr($name, strlen('action_'));
          break;
        }
      }
      
      switch ($action) {
        case 'posts':
        $posts = tposts::instance();
        $idview = (int) $_POST['postview'];
        if (dbversion) {
          $posts->db->update("idview = '$idview'", 'id > 0');
        } else {
          foreach ($posts->items as $id => $item) {
            $post = tpost::instance($id);
            $post->idview = $idview;
            $post->save();
            $post->free();
          }
        }
        break;
        
        case 'menus':
        $idview = (int) $_POST['menuview'];
        $menus = tmenus::instance();
        foreach ($menus->items as $id => $item) {
          $menu = tmenu::instance($id);
          $menu->idview = $idview;
          $menu->save();
        }
        break;
        
        case 'themes':
        $themename = $_POST['themeview'];
        $views = tviews::instance();
        $views->lock();
        foreach ($views->items as $id => $item) {
          $view = tview::instance($id);
          $view->themename = $themename;
          $view->save();
        }
        $views->unlock();
        break;
      }
      break;
      
      case 'defaults':
      $views = tviews::instance();
      foreach ($views->defaults as $name => $id) {
        $views->defaults[$name] = (int) $_POST[$name];
      }
      $views->save();
      break;
      
      case 'headers':
      $template = ttemplate::instance();
      $template->heads = $_POST['heads'];
      $template->save();
      break;
      
      case 'admin':
      return $this->adminoptionsform->processform();
    }
    
    ttheme::clearcache();
  }
  
  public function getadminoptionsform() {
    if (isset($this->_adminoptionsform)) return $this->_adminoptionsform;
    $form = new tautoform(tajaxposteditor ::instance(), 'views', 'adminoptions');
    $form->add($form->ajaxvisual, $form->visual);
    $form->obj = tadminmenus::instance();
    $form->add($form->heads('editor'));
    $this->_adminoptionsform = $form;
    return $form;
  }
  
}//class

?>