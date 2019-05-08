<?php
namespace Able\Crow\Consumers;

use \Able\Crow\Abstractions\AConsumer;

use \Generator;
use \Exception;

class CFunction extends AConsumer {

	/**
	 * @var string|null
	 */
	private ?string $name = null;

	/**
	 * @return string|null
	 */
	public final function name(): ?string {
		return $this->name;
	}

	/**
	 * @param array $Tokens
	 * @return CFunction
	 *
	 * @throws Exception
	 */
	function consume(array &$Tokens): CFunction {
		if ($this->count() > 0) {
			throw new Exception('Cache is not empty!');
		}

		/**
		 * The function block can be consolidated as closed only
		 * if the number of opening curly brackets is equal to closing curly brackets.
		 */
		$brackets = 0;

		/**
		 * Brackets calculation has no sense
		 * until the first opening curly bracket remains unrecognized.
		 */
		$opened = false;

		while (count($Tokens) > 0) {
			$Token = array_shift($Tokens);

			/**
			 * Following the PHP semantics,
			 * the first string token is always will be the function name.
			 */
			if (is_array($Token)
				&& is_null($this->name)
				&& $Token[0] == T_STRING) {

					$this->name = $Token[1];
			}

			/**
			 * If the current token is the opening curly bracket,
			 * the counting process must be initiated.
			 */
			if (!is_array($Token)
				&& !$opened
				&& $Token == '{') {

					$opened = true;
			}

			if (!is_array($Token)
				&& $opened
				&& $Token == '{') {

					$brackets++;
			}

			if (!is_array($Token)
				&& $opened
				&& $Token == '}') {

					$brackets--;
			}

			$this->cache(is_array($Token) ? $Token[1] : $Token);
			if (!is_null($this->name)
				&& $opened
				&& $brackets < 1) {

					while (count($Tokens) > 0

						&& is_array($Tokens[0])
						&& $Tokens[0][0] == T_WHITESPACE) {

							array_shift($Tokens);
					}

					break;
			}
		}

		return $this;
	}
}
