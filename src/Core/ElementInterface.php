<?php
declare( strict_types = 1 );

namespace Ruzgfpegk\GeneratorsImg\Core;

/**
 * Interface ElementInterface
 *
 * @package Ruzgfpegk\GeneratorsImg\Core
 */
interface ElementInterface
{
	/**
	 * Set default values of the object properties
	 */
	public function setDefaults();
	
	/**
	 * @param string[] $section Associative array of section parameters
	 */
	public function loadSection($section);
	
	/**
	 * Internal treatments/checks to run after the conf is loaded
	 */
	public function postLoad();
}
