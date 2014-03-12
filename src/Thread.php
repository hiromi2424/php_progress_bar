<?php

namespace ProgressBar;

use ProgressBar\Message;

class Thread extends \Thread {

	public $Message;
	protected $_intervalMSec;
	protected $_position;
	protected $_continue;
	protected $_start;
	protected $_immediate;

	public function __construct(Message $Message, $intervalMSec) {
		$this->Message = $Message;
		$this->_intervalMSec = $intervalMSec;
		$this->_position = 0;
		$this->_continue = true;
		$this->_start = microtime(true);
		$this->_immediate = false;
	}

	public function run() {
		while ($this->_continue) {
			if ($this->_immediate) {
				$this->Message->show($this->_position);
				$this->_immediate = false;
				continue;
			}

			$now = microtime(true);
			if ($now - $this->_start > $this->_intervalMSec / 1000) {
				$this->Message->show($this->_position);
				$this->_start = microtime(true);
			}
		}
	}

	public function stop() {
		$this->_continue = false;
		$this->join();
	}

	public function setPosition($position) {
		$this->_position = $position;
	}

	public function immediate() {
		$this->_immediate = true;
	}

}