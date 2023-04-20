<?php
// @author: C.A.D. BONDJE DOUE
// @filename: VueInitializeLibrary.php
// @date: 20230418 17:01:51
// @desc: 

// + | --------------------------------------------------------------------
// + | 
// + |

namespace igk\js\Vue3\Libraries;

use IGK\System\IO\StringBuilder;

/**
 * initialize application helper library
 * @package 
 */
class VueInitializeLibrary extends VueLibraryBase
{
    private $m_options_name;  
    private $m_component_prefix;  
    public function __construct($options_name, $prefix = '/src/Components')
    {
        $this->m_options_name = $options_name;
        $this->m_component_prefix = $prefix;
        parent::__construct('__init__::');
    }
    public function useLibrary($option = null): array
    {
        return [];
    }

    public function render($option = null): ?string
    {
        $sb = new StringBuilder;
        $sb->append('const AppComponent = {};');
        $sb->append($this->_getAssetsLibrary());
        $n = $this->m_options_name ;
        $sb->append("igk.js.vue3.loadAsyncJsComponent(AppComponent, {$n}.components, getAssets(), defineAsyncComponent");
        if ($this->m_component_prefix)
            $sb->append(", '".$this->m_component_prefix."'");
        $sb->append(");");
        return $sb . '';
    }

    private function _getAssetsLibrary(): string
    {
        return igk_str_format(implode("\n", [
            'function getAssets(){',
            ' return igk.js.vue3.initAsset({0});',
            '}',            
        ]), $this->m_options_name);
    }
}