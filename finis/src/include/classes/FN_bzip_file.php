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
class FN_bzip_file extends FN_tar_file
{
    function __construct($name)
    {
        $this->tar_file($name);
        $this->options['type'] = "bzip";
    }
    function create_bzip()
    {
        if ($this->options['inmemory'] == 0)
        {
            $pwd = getcwd();
            chdir($this->options['basedir']);
            if (false != ($fp = bzopen($this->options['name'], "wb")))
            {
                fseek($this->FN_archive, 0);
                while (false != ($temp = fread($this->FN_archive, 1048576))) bzwrite($fp, $temp);
                bzclose($fp);
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
            $this->FN_archive = bzcompress($this->FN_archive, $this->options['level']);
        return 1;
    }
    function open_archive()
    {
        return @ bzopen($this->options['name'], "rb");
    }
}
?>
