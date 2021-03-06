<?php
namespace Spot;
use Spot\Adapter\AdapterInterface;

/**
 * @package Spot
 */
class Config implements \Serializable
{
	/** @var string */
	protected $defaultConnection;

	/** @var array */
	protected $connections = array();

	/** @var \Spot\Config */
	protected static $instance;

	/** @var array */
	protected static $typeHandlers = array();

	protected function __construct()
	{
		static::$typeHandlers = array(
			'string' => '\\Spot\\Type\\String',
			'text' => '\\Spot\\Type\\String',

			'int' => '\\Spot\\Type\\Integer',
			'integer' => '\\Spot\\Type\\Integer',

			'float' => '\\Spot\\Type\\Float',
			'double' => '\\Spot\\Type\\Float',
			'decimal' => '\\Spot\\Type\\Float',

			'bool' => '\\Spot\\Type\\Boolean',
			'boolean' => '\\Spot\\Type\\Boolean',

			'datetime' => '\\Spot\\Type\\Datetime',
			'date' => '\\Spot\\Type\\Datetime',
			'timestamp' => '\\Spot\\Type\\Integer',
			'year' => '\\Spot\\Type\\Integer',
			'month' => '\\Spot\\Type\\Integer',
			'day' => '\\Spot\\Type\\Integer',
		);
	}

	/**
	 * Dont allow cloning
	 */
	protected function __clone() {}

	/**
	 * Singleton method
	 * @param bool $reset, if flag is true, re-instantiate the singleton instance
	 * @return \Spot\Config
	 */
	public static function getInstance($reset = false)
	{
		if ($reset === true || !isset(static::$instance)) {
			static::$instance = new static;
		}
		return static::$instance;
	}

	/**
	 * Set type handler class by type
	 * @param string $type Field type (i.e. 'string' or 'int', etc.)
	 * @param string $class
	 */
	public static function setTypeHandler($type, $class)
	{
		static::$typeHandlers[(string) $type] = (string) $class;
	}

	/**
	 * Get type handler class by type
	 * @param string $type
	 * @return string
	 */
	public static function getTypeHandler($type)
	{
		if (!isset(static::$typeHandlers[$type])) {
			throw new \InvalidArgumentException("Type '$type' not registered. Register the type class handler with \Spot\Config::typeHanlder('$type', '\Namespaced\Path\Class').");
		}
		return static::$typeHandlers[$type];
	}

	/**
	 * Add database connection
	 * @param string $name Unique name for the connection
	 * @param PDO $conn PDO connection, managed outside
	 * @param array $options Array of key => value options for adapter
	 * @param boolean $defaut Use this connection as the default? The first connection added is automatically set as the default, even if this flag is false.
	 * @return \Spot\Adapter\AdapterInterface
	 * @throws \Spot\Exception
	 */
	public function addConnection($name, AdapterInterface $adapter, $default = false)
	{
		// Connection name must be unique
		if (isset($this->connections[$name])) {
			throw new Exception("Connection for '" . $name . "' already exists. Connection name must be unique.");
		}

		// Set as default connection?
		if (true === $default || null === $this->defaultConnection) {
			$this->defaultConnection = $name;
		}

		// Store connection and return adapter instance
		$this->connections[$name] = $adapter;
		return $adapter;
	}

	/**
	 * Get connection by name
	 * @param string $name Unique name of the connection to be returned
	 * @return \Spot\Adapter\AdapterInterface
	 * @throws \Spot\Exception
	 */
	public function connection($name = null)
	{
		null === $name && $name = $this->defaultConnection;
		return (isset($this->connections[$name])) ? $this->connections[$name] : false;
	}

	/**
	 * Get default connection
	 * @return \Spot\Adapter\AdapterInterface
	 */
	public function defaultConnection()
	{
		return $this->connection($this->defaultConnection);
	}

	/**
	 * Prevent adapter connections from being serialized
	 * @return string
	 */
	public function serialize()
	{
		return serialize(array());
	}

	/**
	 * {@inherit}
	 */
	public function unserialize($serialized) {}

	/**
	 * Class loader
	 *
	 * @param string $className Name of class to load
	 * @return bool
	 */
	public static function loadClass($className)
	{
		$loaded = false;

		// Require Spot namespaced files by assumed folder structure (naming convention)
		if (false !== strpos($className, 'Spot\\')) {
			$classFile = trim(str_replace('\\', '/', str_replace('_', '/', str_replace('Spot\\', '', $className))), '\\');
			$loaded = require_once(__DIR__ . '/' . $classFile . '.php');
		}

		return $loaded;
	}
}

/**
 * Register 'spot_load_class' function as an autoloader for files prefixed with 'Spot_'
 */
spl_autoload_register(array('\\Spot\\Config', 'loadClass'));
