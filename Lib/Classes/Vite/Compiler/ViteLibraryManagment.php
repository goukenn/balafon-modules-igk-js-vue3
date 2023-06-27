<?php

// @author: C.A.D. BONDJE DOUE
// @filename: ViteLibraryManagment.php
// @date: 20230614 12:38:26
// @desc: 
namespace igk\js\Vue3\Vite\Compiler;


class ViteLibraryManagment{
    private $m_imports = [];
    private $m_components = [];
    private $m_props;

    public function & getProps(){
        if (!$this->m_props){
            $this->m_props = new ViteLibraryComponentProps();
        }
        return $this->m_props;
    }

    /**
     * use components
     * @param string $component 
     * @return void 
     */
    public function use(string $component){
        $this->m_components[$component] = 1;
    }   
    /**
     * get list of components
     * @return int[]|string[] 
     */
    public function getComponents(){
        return array_keys($this->m_components);
    }
    /**
     * import library to use in vue
     * @param string $path 
     * @param null|string $name 
     * @return void 
     */
    public function import(string $path, ?string $name = null){
        $this->m_imports[$path] = $name ?? igk_io_basenamewithoutext($path);
    }
    /**
     * get imports library
     * @return array 
     */
    public function getLibs(){
        return $this->m_imports;
    }
}