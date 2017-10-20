<?php
namespace Platform;

use \TwitterOAuth;

require_once(Setup::platformPath('vendor/twitteroauth/twitteroauth.php'));

class Twitter {

	protected $request_url;

	public $cache_folder = WP_CONTENT_DIR.'/cache';
	public $cache_file = 'twitter.json';
	public $cache_timeout = 1800;
	public $username = 'spindogs';
	public $url = 'http://twitter.com/';
	public $consumerkey;
	public $consumersecret;
	public $oauth_token;
	public $oauth_secret;
	public $count = 10;
	public $include_rts = true;
	public $include_entities = true;

	/**
	 * @return void
	 */
	public function cache()
	{

		//defaults
		if (!$this->consumerkey && defined('TWITTER_CONSUMERKEY')) {
			$this->consumerkey = TWITTER_CONSUMERKEY;
		}
		if (!$this->consumersecret && defined('TWITTER_CONSUMERSECRET')) {
			$this->consumersecret = TWITTER_CONSUMERSECRET;
		}
		if (!$this->oauth_token && defined('TWITTER_ACCESSTOKEN')) {
			$this->oauth_token = TWITTER_ACCESSTOKEN;
		}
		if (!$this->oauth_secret && defined('TWITTER_ACCESSSECRET')) {
			$this->oauth_secret = TWITTER_ACCESSSECRET;
		}

		//check auth data exists
		if (!$this->consumerkey || !$this->consumersecret || !$this->oauth_token || !$this->oauth_secret) {
			echo 'There are no Twitter oauth secrects configured';
			return;
		}

		//make request url
		$this->request_url 	= 'https://api.twitter.com/1.1/statuses/user_timeline.json?x=x';
		$this->request_url 	.= '&screen_name='.$this->username;
		$this->request_url 	.= '&count='.$this->count;
		$this->request_url 	.= '&include_rts='.$this->include_rts;
		$this->request_url 	.= '&include_entities='.$this->include_entities;

		//check cache folder exists
		if (!file_exists($this->cache_folder)) {
			echo 'Please create folder: '.$this->cache_folder;
			return;
		}

		// //check cache folder has correct perms
		// if (substr(sprintf('%o', fileperms($this->cache_folder)), -4) != '0777') {
		// 	echo 'Please set permission to 0777 on folder: '.$this->cache_folder;
		// 	return;
		// }

		//check cache file exists
		if (!file_exists($this->cache_folder.'/'.$this->cache_file)) {
			$fp = fopen($this->cache_folder.'/'.$this->cache_file, 'w');
			fclose($fp);
			chmod($this->cache_folder.'/'.$this->cache_file, 0777);
			$this->cache_timeout = 0;
		}

		//check if cache is up to date
		if (filemtime($this->cache_folder.'/'.$this->cache_file) > time() - $this->cache_timeout) {
			//cache is already up to date;
			return;
		}

		//get output
		$TwitterOAuth = new TwitterOAuth($this->consumerkey, $this->consumersecret, $this->oauth_token, $this->oauth_secret);
		$tweets = $TwitterOAuth->get($this->request_url);
		//print_r($tweets);

		//check for errors
		if (!empty($tweets->errors)) {
			return;
		}

		//encode output
        $output = json_encode($tweets);

		//write to cache
		if (strlen($output) > 1) {

			$fp = fopen($this->cache_folder.'/'.$this->cache_file, 'w');
			fwrite($fp, $output);
			fclose($fp);

		}  else {
			//echo 'no output im afraid';
		}

	}

	/**
	 * @return array
	 */
	public function getTweets()
	{

		$this->cache(); //run cache

		if (filesize($this->cache_folder.'/'.$this->cache_file) > 0) {

			$json = file_get_contents($this->cache_folder.'/'.$this->cache_file);
			$tweets = json_decode($json);

			foreach ($tweets as &$r) {
				$r->url = $this->url.$this->username;
				$r->content = $this->twitterify($r->text);
				$r->days_ago = human_time_diff(strtotime($r->created_at), time());
			}

			return $tweets;

		} else {
			return array();
		}

	}

	/**
	 * @param string $ret
	 * @return string
	 */
	public function twitterify($ret)
	{

		$ret = preg_replace("#(^|[\n ])([\w]+?://[\w]+[^ \"\n\r\t< ]*)#", "\\1<a href=\"\\2\" target=\"_blank\">\\2</a>", $ret);
		$ret = preg_replace("#(^|[\n ])((www|ftp)\.[^ \"\t\n\r< ]*)#", "\\1<a href=\"http://\\2\" target=\"_blank\">\\2</a>", $ret);
		$ret = preg_replace("/@(\w+)/", "<a href=\"http://www.twitter.com/\\1\" target=\"_blank\">@\\1</a>", $ret);
		$ret = preg_replace("/#(\w+)/", "<a href=\"https://twitter.com/search?q=\\1\" target=\"_blank\">#\\1</a>", $ret);

		return $ret;

	}

}
