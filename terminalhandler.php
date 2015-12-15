<?php

readline_callback_handler_install('', function() { });

class TerminalHandler {
	private $textColors = array(
		'BLACK'       => "0;30",
		'BLUE'        => "0;34",
		'BOLD'        => "1",
		'BROWN'       => "0;33",
		'CYAN'        => "0;36",
		'GREEN'       => "0;32",
		'LIGHT_BLUE'  => "1;34",
		'LIGHT_CYAN'  => "1;36",
		'LIGHT_GREEN' => "1;32",
		'LIGHT_RED'   => "1;31",
		'MAGENTA'     => "1;35",
		'NORMAL'      => "0",
		'RED'         => "0;31",
		'REVERSE'     => "7",
		'UNDERSCORE'  => "4",
		'WHITE'       => "1;37",
		'YELLOW'      => "1;33"
	);
	private $backgroundColors = array(
		'BLACK'      => '40',
		'BLUE'       => '44',
		'CYAN'       => '46',
		'GREEN'      => '42',
		'LIGHT_GRAY' => '47',
		'MAGENTA'    => '45',
		'NORMAL'     => '0',
		'RED'        => '41',
		'YELLOW'     => '43'
	);
	private $useFullScreen = true;
	private $activeTextColor = 'NORMAL';
	private $activeBackgroundColor = 'NORMAL';
	private $activeSelectionColor = 'NORMAL';
	private $activeSelectionBackgroundColor = 'LIGHT_GRAY';
	private $borderCharacters = array('╔','╗','╝','╚','═','║');
	private $currentTerminalSize = array(0,0);
	private $textAlign = 'left';

	private function resetColorCodes() {
		echo "\033[0m";
	}

	private function activateColorCodes() {
		if($this->activeTextColor !== 'NORMAL') echo "\033[".$this->textColors[$this->activeTextColor].'m';
		if($this->activeBackgroundColor !== 'NORMAL') echo "\033[".$this->backgroundColors[$this->activeBackgroundColor].'m';
	}

	private function getTerminalSize() {
		$cols = `tput cols`;
		$rows = `tput lines`;

		return array('cols' => $cols, 'rows' => $rows);
	}

	private function clearScreen() {
		echo "\033[2J";
	}

	private function moveCursorTo($line = 0, $column = 0) {
		echo "\033[". ($line > 0 ? $line : 0) .";". ($column > 0 ? $columm : 0) ."H";
	}

	private function moveCorsor($x = 0, $y = 0) {
		if ($x < 0) {
			echo "\033[{y}C";
		} else if ($x > 0) {
			echo "\033[{y}C";
		}
		if ($y < 0) {
			echo "\033[{y}C";
		} else if ($y > 0) {
			echo "\033[{y}C";
		}
	}

	private function eraseTillEnd() {
		echo "\033[K";
	}

	private function renderBorderWithText($text = '', $options = array(), $default = null) {
		$size = $this->getTerminalSize();

		// Top border
		echo $this->borderCharacters[0];
		echo str_repeat($this->borderCharacters[4], $size['cols'] - 2);
		echo $this->borderCharacters[1];

		// First line is always empty
		echo $this->borderCharacters[5];
		echo str_repeat(' ', $size['cols'] - 2);
		echo $this->borderCharacters[5];

		// Echo our texts
		$text = chunk_split($text, $size['cols'] - 4, "\n");
		$lines = explode("\n", $text);
		foreach($lines as $line) {
			echo $this->borderCharacters[5];
			switch($this->textAlign) {
				case 'left':
				default:
				echo ' '.$line;
				echo str_repeat(' ', $size['cols'] - 3 - strlen($line));
			}
			echo $this->borderCharacters[5];
		}

		// Spacer before the list of options
		echo $this->borderCharacters[5];
		echo str_repeat(' ', $size['cols'] - 2);
		echo $this->borderCharacters[5];

		echo $this->borderCharacters[5];
		$first = true;
		$chrCount = 2;
		foreach($options as $optionText => $option) {
			echo ' ';
			$chrCount++;
			if((!$default && $first) || $optionText === $default) {
				$color = $this->activeTextColor;
				$backgroundColor = $this->activeBackgroundColor;
				
				$this->activeTextColor = $this->activeBackgroundColor;
				$this->activeSelectionColor = $this->activeSelectionBackgroundColor;

				$this->activateColorCodes();
				echo '*'. $optionText. '*';
				$chrCount += 2;

				$this->activeTextColor = $color;
				$this->activeSelectionColor = $backgroundColor;

				$this->activateColorCodes();
			} else {
				echo $optionText;
			}
			$chrCount += strlen($optionText);
			$first = false;
		}
		echo str_repeat(' ', $size['cols'] - $chrCount);
		echo $this->borderCharacters[5];

		// Fill the rest of the screen
		for($i = 0; $i < $size['rows'] - 3 - count($lines); $i++) { // -2 for the top and bottom border
			echo $this->borderCharacters[5];
			echo str_repeat(' ', $size['cols'] - 2);
			echo $this->borderCharacters[5];
		}

		// Bottom border
		echo $this->borderCharacters[3];
		echo str_repeat($this->borderCharacters[4], $size['cols'] - 2);
		echo $this->borderCharacters[2];
	}

