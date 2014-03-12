<?php

namespace ProgressBar;

use ProgressBar\Message;
use ProgressBar\Thread;

class ProgressBar {

	public $data;
	public $callback;
	public $options;
	public $Message;
	public $messageClass = 'ProgressBar\Message';
	public $threadClass = 'ProgressBar\Thread';

	protected $_previousLineLength = 0;
	protected $_position;
	protected $_dataCount;

	public function __construct(array $data, callable $callback, array $options = []) {
		$this->data = $data;
		$this->callback = $callback;
		$this->configure($options);
	}

	public function configure(array $options = []) {
		$this->options = $options + static::_defaultOptions();
	}

	protected static function _defaultOptions() {
		return [
			'started' => "Started at: %s\n",
			'finished' => "Finshed at: %s\n",
			'procedure' => null,
			'interval' => 250,
			'skip' => null,
			'autoSkip' => 10000,
			'defaultSkip' => 10,
		];
	}

	public static function process(array $data, callable $callback, array $options = []) {
		$self = new self($data, $callback, $options);

		$procedure = $self->options['procedure'];
		if ($procedure === null) {
			$procedure = extension_loaded('pthreads') ? 'thread' : 'serial';
		}

		if (!method_exists($self, $procedure)) {
			trigger_error("Specified procedure '$procedure' is not defined in " . __CLASS__);
			return;
		}

		$self->init();
		$self->$procedure();
		$self->finish();

		return $self;
	}

	public function init() {
		$this->_dataCount = count($this->data);
		$this->_position = 0;
		$this->Message = new $this->messageClass($this->_dataCount);

		if ($this->options['started']) {
			$this->Message->out(sprintf($this->options['started'], date('Y-m-d H:i:s')));
		}

		$this->Message->show($this->_position);
	}

	public function finish() {
		$this->Message->show($this->_position);
		$this->Message->out("\n");
		if ($this->options['finished']) {
			$this->Message->out(sprintf($this->options['finished'], date('Y-m-d H:i:s')));
		}
	}

	public function thread() {
		$thread = new $this->threadClass($this->Message, $this->options['interval']);

		$thread->start();
		foreach ($this->data as $key => $value) {
			$this->_forward($key, $value);
			$thread->setPosition($this->_position);

			if (!$this->_shouldSkip()) {
				$thread->immediate();
			}
		}
		$thread->stop();
	}

	public function serial() {
		foreach ($this->data as $key => $value) {
			$this->_forward($key, $value);
			if (!$this->_shouldSkip()) {
				$this->Message->show($this->_position);
			}
		}
	}

	protected function _forward($key, $value) {
		$result = call_user_func($this->callback, $key, $value);

		$this->_position++;
	}

	protected function _shouldSkip() {
		$skip = $this->options['skip'];
		if ($skip === null && $this->_dataCount > $this->options['autoSkip']) {
			$skip = $this->options['defaultSkip'];
		}
		return $skip && $this->_position % $skip !== 0;
	}

}
