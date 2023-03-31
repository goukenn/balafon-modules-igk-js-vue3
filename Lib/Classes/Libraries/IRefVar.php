<?php
// @author: C.A.D. BONDJE DOUE
// @file: IRefVar.php
// @date: 20230126 16:10:25
namespace igk\js\Vue3\Libraries;


///<summary></summary>
/**
* 
* @package igk\js\Vue3\Libraries
*/
interface IRefVar{
    /**
     * 
     * @param mixed $options 
     * @return string 
     */
    function render($options=null):?string;    
}