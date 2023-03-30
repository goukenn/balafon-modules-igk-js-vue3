<?php
// @author: C.A.D. BONDJE DOUE
// @file: VueRefVariable.php
// @date: 20230126 14:19:13
namespace igk\js\Vue3;

use igk\js\common\JSExpression;
use igk\js\Vue3\Libraries\IRefVar;

///<summary></summary>
/**
* 
* @package igk\js\Vue3
*/
class VueRefVariable extends JSExpression{
    var $id;
    public function __construct($id){
        $this->id = $id;
    }

    public function render($options = null): ?string { 
        return null;//$this->getValue($options);
    }
    public function getValue(?object $options=null){
        return $this->id;
    }
    public function __toString()
    {  
        return $this->getValue();
    }
    public function __set($n, $g){
        igk_die("not allowed");
    } 
}