<?php

use NovemBit\wp\plugins\RuntimeDebugger\Main;

defined('ABSPATH') || exit;

require WP_PLUGIN_DIR . '/wp-runtime-debugger/vendor/autoload.php';

Main::instance();