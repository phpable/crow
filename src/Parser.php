<?php
namespace Able\Crow;

use \Able\IO\Path;
use \Able\IO\File;
use \Able\IO\Directory;

use \Exception;
use \Generator;

class Parser {

	/**
	 * @var callable[]
	 */
	private static array $Resolvers = [];

	/**
	 * @param string $name
	 * @param callable $Handler
	 */
	public static final function resolve(string $name, callable $Handler): void {
		self::$Resolvers[$name] = $Handler;
	}

	/**
	 * @const string
	 */
	protected const IT_YIELD = 'yield';

	/**
	 * @param File $Source
	 * @return Generator
	 *
	 * @throws Exception
	 */
	public function parse(File $Source): Generator {
		foreach (token_get_all($Source->getContent(), TOKEN_PARSE) as $Parsed) {

			/**
			 * The simple literal are always return as is
			 * without any reformation.
			 */
			if (!is_array($Parsed)) {
				yield $Parsed;
				continue;
			}

			/**
			 * The current version of CROW engine is required
			 * the special syntax be wrapped by comments.
			 */
			if ($Parsed[0] == T_COMMENT
				&& preg_match('/\/\*<%(yield)(?::([A-Za-z0-9_-]+))?%>\*\//', $Parsed[1], $Data)) {

					switch (strtolower($Data[1])){
						case self::IT_YIELD:
							if (!isset(self::$Resolvers[$Data[2]])) {
								throw new \Exception(sprintf('Undefined resolver: %s!', $Data[2]));
							}

							yield from self::$Resolvers[$Data[2]]();
							continue 2;
					}
			}

			yield $Parsed[1];
		}
	}
}
