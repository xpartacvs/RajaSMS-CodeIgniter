<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Rajasms {
    const REGEX_PHONE_GLOBAL = '/^(62[1-9]{1}[0-9]{1,2})[0-9]{6,8}$/';
    const REGEX_PHONE_GLOBAL_PLUS = '/^(\+62[1-9]{1}[0-9]{1,2})[0-9]{6,8}$/';
    const REGEX_PHONE_TELCO = '/^(0[1-9]{1}[0-9]{1,2})[0-9]{6,8}$/';
    
    private $CI;

    private $host;

    private $uri_gateway_regular;
    private $uri_gateway_masking;
    
    private $uri_report_regular;
    private $uri_report_masking;

    private $uri_credit;

    private $text;
    private $phone;

    public function __construct() {
        $this->CI =& get_instance();
        $this->CI->load->config('rajasms');

        $this->host  = ($this->CI->config->item('rajasms_host')!==FALSE) ? $this->CI->config->item('rajasms_host') : 'http://162.211.84.203/sms';
        $this->uri_gateway_regular = ($this->CI->config->item('rajasms_uri_gateway_regular')!==FALSE) ? $this->CI->config->item('rajasms_uri_gateway_regular') : '/smsreguler.php';
        $this->uri_gateway_masking = ($this->CI->config->item('rajasms_uri_gateway_masking')!==FALSE) ? $this->CI->config->item('rajasms_uri_gateway_masking') : '/smsmasking.php';
        $this->uri_report_regular = ($this->CI->config->item('rajasms_uri_report_regular')!==FALSE) ? $this->CI->config->item('rajasms_uri_report_regular') : '/smsregulerreport.php';
        $this->uri_report_masking = ($this->CI->config->item('rajasms_uri_report_masking')!==FALSE) ? $this->CI->config->item('rajasms_uri_report_masking') : '/smsmaskingreport.php';
        $this->uri_credit = ($this->CI->config->item('rajasms_uri_credit')!==FALSE) ? $this->CI->config->item('rajasms_uri_credit') : '/smssaldo.php';

        $this->phone = '';
        $this->text = '';
    }
    
    public function reset() {
        $this->phone    = '';
        $this->text     = '';
    }
    
    public function set_number($phonenumber,$validate_format=FALSE) {
        if ($validate_format===TRUE) {
            if (preg_match(self::REGEX_PHONE_GLOBAL, $phonenumber)==1) {
                $r = '0'.substr($phonenumber,2);
            } else if (preg_match(self::REGEX_PHONE_GLOBAL_PLUS, $phonenumber)==1) {
                $r = '0'.substr($phonenumber,3);
            } else if (preg_match(self::REGEX_PHONE_TELCO, $phonenumber)==1) {
                $r = $phonenumber;
            } else $r = '';
        } else $r = $phonenumber;
        $this->phone = $r;
    }
    
    public function set_text($text) {
        $t = trim(strval($text));
        $this->text = (strlen($t)>160)?substr($t,0,160):$t;
    }
    
    private function _credit_inquiry() {
        $url = $this->host.$this->uri_credit.'?username='.$this->CI->config->item('rajasms_username')."&password=".$this->CI->config->item('rajasms_password')."&key=".$this->CI->config->item('rajasms_key');
        $c = curl_init();
        curl_setopt($c,CURLOPT_URL,$url);
        curl_setopt($c,CURLOPT_HEADER, 0);
        curl_setopt($c,CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($c,CURLOPT_TIMEOUT,120);
        $r = strval(curl_exec($c));
        curl_close($c);	
        unset($c,$url);
        return $r;
    }
    
    public function get_credit() {
        $r = explode('|', $this->_credit_inquiry());
        $ret = (is_array($r) && count($r)==2) ? intval($r[0]) : FALSE;
        unset($r);
        return $ret;
    }
    
    public function get_expire_timestamp() {
        $r = explode('|', $this->_credit_inquiry());
        $ret = (is_array($r) && count($r)==2) ? strtotime($r[1]) : FALSE;
        unset($r);
        return $ret;
    }

    public function get_expire_date($format='Y-m-d H:i:s') {
        $t = $this->get_expire_timestamp();
        $r = ($t!==FALSE) ? date($format,$t) : FALSE;
    }
    
    private function _credit_validate() {
        $c = explode('|', $this->_credit_inquiry());
        if (is_array($c) && count($c)==2) {
            $saldo = intval($c[0]);
            $expired = strtotime($c[1]);
            $r = (($saldo>500) && ($expired>time())) ? TRUE : FALSE;
        } else $r = FALSE;
        unset($c);
        return $r;
    }
    
    private function _is_ready() {
        $r = ((strlen($this->text)>0) && (strlen($this->phone)>0) && ($this->_credit_validate()===TRUE)) ? TRUE : FALSE;
        return $r;
    }
    
    public function send($is_masking=FALSE,$phone=NULL,$text=NULL) {
        if ($phone!==NULL) $this->set_number($phone);
        if ($text!==NULL) $this->set_text($text);
        if ($is_masking===TRUE) {
            $uri = $this->uri_gateway_masking;
            $b_masking = TRUE;
        } else {
            $uri = $this->uri_gateway_regular;
            $b_masking = FALSE;
        }
        if ($this->_is_ready()) {
            $url = $this->host.$uri.'?username='.$this->CI->config->item('rajasms_username').'&password='.$this->CI->config->item('rajasms_password')."&key=".$this->CI->config->item('rajasms_key')."&number=".$this->phone."&message=".urlencode($this->text);
            $c = curl_init();
            curl_setopt($c,CURLOPT_URL,$url);
            curl_setopt($c,CURLOPT_HEADER, 0);
            curl_setopt($c,CURLOPT_RETURNTRANSFER, TRUE);
            curl_setopt($c,CURLOPT_TIMEOUT,120);
            $r = explode('|', strval(curl_exec($c)));
            curl_close($c);
            $ret = (is_array($r) && count($r)==2) ? (($r[0]==0) ? array('id'=>$r[1],'is_masking'=>$b_masking) : FALSE) : FALSE;
            unset($r,$c,$url);
        } else $ret = FALSE;
        unset($uri);
        return $ret;
    }
    
    public function get_report($sms_result) {
        if ((is_array($sms_result)==TRUE) && (count($sms_result)==2) && (isset($sms_result['id'])==TRUE) && (isset($sms_result['is_masking'])==TRUE)) {
            $id = intval(trim(strval($sms_result['id'])));
            $uri = ($sms_result['is_masking']==TRUE) ? $this->uri_report_masking : $this->uri_report_regular;
            $url = $this->host.$uri.'?id='.$id."&key=".$this->CI->config->item('rajasms_key');
            $c = curl_init();
            curl_setopt($c,CURLOPT_URL,$url);
            curl_setopt($c,CURLOPT_HEADER, 0);
            curl_setopt($c,CURLOPT_RETURNTRANSFER, TRUE);
            curl_setopt($c,CURLOPT_TIMEOUT,120);
            $r = explode('|', strval(curl_exec($c)));
            curl_close($c);
            $ret = (is_array($r) && count($r)==2) ? (($r[0]==0)?strtolower($r[1]):FALSE) : FALSE;
            unset($r,$c,$url,$uri,$id);
        } else $ret = FALSE;
        return $ret;
    }
}