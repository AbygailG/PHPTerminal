#!/usr/bin/php
<?php

require('terminalhandler.php');

Class TestTerminal extends TerminalHandler {
	function __construct() {
		$text = 'Please select to option you want to exectue:';

		$options = array(
			'Create file' => 'askFileName',
			'Fail' => 'thisWillFail',
			'Exit' => 'quit'
		);

		$default = 'Exit';

		$this->requestInput($text, $options, $default);
	}

	public function askFileName() {
		$this->requestInput('Please enter the filename you wish to create:');
	}

	public function thisWillFail() {
		return false;
	}

	public function quit() {
		exit(0);
	}
}

new TestTerminal();
?>
