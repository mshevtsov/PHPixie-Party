<?php
namespace PHPixie;

Class Party {
	public $pixie;
	public $dirs = array();
	public $rootDir;

	public function __construct($pixie) {
		$this->pixie = $pixie;
		$this->rootDir = $this->pixie->root_dir;
	}

	// private function loadLibs() {
	// 	$this->participant = new \MShevtsov\Party\Participant;
	// 	$this->order = new \MShevtsov\Party\Order;
	// 	$this->payment = new \MShevtsov\Party\Payment;
	// }
}
