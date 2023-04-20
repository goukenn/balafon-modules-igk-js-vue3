<?php

namespace igk\js\Vue3;

igk_require_module(\igk\js\common::class);

use igk\js\common\JSExpression;
use IGK\System\Console\Logger;
use IGK\System\Exceptions\ArgumentTypeNotValidException;
use IGK\System\Html\HtmlRendererOptions;
use IGK\System\IO\StringBuilder;
use IGKException;
use ReflectionException;
use stdClass;

/**
 * get the script renderer
 * @package igk\js\Vue3
 */
class ViewScriptRenderer
{
    private $m_data;
    private $m_id;
    private $m_name;
    private $m_libraries;
    private $m_components;

    /**
     * author definition script
     * @var mixed
     */
    var $def;

    /**
     * shared used 
     * @var ?array
     */
    var $sharedUses;
    /**
     * construct script renderer
     * @param mixed $id 
     * @param mixed $data 
     * @param null|string $name 
     * @param null|string $libraries 
     * @param null|string $components  
     * @return void 
     */
    public function __construct(
        $id,
        $data,
        ?string $name = null,
        ?array $libraries = [],
        ?array $components = []
    ) {
        $this->m_id = $id;
        $this->m_data = $data;
        $this->m_name = $name;
        $this->m_libraries = $libraries;
        $this->m_components = $components;
    }
    private function _libraries_import(array $data)
    {
        $std = new stdClass();
        $std->vueLib = [];
        foreach ($data as $k => $v) {
            if (is_numeric($k) || (strtolower($k) == "vue")) {
                // global vue 
                if (is_array($v)) {
                    array_push($std->vueLib, ...$v);
                } else {
                    $std->vueLib[] = $v;
                }
            } else {
                $std->$k = $v;
            }
        }
        return $std;
    }
    /**
     * render script content
     * @param mixed $options 
     * @return null|string 
     * @throws IGKException 
     * @throws ArgumentTypeNotValidException 
     * @throws ReflectionException 
     */
    public function render($options = null): ?string
    {
        $v_header_sb = new StringBuilder;
        $sb = new StringBuilder();
        $v_header_sb->lf = '';
        // $sb->lf = !$options || $options->Indent ? "\n" : "";
        $sb->lf = '';
        $js_options = (object)["objectNotation" => 1];
        $use = "";
        $_vdata = $this->_libraries_import($this->m_libraries ?? []);
        if ($tuses = $_vdata->vueLib) {
            if ($s = implode(", ", array_unique($tuses)))
                $use = ", " . $s;
        }

        unset($_vdata->vueLib);
        $chain = new StringBuilder;
        $v_header_sb->appendLine("\nconst { createApp" . $use . " } = Vue;");
        $v_uses = [];
        $liboption = [];
        if ($options) {
            if ($options instanceof HtmlRendererOptions) {
                $options->setRef(VueConstants::LIB_OPTIONS, $liboption);
            } else {
                $options->{VueConstants::LIB_OPTIONS} = &$liboption;
            }
        }

        // + | import library rendering
        foreach ($_vdata as $k => $v) {
            if (!is_null($s = $v->render($options))) {
                $chain->appendLine(rtrim($s));
            }
            if ($g = $v->useLibrary($options)) {
                if (is_array($g) && count($g) >= 2) {
                    list($key, $op) = $g;
                    $v_uses[$key] = $op;
                } else {
                    $v_uses[$g] = null;
                }
            }
        }
        if ($liboption) {
            foreach ($liboption as $k => $v) {
                $v_header_sb->appendLine('const { ' . implode(", ", $v->to_array()) . ' } = ' . $k . ';');
            }
        }


        if (!is_null($this->def)) {
            $v_header_sb->appendLine("\n" . trim($this->def) . "\n");
        }
        $v_header_sb->appendLine($chain . "");
        $app_name = $this->m_name;
        if ($app_name) {
            $sb->append("const {$this->m_name} = ");
        }
        // $sb->append("createApp(");
        $sb->appendLine('igk.js.vue3.mainApp(createApp');
        $l = JSExpression::Stringify($this->m_data, $js_options);
        if (!empty($l))
            $l =','.$l;
        $sb->append($l);
        $components = $this->m_components ?? [];
        foreach ($components as $k => $c) {
            $sc = JSExpression::Stringify($c, $js_options);
            if (!empty($sc)) {
                $sb->append(").component(");
                if (is_numeric($k)) {
                    $k = igk_getv($c, 'id') ?? igk_die("component identifier not valid value");
                }
                $sb->appendLine("'{$k}', {$sc}");
            } else {
                Logger::info('view renderer string ify return an empty value');
            }
        }
        $v_sharedUsed = [];
        foreach ($v_uses as $k => $c) {
            $sc = JSExpression::Stringify($c, $js_options);
            if (!empty($sc)) {
                $sc = ", " . $sc;
            }
            $sb->append(").use({$k}{$sc}");
            $v_sharedUsed[] = $k;
        }
        if ($this->sharedUses) {
            $inf = '';
            $ch = '';
            foreach ($this->sharedUses as $c) {
                $k = $c->getVarName();
                if (in_array($k, $v_sharedUsed)) {
                    $inf .= $ch . '' . $k;
                    $ch = ',';
                }
            }
            if (!empty($inf)) {
                $v_header_sb->append(sprintf('igk.js.vue3.shared({%s});', $inf));
            }
        }

        if ($app_name) {
            $sb->appendLine(");");
            // + | --------------------------------------------------------------------
            // + | do something
            // + |
            $sb->append("igk.js.vue3.vue_app = $app_name;");
            $sb->append($app_name);
        } else {
            $sb->append(")");
        }
        // + | ------------------------------------------------------------------------
        // + | mount application 
        // + | 
        $sb->appendLine(".mount('#" . $this->m_id . "');");
        return $v_header_sb . $sb . '';
    }
}
