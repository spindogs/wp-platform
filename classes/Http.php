<?php
namespace Platform;

class Http {

    public $base_url;
    public $url;
    public $uri;
    public $method = 'GET';
    public $headers = array();
    public $params = array();
    public $authorization;
    public $content_type;
    public $connect_timeout;
    public $body;
    public $auto_decode_json = true;
    protected $http_code;
    protected $redirect_url;
    protected $debug;

    /**
     * @return mixed
     */
    public function call()
    {
        if ($this->url) {
            $url = $this->url;
        } else {
            $url = $this->base_url.$this->uri;
        }

        $ch = curl_init();
        $headers = $this->headers;

        if ($this->authorization) {
            $headers[] = 'Authorization: '.$this->authorization.'';
        }

        if ($this->content_type) {
            $headers[] = 'Content-Type: '.$this->content_type.'';
        }

        if ($this->params) {
            $params = $this->params;
            $param_string = http_build_query($params);
            $url .= '?'.$param_string;
        }

        if ($this->connect_timeout) {
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT_MS, $this->connect_timeout);
        }

        if ($this->body) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $this->body);
        }

        if ($this->method == 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
        }

        if ($this->method == 'PUT') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
        } elseif ($this->method == 'DELETE') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
        } elseif ($this->method == 'PATCH') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
        }

        if ($headers) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLINFO_HEADER_OUT, true);

        $rtn = curl_exec($ch);
        $this->http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $this->redirect_url = curl_getinfo($ch, CURLINFO_REDIRECT_URL);

        //debug
        $this->debug = curl_getinfo($ch);
        $this->debug['response'] = $rtn;

        curl_close($ch);

        if ($this->auto_decode_json) {
            $rtn = json_decode($rtn);
        }

        return $rtn;

    }

    /**
     * @return bool
     */
    public function success()
    {
        if (empty($this->http_code)) {
            return false;
        } elseif ($this->http_code == 200) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @return int
     */
    public function getHttpCode()
    {
        return $this->http_code;
    }

    /**
     * @return string
     */
    public function getRedirectUrl()
    {
        return $this->redirect_url;
    }

}
