<?php
// Internal classes are also autoloaded through composer
require __DIR__ . '/../vendor/autoload.php';

use Ruzgfpegk\GeneratorsImg\RealTimeGenerator;

// Have RTG read the settings related to the GET p parameter
$RTG = new RealTimeGenerator;

if (empty($RTG)) {
	echo '<p>' . "configuration file couldn't be loaded!" . '</p>';
} else {
	$RTG->displayImage();
}
