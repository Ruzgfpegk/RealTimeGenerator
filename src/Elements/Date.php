<?php
declare( strict_types = 1 );

namespace Ruzgfpegk\GeneratorsImg\Elements;

use DateTime;
use DateTimeZone;
use Exception;
use RuntimeException;

/**
 * Class Date
 *
 * @package Ruzgfpegk\GeneratorsImg\Elements
 */
class Date extends Text
{
	/**
	 * Internal variable: has the string field been converted to text?
	 */
	public bool $isConverted;
	
	/**
	 * The date that will be displayed, a DateTime-compatible string if $isConverted is false, as an array of strings if animation
	 */
	public string|array $string;
	
	/**
	 * The timezone of the date, a DateTimeZone-compatible string
	 */
	public string $timezone;
	
	/**
	 * Date constructor
	 *
	 * @param string   $configName         Name of the configuration file of the section
	 * @param string   $elementName        Name of the element
	 * @param string[] $elementParameters  Associative array of section parameters
	 * @param array    $globalConfig       Global elements passed to objects
	 *
	 * @throws RuntimeException
	 * @throws Exception
	 */
	public function __construct(string $configName, string $elementName, array $elementParameters, array $globalConfig)
	{
		parent::__construct($configName, $elementName, $elementParameters, $globalConfig);
		$this->setDefaults();
		$this->loadSection($elementParameters);
		$this->postLoad();
		$this->strToDate();
	}
	
	
	/**
	 * Defaults of the class
	 */
	public function setDefaults() : void
	{
		parent::setDefaults();
		
		$this->string      = 'Y-m-d H:i:s';
		$this->timezone    = date_default_timezone_get(); // date.timezone of php.ini, UTC if null
		$this->isConverted = false;
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
		
		if (array_key_exists('timezone', $section)) {
			$this->timezone = $section['timezone'];
		}
	}
	
	/**
	 * Function to transform a date format string into a final string.
	 *
	 * @param  int  $frames    the number of elements to return
	 * @param  int  $interval  the delay in seconds between two elements
	 *
	 * @throws Exception
	 */
	public function strToDate(int $frames = 1, int $interval = 1) : void
	{
		$framesStr = [];
		$params    = [];
		
		if ($this->timezone !== null) {
			$tzObject = new DateTimeZone($this->timezone);
			$params   = [ 'now', $tzObject ];
		} else {
			$params = [ 'now' ];
		}
		
		$date = new DateTime(...$params);
		
		if ($frames === 1) {
			$framesStr = $date->format($this->string);
		} else {
			for ($i = 1; $i <= $frames; $i++) {
				$framesStr[] = $date->format($this->string);
				$date->modify($interval . ' second');
			}
		}
		
		$this->isConverted = true; // Unused right now
		$this->string      = $framesStr;
	}
}
