<?php
declare( strict_types = 1 );

namespace Ruzgfpegk\GeneratorsImg;

use Exception;
use RuntimeException;

use Imagine\Gd\Imagine as ImagineGd;
use Imagine\Imagick\Imagine as ImagineImagick;
use Imagine\Gmagick\Imagine as ImagineGmagick;

use Imagine\Image\AbstractImage;   // Here so that PhpStorm understands what's going on.
use Imagine\Image\AbstractImagine; // Same
use GifCreator\GifCreator;

use Ruzgfpegk\GeneratorsImg\Elements\ElementFactory;

/**
 * Class RealTimeGenerator
 *
 * @package Ruzgfpegk\GeneratorsImg
 */
class RealTimeGenerator
{
	// Class defaults
	
	/**
	 * @var int The delay in seconds between two gif frames
	 *          In the GIF format it's a WORD (16-bit), so 65536 hundredths of a second,
	 *          so it may work up to 655 seconds (10mn55s).
	 *          Source: http://www.fileformat.info/format/gif/egff.htm
	 * @todo implement
	 */
	private $defaultInterval = 1;
	
	/**
	 * @var int The number of seconds a cache file is valid (0 = no cache, null = use file limit)
	 */
	//private $cacheTimeout = 0;
	
	/**
	 * @var int The number of most recent cache files to keep
	 */
	//private $cacheNumber = 50;

	/**
	 * @var array Elements to render
	 */
	private $elements;
	
	// Work variables
	/**
	 * @var AbstractImagine The Imagine object to manage transformations
	 */
	private $imagine;
	/**
	 * @var AbstractImage The Canvas on which the final image is drawn
	 */
	private $canvas;
	/**
	 * @var array[AbstractImage] The independent frames as we can't use layers with Gd
	 */
	private $frames;
	/**
	 * @var string binary string for the final image if the rendering is done by GifCreator
	 */
	private $gcImage;
	/**
	 * @var array List of options for Imagine output
	 */
	private $renderOptions;
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
	 * @throws Exception Error message if the config file doesn't exist
	 */
	public function __construct($configName = null, $queryParameter = 'p')
	{
		// Closure to strip all non-alphanumerical characters
		$alphanumFilter = static function ($input) {
			return mb_ereg_replace('[^A-Za-z0-9]', '', $input);
		};
		
		if ($configName === null) {
			// Get the conf from the URL parameter, or use 'default'
			$configName = filter_input(INPUT_GET, $queryParameter);
			
			if (empty($configName)) {
				$configName = 'default';
			}
		}
		
		$configName       = filter_var(
			$configName,
			FILTER_CALLBACK,
			array('options' => $alphanumFilter)
		);
		$this->elements   = $this->loadConfig($configName);
		
		if (empty($this->elements)) {
			throw new RuntimeException('Unable to load config "' . $configName . '"!');
		}
	}
	
	/**
	 * Loads the ini file for the provided configuration name
	 *
	 * @param  string  $configName  The requested config
	 * @param  int     $depth       The level of inclusion of the file (main 1, include 2, ...)
	 *
	 * @return array[RTG_Element] The elements loaded from the configuration
	 *
	 * @throws Exception
	 * @todo set a max depth level
	 *
	 */
	public function loadConfig(string $configName, int $depth = 1) : array
	{
		$configFilePath = __DIR__ . '/../configurations/' . $configName . '.conf';
		
		$elements = array();
		
		if (file_exists($configFilePath)) {
			$parsedConf = parse_ini_file($configFilePath, true);
			
			if (!empty($parsedConf)) {
				// First, import settings from external files
				// TODO: Support multiple imported configurations
				if (array_key_exists('import', $parsedConf)
					&& array_key_exists('configuration', $parsedConf['import'])
				) {
					$elements = $this->loadConfig(
						$parsedConf['import']['configuration'],
						$depth+1
					);
					
					unset($parsedConf['import']);
				}
				
				// Then, create an object for the "config" element
				$configElement = null;
				
				if (array_key_exists('config', $parsedConf)) {
					try {
						$configElement = ElementFactory::create(
							'Config',
							$configName,
							'config',
							$parsedConf['config']
						);
					} catch (Exception $e) {
						$this->exceptionFound = true;
						echo $e->getMessage();
					}
					
					unset($parsedConf['config']);
					$elements['config'] = $configElement;
				}
				
				// $globalConfig holds the key/values we want to give to objects for context
				// 'imagine' and 'frames' are defined afterwards:
				// they shouldn't be used during elements initialization anyway
				$globalConfig['config']   =  $configElement;
				$globalConfig['elements'] =& $this->elements;
				$globalConfig['imagine']  =& $this->imagine;
				$globalConfig['frames']   =& $this->frames;
				
				// Create an object for each separate element
				foreach ($parsedConf as $elementName => $elementParameters) {
					$element = null;
					
					try {
						$element = ElementFactory::create(
							$elementParameters['type'],
							$configName,
							$elementName,
							$elementParameters,
							$globalConfig
						);
					} catch (Exception $e) {
						$this->exceptionFound = true;
						echo $e->getMessage();
					}
					
					$elements[$elementName] = $element;
				}
			}
		}
		
		if (( $depth === 1 ) && ! array_key_exists('config', $elements)) {
			throw new RuntimeException('No [config] section in "' . $configFilePath . '"!');
		}
		
		return $elements;
	}
	
