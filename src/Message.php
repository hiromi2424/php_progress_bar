<?php

namespace ProgressBar;

class Message {

	public $start;
	public $last;
	public $dataCount;
	protected $_previousLineLength;

	public function __construct($dataCount) {
		$this->start = microtime(true);
		$this->dataCount = $dataCount;
		$this->_previousLineLength = 0;
	}

	public function show($position, $last = null) {
		if ($last !== null) {
			$this->last = $last;
		}

		$now = microtime(true);
		$elapsed = $now - $this->start;

		if ($elapsed && $this->last !== null) {
			$numPerSecForLast = $position / ($this->last - $this->start);
			$position = $position + $numPerSecForLast * ($now - $this->last);
		}
		$numPerSec = $elapsed ? $position / $elapsed : 0;

		$estimated = $numPerSec ? $this->dataCount / $numPerSec : 0;
		$remaining = $estimated ? $estimated - $elapsed : 0;

		$message = $this->_message($position, $elapsed, $remaining);
		$this->_show($message);
	}

	protected function _message($position, $elapsed, $remaining) {
		$message = "$position/$this->dataCount";

		$elapsedTime = $this->_formatSecond($elapsed);
		$remainingTime = $this->_formatSecond($remaining);
		$message .= " ${elapsedTime} passed. Remaining about ${remainingTime}.";

		return $message;
	}

	protected function _show($message) {
		$this->out("\r");

		$this->out($message);
		$length = strlen($message);
		$remainingLength = $this->_previousLineLength - $length;
		if ($remainingLength > 0) {
			$this->out(str_repeat(' ', $remainingLength));
		}

		$this->_previousLineLength = $length;
	}

	protected function _formatSecond($seconds) {
		$time = $this->_calculateTime($seconds);
		return $this->_formatTime($time);
	}

	protected function _formatTime($time) {
		return sprintf('%02d:%02d:%02d', $time['hours'], $time['minutes'], $time['seconds']);
	}

	protected function _calculateTime($seconds) {
		$time = [
			'hours' => 0,
			'minutes' => 0,
		];

		if ($seconds >= 3600) {
			$time['hours'] = (int)floor($seconds / 3600);
			$seconds -= $time['hours'] * 3600.0;
		}

		if ($seconds >= 60) {
			$time['minutes'] = (int)floor($seconds / 60);
			$seconds -= $time['minutes'] * 60.0;
		}

		$time['seconds'] = $seconds;
		return $time;
	}

	public function out($str) {
		echo $str;
	}

}