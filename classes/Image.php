<?php
namespace Platform;

class Image {

    protected static $sizes = array();
    protected static $use_cache = true;

    public $source;
    public $destination;
    public $width;
    public $height;
    public $is_crop = false;
    public $is_zoom = true;
    protected $calculations;

    /**
     * @param int $w
     * @param int $h
     * @return string
     */
    public function crop($w, $h)
    {
        $this->is_crop = true;
        $this->is_zoom = true;
        return $this->resize($w, $h);
    }

    /**
     * @param int $w
     * @param int $h
     * @return string
     */
    public function max($w, $h)
    {
        if ($w <= 0) {
            $w = 9999;
        }

        if ($h <= 0) {
            $h = 9999;
        }

        $this->is_crop = false;
        $this->is_zoom = false;

        return $this->resize($w, $h);
     }

    /**
     * @param string $size
     * @return string
     */
    public function size($size)
    {
        if (empty(self::$sizes[$size])) {
            return false;
        }

        $w = self::$sizes[$size][0];
        $h = self::$sizes[$size][1];

        if (isset(self::$sizes[$size][2])) {
            $this->is_crop = self::$sizes[$size][2];
        }

        if (isset(self::$sizes[$size][3])) {
            $this->is_zoom = self::$sizes[$size][3];
        }

        return $this->resize($w, $h);
    }

    /**
     * @param int $w
     * @param int $h
     * @return string
     */
    public function resize($w, $h)
    {
        if (!is_file($this->source)) {
            return false;
        }

        //set target size
        $this->width = $w;
        $this->height = $h;

        //calculate new size
        $size = $this->calculate();
        $this->width = $size['crop_w'];
        $this->height = $size['crop_h'];

        //use cache
        $cache_folder = Setup::getCacheUri('images');
        $cache_path = Setup::getCachePath('images');
        $ext = Filter::extension($this->source);
        $filename = round($this->width).'x'.round($this->height).'.'.$ext;
        $source_hash = md5($this->source);
        $destination_dir = $cache_path.'/'.$source_hash;
        $destination_uri = $cache_folder.'/'.$source_hash.'/'.$filename;
        $destination = $destination_dir.'/'.$filename;

        if (self::$use_cache && file_exists($destination)) {
            return $destination_uri;
        }

        if (!file_exists($cache_path)) {
            mkdir($cache_path, 0777);
        }

        if (!file_exists($destination_dir)) {
            mkdir($destination_dir, 0777);
        }

        $this->destination = $destination;
        $this->create();

        $root_path = Setup::getRootPath();
        $destination_uri = str_replace($root_path, '', $destination);
        return $destination_uri;
    }

    /**
     * @return array
     */
    public function calculate()
    {
        if ($this->calculations) {
            return $this->calculations;
        }

        $image_info = getimagesize($this->source, $image_info);
        $curr_w = $image_info[0];
        $curr_h = $image_info[1];

        $target_w = $this->width;
        $target_h = $this->height;
        $scale_w = $target_w / $curr_w;
        $scale_h =  $target_h / $curr_h;

        if ($this->is_crop) {
            $scale = max($scale_w, $scale_h);
        } else {
            $scale = min($scale_w, $scale_h);
        }

        if (!$this->is_zoom && $scale > 1) {
            $scale = 1;
        }

        $resize_w = $curr_w * $scale;
        $resize_h = $curr_h * $scale;

        if ($this->is_crop) {
            $crop_w = min($resize_w, $target_w);
            $crop_h = min($resize_h, $target_h);
        } else {
            $crop_w = $resize_w;
            $crop_h = $resize_h;
        }

        $offset_x = ($resize_w - $crop_w) * -0.5;
        $offset_y = ($resize_h - $crop_h) * -0.5;

        $rtn = array(
            'curr_w' => $curr_w,
            'curr_h' => $curr_h,
            'target_w' => $target_w,
            'target_h' => $target_h,
            'scale_w' => $scale_w,
            'scale_h' => $scale_h,
            'is_zoom' => $this->is_zoom,
            'scale' => $scale,
            'resize_w' => $resize_w,
            'resize_h' => $resize_h,
            'is_crop' => $this->is_crop,
            'offset_x' => $offset_x,
            'offset_y' => $offset_y,
            'crop_w' => $crop_w,
            'crop_h' => $crop_h
        );

        $this->calculations = $rtn; //cache
        return $rtn;
        //print_r($rtn);
    }

