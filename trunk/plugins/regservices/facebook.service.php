<?php
/**
* Lite Publisher
* Copyright (C) 2010, 2012 Vladimir Yushko http://litepublisher.com/
* Dual licensed under the MIT (mit.txt)
* and GPL (gpl.txt) licenses.
**/

class tfacebookregservice extends tregservice {

    public static function i() {
    return getinstance(__class__);
  }
  
  protected function create() {
    parent::create();
    $this->basename = 'regservices' . DIRECTORY_SEPARATOR . 'facebook';
$this->data['title'] = 'FaceBook';
$this->data['icon'] = 'facebook.png';
$this->data['url'] = '/facebook-oauth2callback.php';
}

public function getauthurl() {
$url = 'https://www.facebook.com/dialog/oauth?scope=email&';
$url .= parent::getauthurl();
return $url;
}

//handle callback
  public function request($arg) {
if ($err = parent::request($arg)) return $err;
$code = $_REQUEST['code'];
$resp = http::get('https://graph.facebook.com/oauth/access_token?' . http_build_query(array(
'code' => $code,
'client_id' => $this->client_id,
'client_secret' => $this->client_secret,
'redirect_uri' => litepublisher::$site->url . $this->url,
//'grant_type' => 'authorization_code'
)));

if ($resp) {
     $params = null;
     parse_str($resp, $params);

if ($r = http::get('https://graph.facebook.com/me?access_token=' . $params['access_token'])) {
$info = json_decode($r);
return $this->adduser(array(
'uniqid' => isset($info->id) ? $info->id : '',
'email' => isset($info->email) ? $info->email : '',
'name' => $info->name, 
'website' => isset($info->link) ? $info->link : ''
));
}
}

return $this->errorauth();
}

public function gettab($html, $args, $lang) {
$result = $html->p($lang->google_head . litepublisher::$site->url . $this->url);
$result .= $html->getinput('text', "client_id_$this->id", tadminhtml::specchars($this->client_id), $lang->client_id) ;
$result .= $html->getinput('text', "client_secret_$this->id", tadminhtml::specchars($this->client_secret), $lang->client_secret) ;
return $result;
}

}//class