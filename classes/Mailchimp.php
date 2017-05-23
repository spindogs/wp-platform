<?php
namespace Platform;

use Platform\Exception;

class Mailchimp extends Http {

    public $apikey;
    public $list_id;
    public $auto_decode_json = false; //legacy

    /**
     * @param string $email
     * @param array $data
     * @return mixed
     */
    public function subscribe($email, $data=array())
    {
        $email_md5 = md5($email);

        $request = clone $this;
        $request->uri = '/lists/'.$this->list_id.'/members/'.$email_md5;
        $request->method = 'GET';

        try {
            $response = $request->call();
        } catch (Exception $e) {
            //do nothing
        }

        if (!$request->success()) {
            $mode = 'create';
        } elseif ($response->status != 'subscribed') {
            $mode = 'update';
        } else {
            return false; //already subscribed
        }

        if (empty($data['status'])) {
            $data['status'] = 'subscribed';
        }

        if ($mode == 'create') {

            $data['email_address'] = $email;

            $this->uri = '/lists/'.$this->list_id.'/members';
            $this->method = 'POST';
            $this->body = $data;
            $response = $this->call();

        } elseif ($mode == 'update') {

            $this->uri = '/lists/'.$this->list_id.'/members/'.$email_md5;
            $this->method = 'PATCH';
            $this->body = $data;
            $response = $this->call();

        }

        return $response;
    }

    /**
     * @return mixed
     */
    public function call()
    {
        $this->setBaseUrl();
        $this->authorization = 'anystring '.$this->apikey;

        if ($this->body) {
            $this->content_type = 'json';
            $this->body = json_encode($this->body);
        }

        $rtn = parent::call();
        $rtn = json_decode($rtn);

        if ($this->http_code != 200) {
            throw new Exception($rtn->detail);
        }

        return $rtn;
    }

    /**
     * @return str
     */
    protected function setBaseUrl()
    {
        $split = explode('-', $this->apikey, 2);

        if (isset($split[1])) {
            $dc = $split[1];
        } else {
            $dc = 'us1';
        }

        $this->base_url = 'https://'.$dc.'.api.mailchimp.com/3.0';
    }

}
