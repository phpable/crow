<?php
namespace Able\Crow\Structures;

use \Able\Struct\AStruct;
use \Able\Crow\Exceptions\EInvalidSintax;

class SOpenTag extends AStruct {

	/**
	 * @param string $value
	 * @return string
	 * @throws EInvalidSintax
	 */
	protected final function setContentProperty(string $value): string {
		if (!preg_match('/<\?php/', $value)) {
			throw new EInvalidSintax($value);
		}

		return $value;
	}
}
