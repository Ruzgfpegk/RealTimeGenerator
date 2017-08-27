<?php

namespace Ruzgfpegk\GeneratorsImg;

use Imagine\Image\AbstractImage;   // Here so that PhpStorm understands what's going on.
use Imagine\Image\AbstractImagine; // Same
use Imagine\Image\AbstractFont;    // Same
use Imagine\Image\Box;
use Imagine\Image\Palette\Color\ColorInterface;
use Imagine\Image\Point;
use Imagine\Image\Palette\RGB;
use GifCreator\GifCreator;

/**
 * Class RealTimeGenerator
 *
 * @package Ruzgfpegk\GeneratorsImg
 */
class RealTimeGenerator
{
	// Class defaults
	
	/**
	 * @var string The config file to open, and default subfolder for elements
	 */
	private $configName = null;
	
	/**
	 * @var string The renderer to use (among Gd, Imagick, Gmagick)
	 */
	private $outputRenderer = 'Gd';
	/**
	 * @var string The output image format
	 */
	private $outputFormat = 'jpg';
	/**
	 * @var int The delay in seconds between two gif frames
	 *          In the GIF format it's a WORD (16-bit), so 65536 hundredths of a second,
	 *          so it may work up to 655 seconds (10mn55s).
	 *          Source: http://www.fileformat.info/format/gif/egff.htm
	 */
	private $animationInterval = 1;
	/**
	 * @var int The number of frames the image will have
	 */
	private $numberOfFrames = 1;
	/**
	 * @var int The JPG output quality
	 */
	private $outputQuality = 80;
	/**
	 * @var Box The dimensions of the final image
	 */
	private $imageSize = null;
	/**
	 * @var ColorInterface The background color
	 */
	private $bgColor = null;
	/**
	 * @var int The number of seconds a cache file is valid (0 = no cache, null = use file limit)
	 */
	private $cacheTimeout = 0;
	/**
	 * @var int The number of most recent cache files to keep
	 */
	private $cacheNumber = 50;
	/**
	 * @var bool Is the config file correctly loaded?
	 */
	private $loadState = false;
	/**
	 * @var array Names of the elements to render, in order
	 */
	private $layout = array();
	/**
	 * @var RealTimeGeneratorElement[] Elements to render
	 */
	private $elements = array();
	
	// Work variables
	/**
	 * @var AbstractImagine The Imagine object to manage transformations
	 */
	private $imagine = null;
	/**
	 * @var AbstractImage The Canvas on which the final image is drawn
	 */
	private $canvas = null;
	/**
	 * @var array[AbstractImage] The independent frames as we can't use layers with Gd
	 */
	private $frames = null;
	/**
	 * @var string binary string for the final image if the rendering is done by GifCreator
	 */
	private $gcImage = null;
	/**
	 * @var array List of options for Imagine output
	 */
	private $renderOptions = null;
	/**
	 * @var boolean Has an exception been raised? If yes, no rendering will be done, to allow error output.
	 */
	public $exceptionFound = false;
	
	/**
	 * RealTimeGenerator constructor.
	 *
	 * @param $configName     string The configuration to use (null to use query parameter)
	 * @param $queryParameter string The parameter of the URL query string (GET)
	 *
	 * @return bool The status of the configuration loading
	 */
	public function __construct($configName = null, $queryParameter = 'p')
	{
		// Closure to strip all non-alphanumerical characters
		$alnumFilter = function ($input) {
			return mb_ereg_replace('[^A-Za-z0-9]', '', $input);
		};
		
		if (is_null($configName)) {
			// Get the conf from the URL parameter, or use 'default'
			$configName = filter_input(INPUT_GET, $queryParameter);
			
			if (empty($configName)) {
				$configName = 'default';
			}
		}
		
		$configName       = filter_var(
			$configName,
			FILTER_CALLBACK,
			array('options' => $alnumFilter)
		);
		$this->configName = $configName;
		$this->elements   = $this->loadConfig($configName);
		
		if (!empty($this->elements)) {
			$this->loadState = true;
		}
		
		return $this->loadState;
	}
	
