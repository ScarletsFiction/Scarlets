<?php
namespace Scarlets\Console;
use \Scarlets\Console;

class ProgressBar{
	private $lists;
	private $range = 100;
	private $firstDraw = false;

	// [style, character]
	private $style;
	private $character_ = ['©', '®', 'º'];
	private $character;

	public $endOfLine = "\n";
	public $barLength = 26;

	public function __construct(){
		$this->style = ["<cyan>%s</cyan>", '%s', '%s'];
		$this->character = ["●", "»", " "];
	}

	public function setCompletedStyle($style, $character=false){
		if($character !== false){
			$this->character[0] = &$character;
			$this->style[0] = $style ?: '%s';
		}
		else{
			$this->character[0] = $style;
			$this->style[0] = '%s';
		}
	}

	public function setProgressStyle($style, $character=false){
		if($character !== false){
			$this->character[1] = &$character;
			$this->style[1] = $style ?: '%s';
		}
		else{
			$this->character[1] = $style;
			$this->style[1] = '%s';
		}
	}

	public function setWaitingStyle($style, $character=false){
		if($character !== false){
			$this->character[2] = &$character;
			$this->style[2] = $style ?: '%s';
		}
		else{
			$this->character[2] = $style;
			$this->style[2] = '%s';
		}
	}

	public function add($name, $maxValue, $currentValue = 0){
		// [name, mavValue, currentValue, processedBar]
		$this->lists[] = [Console::chalk($name, 'green', true), &$maxValue, &$currentValue, '', ''];
		return count($this->lists)-1;
	}

	public function update($index, $value, $barText = false, $endText = false){
		$lists = &$this->lists;

		$current = &$lists[$index];
		$current[2] = &$value;

		// Generate bar
		$render = '';
		$n = &$this->barLength;
		$completed = round(($value/$current[1])*$n);

		for ($i=0; $i < $n; $i++) { 
			if($i < $completed)
				$render .= '©';
			elseif($i > $completed)
				$render .= 'º';
			else
				$render .= '®';
		}

		if($barText !== false){
			$len = strlen($barText);
			if($len <= $n){
				$loc = round($n/2) - round($len/2);
				$render = mb_substr($render, 0, $loc)."($barText)".mb_substr($render, $loc+$len+2);
			}
		}

		$style = &$this->style;

		$render_ = Console::style(sprintf($style[0], mb_substr($render, 0, $completed))); // Completed bar
		if($completed !== $n)
			$render_ .= Console::style(sprintf($style[1], mb_substr($render, $completed, 1))); // On progress bar
		$render_ .= Console::style(sprintf($style[2], mb_substr($render, $completed+1))); // Waiting bar

		$current[3] = str_replace($this->character_, $this->character, $render_);
		$current[4] = &$endText;

		// Check first draw
		if($this->firstDraw === false){
			echo "\n";
			Console::saveCursor();
			$this->firstDraw = true;
		}

		// Create progress from the beginning
		Console::loadCursor();
		foreach ($lists as &$list) {
			echo "$list[0] \t[$list[3]] $list[4]      $this->endOfLine";
		}
	}
}