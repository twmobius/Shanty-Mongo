<?php

namespace Shanty\Mongo\Connection;

use Shanty\Mongo\Connection,
  Shanty\Mongo\Exception as ShantyException;

/**
 * @category   Shanty
 * @package    Shanty\Mongo
 * @copyright  Shanty Tech Pty Ltd
 * @license    New BSD License
 * @author     Coen Hyde
 */
class Stack implements \SeekableIterator, \Countable, \ArrayAccess
{
	protected $_position = 0;
	protected $_nodes = array();
	protected $_weights = array();
	protected $_options = array(
		'cacheConnectionSelection' => true
	);
	protected $_cacheConnectionSelection = true;
	protected $_cachedConnection = null;

	/**
	 * Get an option
	 *
	 * @param string $option
	 */
	public function getOption($option)
	{
		if (!array_key_exists($option, $this->_options)) {
			return null;
		}

		return $this->_options[$option];
	}

	/**
	 * Set an option
	 *
	 * @param string $option
	 * @param mixed $value
	 */
	public function setOption($option, $value)
	{
		$this->_options[$option] = $value;
	}

	/**
	 * Set Options
	 *
	 * @param array $options
	 */
	public function setOptions(array $options)
	{
		$this->_options = array_merge($this->_options, $options);
	}

	/**
	 * Add node to connection stack
	 *
	 * @param Shanty\Mongo\Connection $connection
	 * @param int $weight
	 */
	public function addNode(Connection $connection, $weight = 1)
	{
		$this->_nodes[] = $connection;
		$this->_weights[] = (int) $weight;
	}

	/**
	 * Select a node from the connection stack.
	 *
	 * @return Shanty\Mongo\Connection
	 */
	public function selectNode()
	{
		if (count($this) == 0) {
			// no nodes to select from
			return null;
		}

		// Return the cached connection if available
		if ($this->cacheConnectionSelection() && $this->hasCachedConnection()) {
			return $this->getCachedConnection();
		}

		// Select a new connection
		$r = mt_rand(1,array_sum($this->_weights));
		$offset = 0;
		foreach ($this->_weights as $k => $weight) {
			$offset += $weight;
			if ($r <= $offset) {
				$connection = $this->_nodes[$k];
				break;
			}
		}

		// Cache the connection for later use
		if ($this->cacheConnectionSelection()) {
			$this->_cachedConnection = $connection;
		}

		return $connection;
	}

	/**
	 * Determine if this connection stack has a cached connection
	 *
	 * @return boolean
	 */
	public function hasCachedConnection()
	{
		return !is_null($this->_cachedConnection);
	}

	/**
	 * Get the cached connection
	 *
	 * @return Shanty\Mongo\Connection
	 */
	public function getCachedConnection()
	{
		return $this->_cachedConnection;
	}

	/**
	 * Get or set the flag to determine if the first connection selection should be cached
	 *
	 * @param boolean $value
	 */
	public function cacheConnectionSelection($value = null)
	{
		if (!is_null($value)) {
			$this->_options['cacheConnectionSelection'] = (boolean) $value;
		}

		return $this->_options['cacheConnectionSelection'];
	}

	/**
	 * Seek to a particular connection
	 *
	 * @param $position
	 */
	public function seek($position)
	{
		if (!is_numeric($position)) {
			throw new ShantyException("Position must be numeric");
		}

		$this->_position = $position;

		if (!$this->valid()) {
			throw new \OutOfBoundsException("invalid seek position ($position)");
		}
	}

	/**
	 * Get the current connection
	 *
	 * @return Shanty\Mongo\Connection
	 */
	public function current()
	{
		return $this->_nodes[$this->_position];
	}

	/**
	 * Get teh current key
	 *
	 * @return int
	 */
	public function key()
	{
		return $this->_position;
	}

	/**
	 * Move the pointer to the next connection
	 */
	public function next()
	{
		$this->_position +=1;
	}

	/**
	 * Rewind the pointer to the begining of the stack
	 */
	public function rewind()
	{
		$this->_position = 0;
	}

	/**
	 * Is the location of the current pointer valid
	 */
	public function valid()
	{
		return $this->offsetExists($this->_position);
	}

	/**
	 * Count all the connections
	 */
	public function count()
	{
		return count($this->_nodes);
	}

	/**
	 * Test if an offset exists
	 *
	 * @param int $offset
	 */
	public function offsetExists($offset)
	{
		return array_key_exists($offset, $this->_nodes);
	}

	/**
	 * Get an offset
	 *
	 * @param int $offset
	 */
	public function offsetGet($offset)
	{
		if (!$this->offsetExists($offset)) return null;

		return $this->_nodes[$offset];
	}

	/**
	 * Set an offset
	 *
	 * @param Shanty\Mongo\Connection $offset
	 * @param $connection
	 */
	public function offsetSet($offset, $connection)
	{
		if (!is_numeric($offset)) {
			throw new ShantyException("Offset must be numeric");
		}

		$this->_nodes[$offset] = $connection;
		$this->_weights[$offset] = 1;
	}

	/**
	 * Unset an offset
	 *
	 * @param int $offset
	 */
	public function offsetUnset($offset)
	{
		unset($this->_nodes[$offset]);
		unset($this->_weights[$offset]);
	}
}