	/**
	 * Loads the ini file for the provided configuration name
	 *
	 * @param $configName string The requested config
	 *
	 * @return array[RTG_Element] The elements loaded from the configuration
	 */
	private function loadConfig($configName)
	{
		$configFilePath = __DIR__ . '/../configurations/' . $configName
			. '.conf';
		
		$elements = array();
		
		// Default values
		$dimensions = array(200, 100);
		$bgcolor    = array(255, 255, 255);
		
		if (file_exists($configFilePath)) {
			$parsedConf = parse_ini_file($configFilePath, true);
			
			if (!empty($parsedConf)) {
				// Global settings
				if (array_key_exists('output', $parsedConf)) {
					if (array_key_exists('renderer', $parsedConf['output'])) {
						$this->outputRenderer
							= $parsedConf['output']['renderer'];
					}
					if (array_key_exists('format', $parsedConf['output'])) {
						$this->outputFormat
							= $parsedConf['output']['format'];
					}
					if (array_key_exists('interval', $parsedConf['output'])) {
						$this->animationInterval
							= $parsedConf['output']['interval'];
					}
					if (array_key_exists('frames', $parsedConf['output'])) {
						$this->numberOfFrames
							= $parsedConf['output']['frames'];
					}
					if (array_key_exists('quality', $parsedConf['output'])) {
						$this->outputQuality
							= $parsedConf['output']['quality'];
					}
					if (array_key_exists('layout', $parsedConf['output'])) {
						$this->layout
							= explode(',', $parsedConf['output']['layout']);
					}
					if (array_key_exists('dimensions', $parsedConf['output'])) {
						$dimensions = array_map(
							'intval',
							explode(',', $parsedConf['output']['dimensions'])
						);
					}
					if (array_key_exists('bgcolor', $parsedConf['output'])) {
						$bgcolor = array_map(
							'intval',
							explode(',', $parsedConf['output']['bgcolor'])
						);
					}
					
					unset($parsedConf['output']);
				}
				
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
				
				// Import settings from external files
				// TODO: Support multiple imported configurations
				if (array_key_exists('import', $parsedConf)
					&& array_key_exists('configuration', $parsedConf['import'])
				) {
					$importedElements
						= $this->loadConfig($parsedConf['import']['configuration']);
					
					if (!empty($importedElements)) {
						$elements = $importedElements;
					}
					
					unset($parsedConf['import']);
				}
				
				
				// Create an object for each separate element
				foreach ($parsedConf as $section => $parameters) {
					//TODO: Check sections, parameters and values
					$element = null;
					
					try {
						$element = new RealTimeGeneratorElement(
							$configName,
							$section,
							$parameters
						);
					} catch (\Exception $e) {
						$this->exceptionFound = true;
						echo $e->getMessage();
					}
					
					$elements[$section] = $element;
				}
			}
		}
		
		// The following is executed whether the config file exists or not
		
		// TOFIX: Right now a new object is created for each import, then a last
		//        one for the global configuration at the end. That's ugly.
		
		$palette = new RGB();
		
		$this->imageSize = new Box(...$dimensions);
		$this->bgColor   = $palette->color($bgcolor, 100);
		
		return $elements;
	}
	
	/**
	 * Save the rendered file to cache
	 *
	 * @todo everything
	 */
	public function saveCache()
	{
		return 0;
	}
	
