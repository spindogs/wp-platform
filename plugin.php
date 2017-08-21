<?php
/**
 * Plugin Name: WP-Platform
 * Version: 1.7.27
 * Description: Platform to allow developers to build bespoke functionality in an MVC and OOP fashion
 */

//set plugin path
define('WP_PLATFORM', __FILE__);

//load in platform
require_once('classes/Setup.php');

//setup platform
Platform\Setup::setupWordpress();
