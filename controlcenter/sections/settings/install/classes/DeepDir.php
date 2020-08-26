<?php
require_once (dirname(__FILE__)."/FILO.php");

class DeepDir
{
	var $dir;
	var $files;
	function __construct()
	{
		$this->dir = '';
		$this->files = array();
		$this->dirFILO = new FILO();
	}
	function setDir($dir)
	{
		$this->dir = $dir;
		$this->files = array();
		$this->dirFILO->zero();
		$this->dirFILO->push($this->dir);
	}
	function load()
	{
		while (false != ($this->curDir = $this->dirFILO->pop()))
		{
			$this->loadFromCurDir();
		}
	}
	function loadFromCurDir()
	{
		if ( false != ($handle = @ opendir($this->curDir)) )
		{
			while (false != ($file = readdir($handle)))
			{
				if ( $file == "." || $file == ".." )
					continue;
				$filePath = $this->curDir . '/' . $file;
				$fileType = filetype($filePath);
				if ( $fileType == 'dir' )
				{
					$this->dirFILO->push($filePath);
					continue;
				}
				$this->files[] = $filePath;
			}
			closedir($handle);
		}
		else
		{
			echo 'error open dir "' . $this->curDir . '"';
		}
	}
} // end class

?>
