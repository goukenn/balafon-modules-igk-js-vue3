<?php
namespace igk\js\Vue3\Vite\Helper;

class Utility{
    static function ViteRouterPreloadScript(){
        return file_get_contents(igk_current_module()->getDataDir(). '/vite/js/routed-preload.js');
    }
}