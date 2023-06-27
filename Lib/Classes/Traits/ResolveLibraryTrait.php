<?php

namespace igk\js\Vue3\Traits;

use IGKException;

/**
 * inject method traits
 */
trait ResolveLibraryTrait{

    /**
     * 
     * @param mixed $d 
     * @return mixed 
     * @throws IGKException 
     */
    protected static function ResolvLibToDev($d){
        return igk_getv([
            'vue'=>'vue',
            'vue-router'=>'vue-router',
        ],strtolower($d)) ?? igk_die("not resolved : ".$d);
    }
}