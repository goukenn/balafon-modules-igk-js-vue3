<?php
// @author: C.A.D. BONDJE DOUE
// @file: VueInlineProjectConverter.php
// @date: 20230308 03:32:09
namespace igk\js\Vue3\System\Converter;

use IGK\Controllers\BaseController;
use IGK\Helper\IO;
use igk\js\Vue3\Compiler\VueSFCCompiler;
use igk\js\Vue3\Controllers\VueInlineAssetResource;
use igk\js\Vue3\VueConstants;
use IGK\System\Console\Logger;
use IGK\System\Html\Css\CssComment;
use IGK\System\Html\Css\CssParser;
use IGK\System\Html\Css\CssStyle;
use IGK\System\Html\HtmlContext;
use IGK\System\Html\HtmlNodeBuilder;
use IGK\System\Html\HtmlReader;
use IGK\System\IO\File\PHPScriptBuilder;
use IGK\System\IO\Path;
use IGK\System\IO\StringBuilder;

///<summary></summary>
/**
* 
* @package igk\js\Vue3\System\Converter
*/
class VueInlineProjectConverter{
    var $outdir;
    var $inputdir;
    public function store($path, $content){
        $path = Path::Combine($this->outdir, $path);
        igk_io_w2file($path, $content);
    }
    public function copy($file, $path){
        $path = Path::Combine($this->outdir, $path);
        IO::CreateDir(dirname($path));
        copy($file, $path);
    }
    public function convert(string $file, ?BaseController $baseController = null){
        $ext = igk_io_path_ext($file);
        $path = substr($file, strlen($this->inputdir)+1);
        if(method_exists($this, $fc = "convert_".$ext)){
            return $this->$fc($file, $path, $baseController);
        } else {
            $this->copy($file, $path);
        }
        return false;
    }
    public function convert_css($file, $path, BaseController $baseController = null){
        $this->store($path, file_get_contents($file));
    }
    public function convert_vue($file, $path, BaseController $baseController = null){
        $data = HtmlReader::LoadFile($file, HtmlContext::XML);
        $template = igk_getv($data->getElementsByTagName("template"), 0);
        $scripts =  $data->getElementsByTagName("script");
        $styles =  $data->getElementsByTagName("style");
        $base = igk_io_basenamewithoutext($file);
        $dirname = dirname($path);
        if ($template){

            foreach($template->getElementsByTagName('img') as $img){
                $src = $img['src'];
                if (igk_str_startWith($src, '@')){
                    $src = ltrim(substr($src,1),'@');
                    $img['src'] = new VueInlineAssetResource($src, $baseController);
                }
            }

            $file = Path::Combine($dirname, $base.'.phtml');
            $builder = new PHPScriptBuilder;
            $sb = new StringBuilder;
            $sb->appendLine(HtmlNodeBuilder::Generate($template));
            $builder->type('function')->defs($sb);
            // $this->store(Path::Combine($dirname, $base.'.phtml'), $template->getInnerHtml());
            $this->store($file, $builder->render());
            // Logger::info(__FILE__.":".__LINE__ .' '. $file);
            // exit;
        }
        if ($scripts){
            $sb = new StringBuilder;
            $inline = new StringBuilder;
            foreach($scripts as $sc){
                $is_setup = $sc['setup'];
                $sc = $sc->getInnerHtml();
                if ($is_setup){
                    $sb->appendLine($sc);
                } else {
                    $inline->appendLine($sc);
                }
            }
            $sb = $sb.'';
            $inline = $inline.'';
            if (!empty($sb) && !empty($inline)){
                if (preg_match("/(\{|,)\s*setup\s*(\(|:)/", $inline)){
                    igk_die("setup is define in inline script: ".$file);
                }       
                $sb = VueSFCCompiler::GetLitteralSetupScript($sb, $inline);
            }else if (empty($sb."")){
                // use option api
                $sb = sprintf(implode("", [
                '(function(){ return %s;})'
                ]),$inline);                    
            } else if (!empty($sb."")) {
                $sb = VueSFCCompiler::GetLitteralSetupScript($sb, null);
            } else {
                return;
            }
            if (!empty($sb)){
                $this->store(Path::Combine($dirname, $base.VueConstants::VUE_JS_SETUP_EXT),$sb.'');
            }
        }
        if ($styles){
            $sb = new StringBuilder;

            $out = [];
            foreach($styles as $sc){
                $type = $sc['type'] ?? 'css';
                switch($type){
                    case 'scss':
                        igk_ilog('not implement');
                        break;
                    case 'css':
                    default:
                    $css = CssParser::Parse($sc->getInnerHtml());
                    if ($css){
                       $out_css = array_merge($out, $css->to_array());
                    }
                    break;
                }
            }

            foreach($out_css as $k=>$v){
                if (is_array($v)){
                    $v = igk_array_key_map_implode($v);
                }
                if ($v instanceof CssComment)
                    continue;
                    
                $sb->appendLine('$def[\''.$k.'\'] = "'.$v.'";');
            }

            // $sb->appendLine(igk_array_key_map_implode($out_css));
            $builder = new PHPScriptBuilder;
            $builder->type('function')->defs($sb.'');
            
            $this->store(Path::Combine($dirname, $base.'.vue3-style.pcss'),$builder->render());
        }
    }
    public function convert_js($file, $path){
        $this->copy($file, $path);
    }
    public function convert_png($file, $path){
        $this->copy($file, $path);
    }
    public function convert_jpg($file, $path){
        $this->copy($file, $path);
    }
    public function convert_jpeg($file, $path){
        $this->copy($file, $path);
    }
}