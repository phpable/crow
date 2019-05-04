<?php
namespace Able\Crow;

use \Able\Crow\Abstractions\AConsumer;
use \Able\Crow\Consumers\CFunction;

use \Able\Helpers\Arr;
use \Able\Helpers\Src;

use \Able\IO\Path;
use \Able\IO\File;
use \Able\IO\Directory;

use \Able\Reglib\Regex;

use \Exception;
use \Generator;

class Engine {

	/**
	 * @const string
	 */
	protected const CT_YIELD = 'yield';

	/**
	 * @const string
	 */
	protected const CT_TO_CLASS = 'to_class';

	/**
	 * @const string
	 */
	protected const CT_CLASS = 'class';

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
	 * @var array
	 */
	private array $Queue = [];

	/**
	 * @param File $File
	 * @throws Exception
	 */
	public final function register(File $File): void {
		array_push($this->Queue, $File);
	}

	/**
	 * @var array
	 */
	private array $Cache = [];

	/**
	 * @param string $class
	 * @param CFunction $Consumer
	 *
	 * @throws Exception
	 */
	protected final function cacheMethod(string $class, CFunction $Consumer): void {
		if (!is_null(Arr::follow($this->Cache,
			'class', $class = Src::tcm($class), $Consumer->name()))) {

				throw new Exception(sprintf('Method %s is duplicated for class %s', $Consumer->name(), $class));
		}

		$this->Cache = Arr::place($this->Cache, $Consumer, 'class', $class, $Consumer->name());
	}

	/**
	 * @param string $name
	 * @return Generator
	 *
	 * @throws Exception
	 */
	protected final function combineClass(string $name): Generator {
		if (!Regex::checkVariable($name)) {
			throw new Exception(sprintf('Invalid class name: %s!',
				$name));

		}

		if (is_null($Signature = Arr::follow($this->Cache, 'class', $name))) {
			throw new Exception(sprintf('Undefined class name: %s!',
				$name));

		}

		yield sprintf("class %s {\n", $name);

		foreach ($Signature as $Comsumer) {
			yield from $Comsumer->iterate();
		}

		yield "\n}\n";
	}

	/**
	 * @return Generator
	 * @throws Exception
	 */
	public function parse(): Generator {
		foreach ($this->Queue as $File) {
			$Tokens = token_get_all($File->getContent(), TOKEN_PARSE);

			while (count($Tokens) > 0) {
				$Parsed = array_shift($Tokens);

				/**
				 * The queue is gonna be linked into a single file,
				 * so any opening or closing tags have to be ignored.
				 */
				if (is_array($Parsed)
					&& in_array(trim($Parsed[1]), ['<?php', '<?=', '?>'])) {

						continue;
				}

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
					&& preg_match('/\/\*[\s*]*<%([A-Za-z][A-Za-z0-9_]+)(?::(.+))?%>[\s*]*\*\//', $Parsed[1], $Data)) {

					switch (strtolower($Data[1])) {
						case self::CT_YIELD:
							if (!isset(self::$Resolvers[$Data[2]])) {
								throw new \Exception(sprintf('Undefined resolver: %s!', $Data[2]));
							}

							yield from self::$Resolvers[$Data[2]]();
							continue 2;

						case self::CT_TO_CLASS:
							if (!Regex::checkVariable($Data[2])
								&& $Data[2] != '@anonymous') {

									throw new Exception(sprintf('Invalid calss name: %s', $Data[2]));
							}

							$this->cacheMethod($Data[2],
								(new CFunction())->consume($Tokens));

							continue 2;

						case self::CT_CLASS:
							yield from $this->combineClass($Data[2]);
							continue 2;
					}
				}

				yield $Parsed[1];
			}
		}
	}
}
