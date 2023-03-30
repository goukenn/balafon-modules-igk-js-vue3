<?php
// @author: C.A.D. BONDJE DOUE
// @file: IRefLibrary.php
// @date: 20230126 16:11:08
namespace igk\js\Vue3\Libraries;


///<summary></summary>
/**
* 
* @package igk\js\Vue3\Libraries
*/
interface IRefLibrary extends IRefVar{
    /**
     * use library
     * @param mixed $option 
     * @return array 
     */
    function useLibrary($option=null):array;
    /**
     * 
     * @param mixed $options 
     * @return string 
     */
    function render($options=null):?string;
}