	/**
	 * Purge the cache from old files
	 *
	 * @todo everything
	 */
	public function purgeCache()
	{
		return 0;
	}
	
	
	/**
	 * Add an image element on top of the image
	 *
	 * @param $elementName string  name of the image element to add on top
	 * @param $frameNumber integer frame on which to add the date
	 */
	private function addImage($elementName, $frameNumber = 0)
	{
		$sourceImgPath = '../resources/images/'
			. $this->elements[$elementName]->configName
			. '/' . $this->elements[$elementName]->properties['image'];
		
		if (file_exists($sourceImgPath)) {
			$sourceImgObj = $this->imagine->open($sourceImgPath);
			
			$sourcePosObj
				= $this->elements[$elementName]->properties['position'];
			
			// Crop if needed (TODO: Check if it really works)
			$sourceSizeObj = $sourceImgObj->getSize();
			if ($sourceSizeObj->getWidth() > $this->imageSize->getWidth()
				|| $sourceSizeObj->getHeight()
				> $this->imageSize->getHeight()) {
				$sourceZeroPosObj = new Point(0, 0);
				$sourceImgObj     = $sourceImgObj->crop(
					$sourceZeroPosObj,
					$this->imageSize
				);
			}
			
			// Draw the image on the requested frame
			$this->frames[$frameNumber] = $this->frames[$frameNumber]->paste(
				$sourceImgObj,
				$sourcePosObj
			);
		}
	}
	
	
	/**
	 * Add a text element on top of the image
	 *
	 * @param $elementName string  name of the text element to add on top
	 * @param $frameNumber integer frame on which to add the text
	 * @param $overrideStr string  to use the specified string instead of the element one
	 */
	private function addText(
		$elementName,
		$frameNumber = 0,
		$overrideStr = null
	) {
		$fontElement = $this->elements[$elementName]->properties['font'];
		
		$sourceFontPath = '../resources/fonts/'
			. $this->elements[$fontElement]->configName
			. '/' . $this->elements[$fontElement]->properties['file'];
		
		if (file_exists($sourceFontPath)) {
			$paletteObj = new RGB();
			
			$sourceColorObj = $paletteObj->color(
				$this->elements[$elementName]->properties['color'],
				(int)$this->elements[$elementName]->properties['opacity']
			);
			
			$sourceFontObj = $this->imagine->font(
				$sourceFontPath,
				(int)$this->elements[$elementName]->properties['size'],
				$sourceColorObj
			);
			
			$sourcePosObj
				= $this->elements[$elementName]->properties['position'];
			
			if (empty($overrideStr)) {
				$text = $this->elements[$elementName]->properties['string'];
			} else {
				$text = $overrideStr;
			}
			
			// Replace text parameters by filtered values from GET
			$varPattern        = '/%([a-z0-9]+)%/i';
			$returnFilteredGet = function ($input) {
				return filter_input(INPUT_GET, $input[1]);
			};
			
			while (preg_match($varPattern, $text)) {
				$text = preg_replace_callback(
					$varPattern,
					$returnFilteredGet,
					$text
				);
			}
			
			// Draw the final text on the requested frame
			$this->frames[$frameNumber]->draw()
				->text($text, $sourceFontObj, $sourcePosObj);
		}
	}
	
	
	/**
	 * Calls addText to each frame with values from a timer.
	 *
	 * Non-existing frames will be generated.
	 *
	 * @param $elementName string name of the timer element to add on top
	 */
	public function addTimer($elementName)
	{
		$params[] = $this->elements[$elementName]->properties['string'];
		
		if (array_key_exists(
			'timezone',
			$this->elements[$elementName]->properties
		)
		) {
			$params[] = $this->elements[$elementName]->properties['timezone'];
		} else {
			$params[] = null;
		}
		
		$params[] = $this->numberOfFrames;
		
		// To support timers with only 1 frame
		$timesTmp = $this->strToDate(...$params);
		if (is_array($timesTmp)) {
			$times = $timesTmp;
		} else {
			$times[] = $timesTmp;
		}
		
		// The copy() method returns a GIF image without alpha channel, so we use a working copy in PNG.
		$referenceFrameBin = $this->frames[0]->get('png');
		
		for ($i = 0; $i < $this->numberOfFrames; $i++) {
			if (!isset($this->frames[$i])) {
				$this->frames[$i] = $this->imagine->load($referenceFrameBin);
			}
			
			$this->addText($elementName, $i, $times[$i]);
		}
	}
	
	/**
	 * Add a date on top of the image
	 *
	 * We just change the contents of the string parameter before feeding
	 * the element to addText.
	 *
	 * @param $elementName string  name of the date element to add on top
	 * @param $frameNumber integer frame on which to add the date
	 */
	private function addDate($elementName, $frameNumber = 0)
	{
		$params[] = $this->elements[$elementName]->properties['string'];
		
		if (array_key_exists(
			'timezone',
			$this->elements[$elementName]->properties
		)) {
			$params[] = $this->elements[$elementName]->properties['timezone'];
		}
		
		$dateString = $this->strToDate(...$params);
		
		$this->elements[$elementName]->properties['string'] = $dateString;
		
		$this->addText($elementName, $frameNumber);
	}
	
	/**
	 * Function to transform a date format string into a final string.
	 *
	 * @param string $string   a DateTime-compatible string
	 * @param null   $timezone a DateTimeZone-compatible string
	 * @param int    $frames   number of elements to return
	 *
	 * @return string|string[] one date, or an array of dates if $frames > 1
	 */
	private function strToDate($string, $timezone = null, $frames = 1)
	{
		$framesStr = null;
		$params    = null;
		
		if (!is_null($timezone)) {
			$tzObject = new \DateTimeZone($timezone);
			$params   = array('now', $tzObject);
		} else {
			$params = array('now');
		}
		
		$date = new \DateTime(...$params);
		
		if ($frames === 1) {
			$framesStr = $date->format($string);
		} else {
			for ($i = 1; $i <= $frames; $i++) {
				$framesStr[] = $date->format($string);
				$date->modify($this->animationInterval . ' second');
			}
		}
		
		return $framesStr;
	}
	
