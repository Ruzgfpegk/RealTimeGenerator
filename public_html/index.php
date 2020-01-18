<?php
declare( strict_types = 1 );

// Internal classes also use autoload through composer
require __DIR__ . '/../vendor/autoload.php';

use Ruzgfpegk\GeneratorsImg\RealTimeGenerator;

// By default, RTG receives the config name through the "p" parameter (HTTP GET)
try {
	$RTG = new RealTimeGenerator;
} catch (Exception $error) {
	echo '<p>' . $error . '</p>';
}

$RTG->displayImage();
