<?php
// @author: C.A.D. BONDJE DOUE
// @file: VueSFCDynamicSlot.php
// @date: 20230524 10:02:27
namespace igk\js\Vue3\Compiler;


///<summary></summary>
/**
* to resolve dynamic slot data
* @package igk\js\Vue3\Compiler
*/
class VueSFCDynamicSlot{
    var $expression;
    var $content;

    public function __construct(string $expression, string $content)
    {
        $this->expression = $expression;
        $this->content = $content;
    }
}