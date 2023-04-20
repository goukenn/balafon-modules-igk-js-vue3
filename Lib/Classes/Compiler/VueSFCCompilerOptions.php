<?php
// @author: C.A.D. BONDJE DOUE
// @file: VueSFCCompilerOptions.php
// @date: 20230413 17:45:38
namespace igk\js\Vue3\Compiler;


///<summary></summary>
/**
* 
* @package igk\js\Vue3\Compiler
*/
class VueSFCCompilerOptions{
    /**
     * export 
     * @var ?bool
     */
    var $export;

    /**
     * resource resolver
     * @var ?callable
     */
    var $resourceResolver;

    /**
     * for testing mode
     * @var ?bool
     */
    var $test;
}