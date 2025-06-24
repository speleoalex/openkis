<?php
/**
 * @package Finis
 * @author Alessandro Vernassa <speleoalex@gmail.com>
 * @copyright Copyright (c) 2011
 * @license http://opensource.org/licenses/gpl-license.php GNU General Public License
 */
/**
 * 
 */
class FN_gzip_file extends FN_tar_file
{
    function __construct($name)
    {
        $this->tar_file($name);
        $this->options['type'] = "gzip";
    }
    function create_gzip()
    {
        if ($this->options['inmemory'] == 0)
        {
            $pwd = getcwd();
            chdir($this->options['basedir']);
            if (false != ($fp = gzopen($this->options['name'], "wb{$this->options['level']}")))
            {
                fseek($this->FN_archive, 0);
                while (false != ($temp = fread($this->FN_archive, 1048576))) gzwrite($fp, $temp);
                gzclose($fp);
                chdir($pwd);
            }
            else
            {
                $this->error[] = "Could not open {$this->options['name']} for writing.";
                chdir($pwd);
                return 0;
            }
        }
        else
            $this->FN_archive = gzencode($this->FN_archive, $this->options['level']);
        return 1;
    }
    function open_archive()
    {
        return @ gzopen($this->options['name'], "rb");
    }
}
?>