	/**
	 * Save the rendered file to cache
	 *
	 * @todo everything
	 */
	public function saveCache() : int
	{
		return 0;
	}
	
	/**
	 * Purge the cache from old files
	 *
	 * @todo everything
	 */
	public function purgeCache() : int
	{
		return 0;
	}
	
	
	/**
	 * Initialize the "imagine" property-object
	 */
	public function initImagine() : void
	{
		$initialized = true;
		
		switch ($this->elements['config']->renderer) {
			case 'Gd':
				$this->imagine = new ImagineGd();
				break;
			case 'Imagick':
				$this->imagine = new ImagineImagick();
				break;
			case 'Gmagick':
				$this->imagine = new ImagineGmagick();
				break;
			default:
				$initialized = false;
		}
		
		if ($initialized) {
			$this->frames[0] = $this->imagine->create(
				$this->elements['config']->dimensionsObj,
				$this->elements['config']->bgcolorObj
			);
		} else {
			throw new RuntimeException('Invalid renderer ' . $this->elements['config']->renderer);
		}
	}
	
	
	/**
	 * Where images are rendered.
	 *
	 * This method uses the Imagine library to be able to use various
	 * graphics libraries at will.
	 * See https://imagine.readthedocs.io/en/latest/ for details.
	 *
	 * @throws Exception
	 */
	public function renderImage() : void
	{
		if ($this->imagine === null) {
			$this->initImagine();
		}
		
		// Processing elements back to front
		foreach ($this->elements['config']->layoutArr as $elementName) {
			// Skip elements of the layout that aren't declared
			if (!empty($this->elements[$elementName])) {
				// The copy() method returns a GIF image without alpha channel, so we use a working copy in PNG.
				$referenceFrameBin = $this->frames[0]->get('png');
				
				for ($frameNumber = 0; $frameNumber < $this->elements['config']->frames; $frameNumber++) {
					if (!isset($this->frames[$frameNumber])) {
						$this->frames[$frameNumber] = $this->imagine->load($referenceFrameBin);
					}
					
					$this->elements[$elementName]->addToFrame($frameNumber);
				}
			}
		}
		
		// PHP-Gd doesn't support layers and animated Gifs, so we use GifCreator
		if ($this->elements['config']->format === 'gif'
			&& $this->elements['config']->renderer === 'Gd'
			&& count($this->frames) > 1) {
			$gcObject       = new GifCreator();
			$gcFrames       = array();
			$framesDuration = array();
			
			foreach ($this->frames as $layer) {
				$gcFrames[]       = imagecreatefromstring($layer->get('gif'));
				$framesDuration[] = $this->elements['config']->interval * 100;
			}
			
			$gcObject->create($gcFrames, $framesDuration, 1);
			$this->gcImage = $gcObject->getGif();
		} else {
			// Else, continue using Imagine
			
			// Set options according to the image
			$this->renderOptions = array('flatten' => true);
			
			if ($this->elements['config']->format === 'gif') {
				if (count($this->frames) > 1) {
					$this->renderOptions['flatten']        = false;
					$this->renderOptions['animated']       = true;
					$this->renderOptions['animated.delay']
						= $this->elements['config']->interval * 1000;
					$this->renderOptions['animated.loops'] = 0;
				}
			} elseif ($this->elements['config']->format === 'png') {
				// According to Imagine's doc, only PNGs and animated GIFs shouldn't be flattened
				$this->renderOptions['flatten'] = false;
			} elseif ($this->elements['config']->format === 'jpg') {
				$this->renderOptions['jpeg_quality']
					= $this->elements['config']->quality;
			}
			
			// Move all layers to the canvas object
			if (count($this->frames) === 1) {
				$this->canvas = $this->frames[0];
			} else {
				$this->canvas = $this->imagine->create(
					$this->elements['config']->dimensionsObj,
					$this->elements['config']->bgcolorObj
				);
				
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
	 *
	 * @throws Exception
	 */
	public function displayImage() : void
	{
		$save = false;
		
		//TODO: Implement caching
		if ($this->imagine === null) {
			$this->renderImage();
		}
		
		if (!$this->exceptionFound) {
			if ($this->gcImage === null) {
				// The content type header is automatically sent by Imagine
				$this->canvas->show($this->elements['config']->format, $this->renderOptions);
			} elseif ($save) {
				file_put_contents('final.gif', $this->gcImage);
			} else {
				header('Content-type: image/gif');
				header('Content-Disposition: filename="output.gif"');
				echo $this->gcImage;
			}
		}
	}
}