	protected function setFullScreen($boolean) {
		$this->useFullScreen = (bool)$boolean;
	}

	protected function setTextColor($color) {
		if(!isset($this->textColors[strtoupper($color)])) return false;
		$this->activeTextColor = strtoupper($color);
	}

	protected function setBackgroundColor($color) {
		if(!isset($this->backgroundColors[strtoupper($color)])) return false;
		$this->activeBackgroundColor = strtoupper($color);
	}

	protected function setSelectionColor($color) {
		if(!isset($this->textColors[strtoupper($color)])) return false;
		$this->activeSelectionColor = strtoupper($color);
	}

	protected function setSelectionBackgroundColor($color) {
		if(!isset($this->backgroundColors[strtoupper($color)])) return false;
		$this->activeSelectionBackgroundColor = strtoupper($color);
	}

	protected function requestInput($text, $options, $default = null) {
		$this->activateColorCodes();
		if($this->useFullScreen) {
			$this->moveCursorTo();
			$this->renderBorderWithText($text, $options, $default);
		} else {
			echo $text.PHP_EOL;
		}
		$currentActive = $default;
		$control = false;
		if(count($options)) {
			$keys = array_keys($options);
			if(is_null($default)) $default = $keys[0];
		}
		while (true) {
			if($this->currentTerminalSize != implode(':',$this->getTerminalSize())) {
				$this->moveCursorTo();
				$this->renderBorderWithText($text, $options, $default);
				$this->currentTerminalSize = implode(':',$this->getTerminalSize());
			}
			$r = array(STDIN);
			$w = NULL;
			$e = NULL;
			$n = stream_select($r, $w, $e, 0);
			if ($n && in_array(STDIN, $r)) {
				$c = stream_get_contents(STDIN, 1);
				if(ord($c) === 10) {
					if(is_string($options[$default]) && is_callable($options[$default])) {
						echo 'Execute basic!';
					};
					
					if(is_string($options[$default])) {
						$method = array($this, $options[$default]);
						if(is_callable($method)) {
							$functionName = $options[$default];
							// todo: Handle fails
							$this->$functionName();
						}
					};
				}
				if($control) {
					switch($c) {
						case 'A':
							// Arrow UP
						case 'D':
							// Arrow LEFT
							$current = array_search($default, $keys);
							if(isset($keys[$current - 1])) $default = $keys[$current - 1];
							break;
						case 'C':
							// Arrow RIGHT
						case 'B':
							// Arrow DOWN
							$current = array_search($default, $keys);
							if(isset($keys[$current + 1])) $default = $keys[$current + 1];
							break;
						
					}
					$this->moveCursorTo();
					$this->renderBorderWithText($text, $options, $default);
				}
				$control = ($c=="[");
			}
			$this->resetColorCodes();
			usleep(500);
		}
	}

	function __construct() {
		$this->currentTerminalSize = implode(':',$this->getTerminalSize());
		$this->activateColorCodes();
		echo 'Wheee!';
		$this->clearScreen();
		echo 'You need to override this constructor.'.PHP_EOL;
		echo 'Set options using the following functions:'.PHP_EOL;
		echo '* setTextColor($color)'.PHP_EOL;
		echo '* setBackgroundColor($color)'.PHP_EOL;
		echo '* setSelectionColor($color)'.PHP_EOL;
		echo '* requestInput($text, $options, $default)'.PHP_EOL;
		$exit = function() { exit(); };
		$this->requestInput('Press enter to quit', array('OK' => $exit));
		$this->resetColorCodes();
		$this->clearScreen();
	}
}