	/**
	 * Initialize the "imagine" property-object
	 */
	public function initImagine()
	{
		switch ($this->outputRenderer) {
			case 'Gd':
				$this->imagine = new \Imagine\Gd\Imagine();
				break;
			case 'Imagick':
				$this->imagine = new \Imagine\Imagick\Imagine();
				break;
			case 'Gmagick':
				$this->imagine = new \Imagine\Gmagick\Imagine();
				break;
			default:
				exit('Invalid renderer ' . $this->outputRenderer);
		}
		
		$this->frames[0] = $this->imagine->create(
			$this->imageSize,
			$this->bgColor
		);
	}
	
	
	/**
	 * Where images are rendered.
	 *
	 * This method uses the Imagine library to be able to use various
	 * graphics libraries at will.
	 * See https://imagine.readthedocs.io/en/latest/ for details.
	 */
	public function renderImage()
	{
		if (is_null($this->imagine)) {
			$this->initImagine();
		}
		
		// Processing elements back to front
		foreach ($this->layout as $elementName) {
			// Skip elements of the layout that aren't declared
			if (!empty($this->elements[$elementName])) {
				switch ($this->elements[$elementName]->properties['type']) {
					case 'image':
						$this->addImage($elementName);
						break;
					case 'text':
						$this->addText($elementName);
						break;
					case 'date':
						$this->addDate($elementName);
						break;
					case 'timer':
						$this->addTimer($elementName);
						break;
					case 'countdown':
						break;
				}
			}
		}
		
		
		// PHP-Gd doesn't support layers and animated Gifs so we use GifCreator
		if ($this->outputFormat === 'gif'
			&& $this->outputRenderer === 'Gd'
			&& count($this->frames) > 1) {
			$gcObject       = new GifCreator();
			$gcFrames       = array();
			$framesDuration = array();
			
			foreach ($this->frames as $layer) {
				$gcFrames[]       = imagecreatefromstring($layer->get('gif'));
				$framesDuration[] = $this->animationInterval * 100;
			}
			
			$gcObject->create($gcFrames, $framesDuration, 1);
			$this->gcImage = $gcObject->getGif();
		} else {
			// Else, continue using Imagine
			
			// Set options according to the image
			$this->renderOptions = array('flatten' => true);
			
			if ($this->outputFormat === 'gif') {
				if (count($this->frames) > 1) {
					$this->renderOptions['flatten']        = false;
					$this->renderOptions['animated']       = true;
					$this->renderOptions['animated.delay']
					                                       = $this->animationInterval
						* 1000;
					$this->renderOptions['animated.loops'] = 0;
				}
			} elseif ($this->outputFormat === 'png') {
				// According to Imagine's doc, only PNGs and animated GIFs shouldn't be flattened
				$this->renderOptions['flatten'] = false;
			} elseif ($this->outputFormat === 'jpg') {
				$this->renderOptions['jpeg_quality'] = $this->outputQuality;
			}
			
			// Move all layers to the canvas object
			if (count($this->frames) === 1) {
				$this->canvas = $this->frames[0];
			} else {
				$this->canvas = $this->imagine->create($this->imageSize);
				
				// TODO: Replace also frame 0
				foreach ($this->frames as $layer) {
					$this->canvas->layers()->add($layer);
				}
				
				$this->canvas->layers()->coalesce();
			}
		}
	}
	
	/**
	 * Outputs the rendered image to the browser (headers included)
	 */
	public function displayImage()
	{
		$save = false;
		
		//TODO: Implement caching
		if (is_null($this->imagine)) {
			$this->renderImage();
		}
		
		if (!$this->exceptionFound) {
			if (is_null($this->gcImage)) {
				// The content type header is automatically sent by Imagine
				$this->canvas->show($this->outputFormat, $this->renderOptions);
			} else {
				if ($save) {
					file_put_contents('final.gif', $this->gcImage);
				} else {
					header('Content-type: image/gif');
					header('Content-Disposition: filename="output.gif"');
					echo $this->gcImage;
				}
			}
		}
	}
}
