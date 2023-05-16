<?php
// @author: C.A.D. BONDJE DOUE
// @file: VueSFCRenderNodeVisitorOptions.php
// @date: 20230331 01:30:15
namespace igk\js\Vue3\Compiler;


///<summary></summary>
/**
* 
* @package igk\js\Vue3\Compiler
*/
class VueSFCRenderNodeVisitorOptions{
    var $libraries = [];
    var $defineArgs = [];
    var $defineGlobal = [];

    var $component_prefix = '_vue_';
    var $global_prefix = '$__';
    var $components = [];

    /**
     * test mode
     * @var ?bool
     */
    var $test; 

    /**
     * @var ?inloop
     */
    var $inLoop; // in loop flags

    /**
     * context vars 
     * @var mixed
     */
    var $contextVars = [];
}