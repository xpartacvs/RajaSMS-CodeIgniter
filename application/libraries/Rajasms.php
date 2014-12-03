<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Rajasms {
    const REGEX_PHONE_GLOBAL = '/^(62[1-9]{1}[0-9]{1,2})[0-9]{6,8}$/';
    const REGEX_PHONE_GLOBAL_PLUS = '/^(\+62[1-9]{1}[0-9]{1,2})[0-9]{6,8}$/';
    const REGEX_PHONE_TELCO = '/^(0[1-9]{1}[0-9]{1,2})[0-9]{6,8}$/';

    private $host;
    private $timeout;

    private $uri_gateway_regular;
    private $uri_gateway_masking;
    
    private $uri_report_regular;
    private $uri_report_masking;

    private $uri_credit;

    private $account_username;
    private $account_password;
    private $account_key;

    private $text;
    private $phone;

    public function __construct() {
        $this->_check_compatibility();

        $CI =& get_instance();
        $CI->load->config('rajasms');

        $this->host  = ($CI->config->item('rajasms_host')!==FALSE) ? $CI->config->item('rajasms_host') : 'http://162.211.84.203/sms';
        $this->timeout = ($CI->config->item('rajasms_timeout')!==FALSE) ? $CI->config->item('rajasms_timeout') : 120;
        
        $this->uri_gateway_regular = ($CI->config->item('rajasms_uri_gateway_regular')!==FALSE) ? $CI->config->item('rajasms_uri_gateway_regular') : '/smsreguler.php';
        $this->uri_gateway_masking = ($CI->config->item('rajasms_uri_gateway_masking')!==FALSE) ? $CI->config->item('rajasms_uri_gateway_masking') : '/smsmasking.php';
        
        $this->uri_report_regular = ($CI->config->item('rajasms_uri_report_regular')!==FALSE) ? $CI->config->item('rajasms_uri_report_regular') : '/smsregulerreport.php';
        $this->uri_report_masking = ($CI->config->item('rajasms_uri_report_masking')!==FALSE) ? $CI->config->item('rajasms_uri_report_masking') : '/smsmaskingreport.php';
        
        $this->uri_credit = ($CI->config->item('rajasms_uri_credit')!==FALSE) ? $CI->config->item('rajasms_uri_credit') : '/smssaldo.php';

        $this->account_username = ($CI->config->item('rajasms_username')!==FALSE) ? $CI->config->item('rajasms_username') : '';
        $this->account_password = ($CI->config->item('rajasms_password')!==FALSE) ? $CI->config->item('rajasms_password') : '';
        $this->account_key = ($CI->config->item('rajasms_key')!==FALSE) ? $CI->config->item('rajasms_key') : '';

        $this->reset();
    }

    private function _check_compatibility() {
        if (!extension_loaded('curl')) throw new Exception('There is missing dependant extensions - please ensure both cURL modules are installed');
    }

    private function _credit_inquiry() {
        $data = array(
            'username' => $this->account_username, 
            'password' => $this->account_password,
            'key' => $this->account_key
        );
        $url = $this->host.$this->uri_credit.'?'.http_build_query($data);
        $c = curl_init();
        curl_setopt($c,CURLOPT_URL,$url);
        curl_setopt($c,CURLOPT_HEADER, 0);
        curl_setopt($c,CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($c,CURLOPT_TIMEOUT,$this->timeout);
        $r = strval(curl_exec($c));
        curl_close($c); 
        unset($c,$url,$data);
        return $r;
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
    
    public function get_credit() {
        $r = explode('|', $this->_credit_inquiry());
        $ret = (is_array($r) && count($r)==2) ? intval($r[0]) : FALSE;
        unset($r);
        return $ret;
    }
    
    public function send($is_masking=FALSE) {
        if ($is_masking===TRUE) {
            $uri = $this->uri_gateway_masking;
            $b_masking = TRUE;
        } else {
            $uri = $this->uri_gateway_regular;
            $b_masking = FALSE;
        }
        if ($this->_is_ready()) {
            $data = array(
                'username' => $this->account_username, 
                'password' => $this->account_password,
                'key' => $this->account_key,
                'number' => $this->phone,
                'message' => urlencode($this->text)
            );
            $url = $this->host.$uri.'?'.http_build_query($data);
            $c = curl_init();
            curl_setopt($c,CURLOPT_URL,$url);
            curl_setopt($c,CURLOPT_HEADER, 0);
            curl_setopt($c,CURLOPT_RETURNTRANSFER, TRUE);
            curl_setopt($c,CURLOPT_TIMEOUT,$this->timeout);
            $r = explode('|', strval(curl_exec($c)));
            curl_close($c);
            $ret = (is_array($r) && count($r)==2) ? (($r[0]==0) ? array('id'=>$r[1],'is_masking'=>$b_masking) : FALSE) : FALSE;
            unset($r,$c,$url,$data);
        } else $ret = FALSE;
        unset($uri);
        return $ret;
    }
    
    public function get_report($sms_result) {
        if ((is_array($sms_result)==TRUE) && (count($sms_result)==2) && (isset($sms_result['id'])==TRUE) && (isset($sms_result['is_masking'])==TRUE)) {
            $id = intval(trim(strval($sms_result['id'])));
            $uri = ($sms_result['is_masking']==TRUE) ? $this->uri_report_masking : $this->uri_report_regular;
            $data = array('id'=>$id,'key'=>$this->account_key);
            $url = $this->host.$uri.'?'.http_build_query($data);
            $c = curl_init();
            curl_setopt($c,CURLOPT_URL,$url);
            curl_setopt($c,CURLOPT_HEADER, 0);
            curl_setopt($c,CURLOPT_RETURNTRANSFER, TRUE);
            curl_setopt($c,CURLOPT_TIMEOUT,$this->timeout);
            $r = explode('|', strval(curl_exec($c)));
            curl_close($c);
            $ret = (is_array($r) && count($r)==2) ? (($r[0]==0)?strtolower($r[1]):FALSE) : FALSE;
            unset($r,$c,$url,$data,$uri,$id);
        } else $ret = FALSE;
        return $ret;
    }
}