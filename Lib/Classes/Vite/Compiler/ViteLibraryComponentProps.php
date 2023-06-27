<?php

// @author: C.A.D. BONDJE DOUE
// @filename: ViteLibraryComponentProps.php
// @date: 20230615 18:15:16
// @desc: properties definitions


namespace igk\js\Vue3\Vite\Compiler;

use igk\js\common\JSExpression;

class ViteLibraryComponentProps{
 
    private $m_def;

    public function litteral(){
        return JSExpression::Stringify((object)$this->m_def);
    }
    public function isEmpty(){
        return empty($this->m_def);
    }
    public function getDef(){
        return $this->m_def;
    }
    public function __invoke(?array $def)
    {
        $this->m_def = $def;
    }
}