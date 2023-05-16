<?php
// @author: C.A.D. BONDJE DOUE
// @file: ViteScriptRenderer.php
// @date: 20230426 08:31:15
namespace igk\js\Vue3\Vite\Html\Dom;

use IGK\Helper\ViewHelper;
use igk\js\Vue3\Libraries\i18n\Vuei18n;
use igk\js\Vue3\Libraries\VueRouter;
use igk\js\Vue3\Vite\ViteAppManagement;
use igk\js\Vue3\VueConstants;
use IGK\System\Html\HtmlRendererOptions;
use IGK\System\IO\StringBuilder;
use IGKException;

///<summary></summary>
/**
 * 
 * @package igk\js\Vue3\Vite\Html\Dom
 */
class ViteScriptRenderer extends ViteNodeBase
{
    protected $tagname = 'script';
    private $m_host;
    private $m_script;
    public function getCanAddChilds()
    {
        return false;
    }
    protected function initialize()
    {
        parent::initialize();
        $this['type'] = 'text/javascript';
    }
    public function __construct(ViteApplicationNode $node)
    {
        $this->m_host = $node;
        parent::__construct();
    }
    /**
     * get script rendering content
     * @return void 
     */
    public function getContent($options = null)
    {
        if (is_null($this->m_script)) {
            $this->m_script = $this->GenerateScriptContent($options);
        }
        return $this->m_script;
    }
    /**
     * build render inline script 
     * @param mixed $options 
     * @return string 
     * @throws IGKException 
     */
    public function GenerateScriptContent($options=null): string
    {
        $id = $this->m_host['id'];
        $ctrl = $this->m_host->getController();
        $doc = igk_getv($options, 'Document') ?? ViewHelper::CurrentDocument();
        $v_appOptions = $this->m_host->getAppOptions();
        $ns = $v_appOptions->entryNamespace;
        $v_def = '';
        $sb = new StringBuilder();
        $liboption = [];
        if ($options) {
            if ($options instanceof HtmlRendererOptions) {
                $options->setRef(VueConstants::LIB_OPTIONS, $liboption);
            } else {
                $options->{VueConstants::LIB_OPTIONS} = &$liboption;
            }
        }


        // + | init extra library
        $v_slib = new StringBuilder();
        $v_uses = [];
        $v_def = [];
        foreach ($v_appOptions->libraries as $k=>$lib) {
            if (is_string($lib)) {
                $v_def[] = $lib;
                continue;
            } 
            $s = $lib->render($options);
            $v_slib->append($s);
            $v_uses[] = $lib->varName;
        }
        $v_header_sb = new StringBuilder;

        if ($liboption) {
            foreach ($liboption as $k => $v) {
                $v_header_sb->appendLine('const { ' . implode(", ", $v->to_array()) . ' } = ' . $k . ';');
            }
        }
        $v_header_sb->append('const _NS_ = '.$ns.';');

        // + | init core constant library 
        $sb->append("const { createApp");
        if (!empty($v_def)) {
            $sb->append(',' . implode(',',  $v_def));
        }
        $sb->appendLine(" } = Vue;");

        $sb->append($v_header_sb);
        $sb->append($v_slib);
        // + | bind inline definitions

        // + | bind definition 

        $v_setup = '{mounted(){ console.log("mounted -- init core "); }}';

        $sb->append('_NS_.uses = {' . implode(',', $v_uses) . '};');
       // $sb->append('const _APP_ = igk.js.vue3.vite;');
        // + | init vite application 
        // $sb->append(implode("", [
        //     'if (_APP_.appComponent){',    
        //         // 'const _an = _APP_.initApp(createApp({mounted(){console.log("mounted .....>"); }, ..._APP_.appComponent}), _NS_)',
        //         // sprintf('.mount(_NS_.target || "#%s");', $id),
        //         //'console.log("finish");',
        //     '} else {'
        // ]));
        // $sb->append("_APP_.initApp(createApp(");
        // $sb->append($v_setup);
        // $sb->append("), _NS_)");
        // $sb->append(sprintf('.mount(_NS_.target || "#%s");', $id));   

        // $sb->append(implode("", [            
        //     '} '
        // ]));
        return 'igk.ready(()=>{ ' . $sb . ' });';
    }
}
