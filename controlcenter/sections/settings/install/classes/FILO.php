<?php
class FILO
{
	var $elements;
	function __construct()
	{
		$this->zero();
	}
	function push($elm)
	{
		array_push($this->elements,$elm);
	}
	function pop()
	{
		return array_pop($this->elements);
	}
	function zero()
	{
		$this->elements = array();
	}
}
?>
