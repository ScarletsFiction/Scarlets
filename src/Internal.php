<?php
namespace Scarlets\Internal;

// For throwing an finish event on the middle of execution
class ExecutionFinish extends \Exception{
	public $data;
	public function __construct($data = false){
		$this->data = $data;
	}
}