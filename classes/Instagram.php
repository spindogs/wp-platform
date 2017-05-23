<?php
namespace Platform;

class Instagram {

	public $user_id;
	public $client_id = INSTAGRAM_CLIENT;
	public $count = 5;
	public $cache_folder = 'sd-cache';
	public $cache_file = 'instagram.json';
	public $cache_timeout = 14400; //4 hours

	/**
	 * @return array
	 */
	public function getImages()
	{

		$this->cache();

		$output = file_get_contents($this->filepath);

		if (!$output) {
			return array();
		}

		$output = json_decode($output);
		$images = array();

		foreach ($output->data as $image) {
			$images[] = $image;
		}

		return $images;

	}

	/**
	 * @return void
	 */
	public function cache()
	{

		$this->filepath = get_template_directory().'/'.$this->cache_folder.'/'.$this->cache_file;

		$url = 'https://api.instagram.com/v1/users/'.$this->user_id.'/media/recent/';
		$url .= '?client_id='.$this->client_id;
		$url .= '&count='.$this->count;

		if (filesize($this->filepath) < 0.01) {
			//ignore cache if empty
		} elseif (filemtime($this->filepath) > time() - $this->cache_timeout) {
			return;
		}

		$json = file_get_contents($url);

		//write to cache
		if (strlen($json) < 1) {
			return;
		}

		$fp = fopen($this->filepath, 'w');
		fwrite($fp, $json);
		fclose($fp);

	}

}
