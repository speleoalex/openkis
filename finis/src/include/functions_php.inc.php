<?php
/**
 * 
 * @author Alessandro Vernassa <speleoalex@gmail.com>
 * @copyright Copyright (c) 2011
 * @license http://opensource.org/licenses/gpl-license.php GNU General Public License
 */
defined('_FNEXEC') or die('Restricted access');

if (!function_exists('ereg'))
{
    /**
     * Regular expression match
     * @link http://php.net/manual/en/function.ereg.php
     * @param pattern string <p>
     * Case sensitive regular expression.
     * </p>
     * @param string string <p>
     * The input string.
     * </p>
     * @param regs array[optional] <p>
     * If matches are found for parenthesized substrings of
     * pattern and the function is called with the
     * third argument regs, the matches will be stored
     * in the elements of the array regs. 
     * </p>
     * <p>
     * $regs[1] will contain the substring which starts at
     * the first left parenthesis; $regs[2] will contain
     * the substring starting at the second, and so on.
     * $regs[0] will contain a copy of the complete string
     * matched.
     * </p>
     * @return int the length of the matched string if a match for
     * pattern was found in string,
     * or false if no matches were found or an error occurred.
     * </p>
     * <p>
     * If the optional parameter regs was not passed or
     * the length of the matched string is 0, this function returns 1.
     */
    function ereg($find, $str, &$regs)
    {
        return FN_erg($find, $str, $regs);
    }
}

if (!function_exists('eregi'))
{
    /**
     * Case insensitive regular expression match
     * @link http://php.net/manual/en/function.eregi.php
     * @param pattern string <p>
     * Case insensitive regular expression.
     * </p>
     * @param string string <p>
     * The input string.
     * </p>
     * @param regs array[optional] <p>
     * If matches are found for parenthesized substrings of
     * pattern and the function is called with the
     * third argument regs, the matches will be stored
     * in the elements of the array regs. 
     * </p>
     * <p>
     * $regs[1] will contain the substring which starts at the first left
     * parenthesis; $regs[2] will contain the substring starting at the
     * second, and so on. $regs[0] will contain a copy of the complete string
     * matched.
     * </p>
     * @return int the length of the matched string if a match for
     * pattern was found in string,
     * or false if no matches were found or an error occurred.
     * </p>
     * <p>
     * If the optional parameter regs was not passed or
     * the length of the matched string is 0, this function returns 1.
     */
    function eregi($find, $str, &$regs)
    {
        return FN_ergi($find, $str, $regs);
    }
}

if (!function_exists('ereg_replace'))
{
    /**
     * Replace regular expression
     * @link http://php.net/manual/en/function.ereg-replace.php
     * @param pattern string <p>
     * A POSIX extended regular expression.
     * </p>
     * @param replacement string <p>
     * If pattern contains parenthesized substrings,
     * replacement may contain substrings of the form
     * \\digit, which will be
     * replaced by the text matching the digit'th parenthesized substring; 
     * \\0 will produce the entire contents of string.
     * Up to nine substrings may be used. Parentheses may be nested, in which
     * case they are counted by the opening parenthesis.
     * </p>
     * @param string string <p>
     * The input string.
     * </p>
     * @return string The modified string is returned. If no matches are found in 
     * string, then it will be returned unchanged.
     */
    function ereg_replace($pattern, $replacement, $string)
    {
        return FN_erg_replace($pattern, $replacement, $string);
    }
}

if (!function_exists('eregi_replace'))
{
    /**
     * Replace regular expression case insensitive
     * @link http://php.net/manual/en/function.eregi-replace.php
     * @param pattern string <p>
     * A POSIX extended regular expression.
     * </p>
     * @param replacement string <p>
     * If pattern contains parenthesized substrings,
     * replacement may contain substrings of the form
     * \\digit, which will be
     * replaced by the text matching the digit'th parenthesized substring; 
     * \\0 will produce the entire contents of string.
     * Up to nine substrings may be used. Parentheses may be nested, in which
     * case they are counted by the opening parenthesis.
     * </p>
     * @param string string <p>
     * The input string.
     * </p>
     * @return string The modified string is returned. If no matches are found in 
     * string, then it will be returned unchanged.
     */
    function eregi_replace($pattern, $replacement, $string)
    {
        return FN_ergi_replace($pattern, $replacement, $string);
    }
}
/**
 * Regular expression match
 * @param pattern string <p>
 * Case sensitive regular expression.
 * </p>
 * @param string string <p>
 * The input string.
 * </p>
 * @param regs array[optional] <p>
 * If matches are found for parenthesized substrings of
 * pattern and the function is called with the
 * third argument regs, the matches will be stored
 * in the elements of the array regs. 
 * </p>
 * <p>
 * $regs[1] will contain the substring which starts at
 * the first left parenthesis; $regs[2] will contain
 * the substring starting at the second, and so on.
 * $regs[0] will contain a copy of the complete string
 * matched.
 * </p>
 * @return int the length of the matched string if a match for
 * pattern was found in string,
 * or false if no matches were found or an error occurred.
 * </p>
 * <p>
 * If the optional parameter regs was not passed or
 * the length of the matched string is 0, this function returns 1.
 */
