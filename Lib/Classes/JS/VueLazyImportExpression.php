<?php

// @author: C.A.D. BONDJE DOUE
// @filename: VueLazyLoadExpression.php
// @date: 20220813 14:36:19
// @desc: 

namespace igk\js\Vue3\JS;

/**
 * load js expression
 * @package igk\js\Vue3\JS
 */
class VueLazyImportExpression{
    /**
     * lazy import data
     * @var ?string 
     */
    var $data;

    /**
     * store options name|the name of the import 
     * */ 
    var $options;

    public function inlineData(){
        $src = $this->data;
        $src = "[\"".implode("\",\"", array_filter(array_map(function($n){
            return trim(str_replace("/", "\/",addslashes($n)));
        }, explode("\n", $src))))."\"].join(\"\\n\")";

        return $src; 
    }
}
