<?php

// @author: C.A.D. BONDJE DOUE
// @filename: VueAppResourceHandler.php
// @date: 20220823 15:17:18
// @desc: 
namespace igk\js\Vue3;
use IGK\System\Http\PageNotFoundException;
use IGK\System\Http\WebFileResponse;
use IGKException;

class VueAppResourceLoader{
    var $app_src;

    public function __construct(?string $app_src)
    {
        $this->app_src = $app_src;
    }
    /**
     * handle asset resource
     * @param mixed $fname 
     * @param mixed $params 
     * @return mixed 
     * @throws IGKException 
     */
    public function handle($fname, $params){
        return igk_view_handle_actions($fname, ["assets"=>function(){
            $app_src = $this->app_src;
            $path = implode("/", func_get_args());
            $file = $app_src."/".$path;
            if (!file_exists($file)){
                if (!is_file($file = $app_src."/assets/".$path)){
                    throw new PageNotFoundException();
                }
            }
            $response = new WebFileResponse($file); 
            $response->cache_output(5000);
            return $response; 
        }], $params);  
    }
}