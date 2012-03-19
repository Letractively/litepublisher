<?php
/**
* Lite Publisher
* Copyright (C) 2010, 2012 Vladimir Yushko http://litepublisher.com/
* Dual licensed under the MIT (mit.txt)
* and GPL (gpl.txt) licenses.
**/

class tgoogleregservice extends tregservice {

    public static function i() {
    return getinstance(__class__);
  }
  
  protected function create() {
    parent::create();
    $this->data['name'] = 'google';
$this->data['title'] = 'Google';
$this->data['icon'] = 'google.png';
$this->data['url'] = '/google-oauth2callback.php';
}

public function getauthurl() {
$url = 'https://accounts.google.com/o/oauth2/auth';
$url .= '?scope=https%3A%2F%2Fwww.googleapis.com%2Fauth%2Fuserinfo.email+https%3A%2F%2Fwww.googleapis.com%2Fauth%2Fuserinfo.profile&';
$url .= parent::getauthurl();
return $url;
}

//handle callback
  public function request($arg) {
if ($err = parent::request($arg)) return $err;
$code = $_REQUEST['code'];
$resp = self::http_post('https://accounts.google.com/o/oauth2/token', array(
'code' => $code,
'client_id' => $this->client_id,
'client_secret' => $this->client_secret,
'redirect_uri' => litepublisher::$site->url . $this->url,
'grant_type' => 'authorization_code'
));

if ($resp) {
$tokens  = json_decode($resp);
if ($r = http::get('https://www.googleapis.com/oauth2/v1/userinfo?access_token=' . $tokens->access_token)) {
$info = json_decode($r);
return $this->adduser(array(
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
$result .= $html->getinput('text', "client_id_$this->name", tadminhtml::specchars($this->client_id), $lang->client_id) ;
$result .= $html->getinput('text', "client_secret_$this->name", tadminhtml::specchars($this->client_secret), $lang->client_secret) ;
return $result;
}

}//class