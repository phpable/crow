<?php
namespace Able\Crow\Exceptions;

use \Able\Exceptions\EInvalid;

class EInvalidSintax extends EInvalid {

	/**
	 * @var string
	 */
	protected static string $template = "Invalid token syntax: %s!";
}