function FN_erg($find, $str, $regs = null)
{
    return preg_match("/" . str_replace('/', '\\/', $find) . "/s", $str, $regs);
}
/**
 * Case insensitive regular expression match
 * @param pattern string <p>
 * Case insensitive regular expression.
 * </p>
 * @param string string <p>
 * The input string.
 * </p>
 * @param regs array[optional] <p>
 * If matches are found for parenthesized substrings of
 * pattern and the function is called with the
 * third argument regs, the matches will be stored
 * in the elements of the array regs. 
 * </p>
 * <p>
 * $regs[1] will contain the substring which starts at the first left
 * parenthesis; $regs[2] will contain the substring starting at the
 * second, and so on. $regs[0] will contain a copy of the complete string
 * matched.
 * </p>
 * @return int the length of the matched string if a match for
 * pattern was found in string,
 * or false if no matches were found or an error occurred.
 * </p>
 * <p>
 * If the optional parameter regs was not passed or
 * the length of the matched string is 0, this function returns 1.
 */
function FN_ergi($find, $str, $regs = null)
{
    return (preg_match("/" . str_replace('/', '\\/', $find) . "/si", $str, $regs));
}
/**
 * Replace regular expression
 * @link http://php.net/manual/en/function.ereg-replace.php
 * @param pattern string <p>
 * A POSIX extended regular expression.
 * </p>
 * @param replacement string <p>
 * If pattern contains parenthesized substrings,
 * replacement may contain substrings of the form
 * \\digit, which will be
 * replaced by the text matching the digit'th parenthesized substring; 
 * \\0 will produce the entire contents of string.
 * Up to nine substrings may be used. Parentheses may be nested, in which
 * case they are counted by the opening parenthesis.
 * </p>
 * @param string string <p>
 * The input string.
 * </p>
 * @return string The modified string is returned. If no matches are found in 
 * string, then it will be returned unchanged.
 */
function FN_erg_replace($pattern, $replacement, $string)
{
    return preg_replace("/" . str_replace('/', '\\/', $pattern) . "/s", $replacement, $string);
}
/**
 * Case insensitive regular expression match
 * @param pattern string <p>
 * Case insensitive regular expression.
 * </p>
 * @param string string <p>
 * The input string.
 * </p>
 * @param regs array[optional] <p>
 * If matches are found for parenthesized substrings of
 * pattern and the function is called with the
 * third argument regs, the matches will be stored
 * in the elements of the array regs. 
 * </p>
 * <p>
 * $regs[1] will contain the substring which starts at the first left
 * parenthesis; $regs[2] will contain the substring starting at the
 * second, and so on. $regs[0] will contain a copy of the complete string
 * matched.
 * </p>
 * @return int the length of the matched string if a match for
 * pattern was found in string,
 * or false if no matches were found or an error occurred.
 * </p>
 * <p>
 * If the optional parameter regs was not passed or
 * the length of the matched string is 0, this function returns 1.
 * </p>
 */
function FN_ergi_replace($pattern, $replacement, $string)
{
    return preg_replace("/" . str_replace('/', '\\/', $pattern) . "/si", $replacement, $string);
}
/**
 *
 * @param $a
 * @param $b
 */
function FN_NatSort_callback($a, $b)
{
    $a = strtolower($a);
    $b = strtolower($b);
    if (fn_erg("^[0-9]", $a) && fn_erg("^[0-9]", $b))
    {
        $aa = explode("_", $a);
        $bb = explode("_", $b);
        $aa = $aa[0];
        $bb = $bb[0];
        if (intval($aa) == intval($bb))
        {
            return strnatcmp($a, $b);
        }
        return (intval($aa) < intval($bb)) ? -1 : 1;
    }
    return strnatcmp($a, $b);
}
/**
 *
 * @param $a
 * @param $b
 */
function FN_NatSortSensitive_callback($a, $b)
{
    if (fn_erg("^[0-9]", $a) && fn_erg("^[0-9]", $b))
    {
        $aa = explode("_", $a);
        $bb = explode("_", $b);
        $aa = $aa[0];
        $bb = $bb[0];
        if (intval($aa) == intval($bb))
        {
            return strnatcmp($a, $b);
        }
        return (intval($aa) < intval($bb)) ? -1 : 1;
    }
    return strnatcmp($a, $b);
}
/**
 *
 * @param string $array
 */
function FN_NatSort(&$array, $case_sensitive = false)
{
    if (is_array($array))
    {
        if ($case_sensitive)
        {
            usort($array, "FN_NatSortSensitive_callback");
        }
        else
        {
            usort($array, "FN_NatSort_callback");
        }
    }
}
if (!function_exists("iconv"))
{
    function iconv($charsetFrom, $charsetTo, $str)
    {
        $str_ret = @htmlentities($str, ENT_QUOTES, $charsetFrom);
        $str_ret = @html_entity_decode($str, ENT_QUOTES, $charsetTo);
        if ($str_ret != "")
            return $str_ret;
        return $str;
    }
}
/**
 *
 * @param array $array
 * @param string $order
 * @return array 
 */
function FN_ArraySortByKey($array, $order)
{
    return xmetadb_array_natsort_by_key($array, $order);
}
/**
 * 
 * @param type $assoc
 * @param type $inglue
 * @return boolean
 */
function FN_ImplodeWithKey($array, $inglue = ',')
{
    if(is_array($array) && count($array)>0)
    {
		ksort($array);
        foreach ($array as $tk => $tv) 
        {
            $return[] = $tk . $inglue . $tv;
        }
        return implode($inglue, $return);
    }
    return false;
}
?>