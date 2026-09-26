<?php
declare( strict_types = 1 );

namespace Ruzgfpegk\GeneratorsImg\Elements;

use Imagine\Image\AbstractFont;
use Imagine\Image\Palette\Color\ColorInterface;
use Imagine\Image\Palette\RGB;

/**
 * Class Text
 *
 * @package Ruzgfpegk\GeneratorsImg\Elements
 */
class Text extends GenericElement
{
	/**
	 * The text that will be displayed, as an array of strings if animation
	 */
	public string|array $string;
	
	/**
	 * The font element name
	 */
	public string $font;
	
	/**
	 * The font size of the text
	 */
	public int $size;
	
	/**
	 * Color: "R,G,B" Properties as string
	 */
	public string $color;
	
	/**
	 * The opacity of the text
	 */
	public int $opacity;
	
	// Objects
	/**
	 * RGBA Element (properties colour and opacity)
	 */
	public ColorInterface $RGBA;
	/**
	 * Font object
	 */
	public AbstractFont $fontObj;
	
	
	/**
	 * Text constructor
	 *
	 * @param string   $configName         Name of the configuration file of the section
	 * @param string   $elementName        Name of the element
	 * @param string[] $elementParameters  Associative array of section parameters
	 * @param array    $globalConfig       Global elements passed to objects
	 */
	public function __construct(string $configName, string $elementName, $elementParameters, array $globalConfig)
	{
		parent::__construct($configName, $elementName, $elementParameters, $globalConfig);
		$this->setDefaults();
		$this->loadSection($elementParameters);
		$this->postLoad();
	}
	
	/**
	 * Defaults of the class
	 */
	public function setDefaults() : void
	{
		parent::setDefaults();
		$this->string  = 'UNDEFINED STRING';
		$this->font    = 'UNDEFINED FONT';
		$this->size    = 12;
		$this->color   = '255,255,255';
		$this->opacity = 100;
	}
	
	/**
	 * @param string[] $section Associative array of section parameters
	 */
	public function loadSection(array $section) : void
	{
		parent::loadSection($section);
		
		if (array_key_exists('string', $section)) {
			$this->string = $section['string'];
		}
		
		if (array_key_exists('font', $section)) {
			$this->font = $section['font'];
		}
		
		if (array_key_exists('size', $section)) {
			$this->size = (int) $section['size'];
		}
		
		if (array_key_exists('color', $section)) {
			$this->color = $section['color'];
		}
	}
	
	/**
	 * Internal treatments/checks to run after the conf is loaded
	 */
	public function postLoad() : void
	{
		parent::postLoad();
		
		// Prepare properties for further use
		$tempColor = explode(
			',',
			$this->color
		);
		
		// Initialize RGBA property, InterfaceObject
		$this->RGBA = ( new RGB() )->color($tempColor, (int)$this->opacity);
		
		$this->argumentReplace();
	}
	
	/**
	 * Replaces %example% strings in the "string" property by the value of the
	 * "example" POST key.
	 */
	public function argumentReplace() : void
	{
		// Replace text parameters by filtered values from GET
		$varPattern        = '/%([a-z0-9]+)%/i';
		$returnFilteredGet = static fn($input) => filter_input(INPUT_GET, $input[1]);
		
		while (preg_match($varPattern, $this->string)) {
			$this->string = preg_replace_callback(
				$varPattern,
				$returnFilteredGet,
				$this->string
			);
		}
	}
	
	/**
	 * Add a text element on top of a frame
	 * Called from renderImage() in RealTimeGenerator.php
	 *
	 * @param $frameNumber integer Frame on which to add the text
	 */
	public function addToFrame(int $frameNumber = 0) : void
	{
		$this->addText($frameNumber);
	}
	
	
	/**
	 * Add a text element on top of the image
	 *
	 * @param integer     $frameNumber frame on which to add the text
	 * @param string|null $overrideStr to use the specified string instead of the element one
	 */
	protected function addText(int $frameNumber = 0, ?string $overrideStr = null): void
	{
		$sourcePosObj = $this->positionObj;
		
		// Initialise Font property AbstractFont
		// font path has already been checked for in the Font element
		$fontElement   = $this->globalConfig['elements'][$this->font];
		
		$this->fontObj = $this->globalConfig['imagine']->font(
			$fontElement->fontPath,
			$this->size,
			$this->RGBA
		);
		
		if (empty($overrideStr)) {
			if (is_array($this->string)) {
				$text = $this->string[$frameNumber];
			} else {
				$text = $this->string;
			}
		} else {
			$text = $overrideStr;
		}
		
		// Draw the final text on the requested frame
		if (is_object($this->fontObj)) {
			$this->globalConfig['frames'][$frameNumber]->draw()
				->text($text, $this->fontObj, $sourcePosObj);
		}
	}
}
