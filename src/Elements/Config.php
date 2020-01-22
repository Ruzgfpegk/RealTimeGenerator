<?php
declare( strict_types = 1 );

namespace Ruzgfpegk\GeneratorsImg\Elements;

use Imagine\Image\Box;
use Imagine\Image\Palette\RGB;
use Imagine\Image\Palette\Color\ColorInterface;

use Ruzgfpegk\GeneratorsImg\Core\ElementInterface;

/**
 * Class Config
 *
 * @package Ruzgfpegk\GeneratorsImg\Elements
 */
class Config implements ElementInterface
{
	/**
	 * @var string The renderer to use, among "Gd", "Imagick" and "Gmagick"
	 */
	public $renderer;
	
	/**
	 * @var string Output format, among "png", "jpg" and "gif"
	 */
	public $format;
	
	/**
	 * @var integer JPEG Quality percentage
	 */
	public $quality;
	
	/**
	 * @var string Comma-separated list of elements to render, back-to-front
	 */
	public $layout;
	
	/**
	 * @var string Dimensions of the image, in the format "weight,height"
	 */
	public $dimensions;
	
	/**
	 * @var string Background color, in the format "R,G,B" (0-255 range each)
	 */
	public $bgcolor;
	
	/**
	 * @var string Other config file to import
	 */
	public $import;
	
	/**
	 * @var integer For animations, the number of seconds between frames
	 */
	public $interval;
	
	/**
	 * @var integer The total number of frames, if the output is an animation
	 */
	public $frames;
	
	
	// Processed variables
	/**
	 * @var string[] The layout, as a back-to-front array
	 */
	public $layoutArr;
	
	/**
	 * @var integer[] The width (element 0) and height (element 1)
	 */
	public $dimensionsArr;
	
	/**
	 * @var integer[] The background color as a R,G,B array (0-255 range each)
	 */
	public $bgcolorArr;
	
	
	// Objects
	/**
	 * @var Box Dimensions of the output image in object form
	 */
	public $dimensionsObj;
	
	/**
	 * @var ColorInterface Background color of the output image in object form
	 */
	public $bgcolorObj;
	
	// Meta-properties
	/**
	 * @var string The name of the configuration containing the element
	 *             (also in the configuration file filename).
	 */
	public $configName;
	
	/**
	 * @var string The identifier of the element
	 *             (section of the configuration file)
	 */
	public $elementName;
	
	/**
	 * Config constructor
	 *
	 * @param string   $configName         Name of the configuration file of the section
	 * @param string   $elementName        Name of the element
	 * @param string[] $elementParameters  Associative array of section parameters
	 */
	public function __construct($configName, $elementName, $elementParameters)
	{
		$this->configName  = $configName;
		$this->elementName = $elementName;
		$this->setDefaults();
		$this->loadSection($elementParameters);
		$this->postLoad();
	}
	
	/**
	 * Defaults of the class
	 */
	public function setDefaults() : void
	{
		$this->renderer   = 'Gd';
		$this->format     = 'jpg';
		$this->quality    = 90;
		$this->layout     = '';
		$this->dimensions = '200,100';
		$this->bgcolor    = '255,255,255';
		$this->import     = '';
		$this->interval   = 1;
		$this->frames     = 1;
	}
	
	
	/**
	 * @param string[] $section Associative array of section parameters
	 */
	public function loadSection($section) : void
	{
		$properties = [
			'renderer',
			'format',
			'quality',
			'layout',
			'dimensions',
			'bgcolor',
			'import',
			'interval',
			'frames'
		];
		
		foreach ($properties as $property) {
			if (array_key_exists($property, $section)) {
				$this->{$property} = $section[$property];
			}
		}
	}
	
	
	/**
	 * Internal treatments/checks to run after the conf is loaded
	 */
	public function postLoad() : void
	{
		$this->layoutArr     = explode(',', $this->layout);
		$this->dimensionsArr = array_map(
			'intval',
			explode(',', $this->dimensions)
		);
		$this->bgcolorArr    = array_map(
			'intval',
			explode(',', $this->bgcolor)
		);
		
		$this->dimensionsObj = new Box(...$this->dimensionsArr);
		
		$this->bgcolorObj = ( new RGB() )->color($this->bgcolorArr, 100);
	}
	
	// TODO For the cache, snippet from the previous version of RealTimeGenerator.php
	// Global settings
	/*
	if (array_key_exists('cache', $parsedConf)) {
		if (array_key_exists('timeout', $parsedConf['cache'])) {
			$this->cacheTimeout
				= $parsedConf['cache']['timeout'];
		}
		if (array_key_exists('number', $parsedConf['cache'])) {
			$this->cacheNumber
				= $parsedConf['cache']['number'];
		}
		unset($parsedConf['cache']);
	}
	*/
}