    /**
     * @return bool
     */
    public function create()
    {
        if (!file_exists($this->source)) {
            return false;
        }

        ini_set('memory_limit', '512M');

        $this->calculate();

        $size = $this->calculations;
        $image_info = getimagesize($this->source, $image_info);
        $image_type = $image_info[2];
        $ext = image_type_to_extension($image_type, false);

        if ($ext == 'jpeg') {
            $image = imagecreatefromjpeg($this->source);
        } elseif ($ext == 'png') {
            $image = imagecreatefrompng($this->source);
        } elseif ($ext == 'gif') {
            $image = imagecreatefromgif($this->source);
        } else {
            throw new Exception('Image type not valid');
        }

        $target_w = $size['crop_w'];
        $target_h = $size['crop_h'];
        $to_x = $size['offset_x'];
        $to_y = $size['offset_y'];
        $from_w = $size['curr_w'];
        $from_h = $size['curr_h'];
        $from_x = 0;
        $from_y = 0;
        $to_w = $size['resize_w'];
        $to_h = $size['resize_h'];

        $src_image = $image;
        $image = imagecreatetruecolor($target_w, $target_h);
        imagesavealpha($image, true);
        $transparent = imagecolorallocatealpha($image, 0, 0, 0, 127);
        imagefill($image, 0, 0, $transparent);
        imagecopyresampled($image, $src_image, $to_x, $to_y, $from_x, $from_y, $to_w, $to_h, $from_w, $from_h);

        $destination = $this->destination;

        if ($ext == 'jpeg') {
            $rtn = imagejpeg($image, $destination, 100);
        } elseif ($ext == 'png') {
            $rtn = imagepng($image, $destination, 0);
        } elseif ($ext == 'gif') {
            $rtn = imagegif($image);
        } else {
            throw new Exception('Something went wrong during Image creation.');
        }

        imagedestroy($image);

        return $rtn;
    }

    /**
     * @param string $filename
     * @return self
     */
    public static function get($filename)
    {
        $paths_to_try = array(
            'g_images/large',
            'uploads'
        );

        foreach ($paths_to_try as $path) {

            $filepath = $path.'/'.$filename;

            if (file_exists($filepath)) {
                return self::filename($filepath);
            }

        }
    }

    /**
     * @param string $filename
     * @return self
     */
    public static function url($url)
    {
        $uri = parse_url($url, PHP_URL_PATH);
        return self::filename($uri);
    }

    /**
     * @param string $filename
     * @return self
     */
    public static function filename($filename)
    {
        $filename = Setup::getRootPath($filename);

        $image = new self();
        $image->source = $filename;
        return $image;
    }

    /**
     * @param string $name
     * @param int $width
     * @param int $height
     * @param bool $is_crop
     * @param bool $is_zoom
     * @return void
     */
    public static function addSize($name, $width, $height, $is_crop=false, $is_zoom=true)
    {
        self::$sizes[$name] = array(
            $width,
            $name,
            $is_crop,
            $is_zoom
        );
    }

    /**
     * @return void
     */
    public static function truncate()
    {
        $cache_folder = Setup::getCacheUri('images');
        $cache_path = Setup::getCachePath('images');
        $cmd = 'rm -rf '.escapeshellarg($cache_path);

        if (file_exists($cache_path)) {
            exec($cmd);
            echo 'Image cache deleted'."\n";
        }
    }

}
