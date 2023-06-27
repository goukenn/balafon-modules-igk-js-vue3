<?php
// @author: C.A.D. BONDJE DOUE
// @file: ConvertSVGToVueCommand.php
// @date: 20230429 19:45:38
namespace igk\js\Vue3\System\Console\Commands\Svg;

use IGK\Helper\StringUtility;
use igk\ios\SfSymbols\Helper;
use igk\js\Vue3\Compiler\VueSFCCompiler;
use igk\js\Vue3\System\Console\Commands\VueCommandBase;
use igk\js\Vue3\System\IO\VueSFCFile;
use IGK\System\Console\Logger;
use IGK\System\Html\Dom\HtmlDoctype;
use IGK\System\Html\Dom\HtmlProcessInstructionNode;
use IGK\System\Html\HtmlRendererOptions;
use IGK\System\Html\XML\XmlNode;
use IGK\System\IO\Path;
use IGK\System\IO\StringBuilder;
use IGK\System\SVG\Traits\SvgTreatTrait;

///<summary></summary>
/**
 * 
 * @package igk\js\Vue3\System\Console\Commands\Svg
 */
class ConvertSVGToVueCommand extends VueCommandBase
{
    use SvgTreatTrait;
    var $command = '--vue3:convert-svg-to-vue';
    var $desc = 'convert .svg to .vue file';
    var $options = [
        '--unlink' => "unlink source file",
        '--type:[js|vue]' => "type of conversion. default is vue"
    ];
    var $usage = "source_dir [target_dir] [options]";



    public function exec($command, string $folder = null, string $target = null)
    {
        $folder = $folder ?? getcwd();
        $files = igk_io_getfiles($folder, "/\.svg$/", false);
        $unlink = property_exists($command->options, '--unlink');
        $type = igk_getv($command->options, '--type', 'vue');
        $c = 0;
        $options = new HtmlRendererOptions;
        $options->filterListener = function ($g) {
            if ($g instanceof HtmlProcessInstructionNode) {
                return true;
            }
            if ($g instanceof HtmlDoctype) {
                return true;
            }
            if ($g->getTagName() == 'svg') {
                if (!$g['viewBox']) {
                    // + | if missing viewBox set viewBox to allow scale with css 
                    $w = $g['width'];
                    $h = $g['height'];
                    if ($w && $h) {
                        $g['viewBox'] = '0 0 ' . $w . ' ' . $h;
                    }
                }
            }
            return false;;
        };
        if ($files) {
            $l = '';
            $bdir = $folder;
            if (!is_null($target)) {
                $l = trim($target) . "/";
            }

            foreach ($files as $f) {
                $name = basename($f);
                $_n = igk_io_basenamewithoutext($name);
                $g = file_get_contents($f);
                $g = self::TreatSvg($g);

                $path  = substr(dirname($f), strlen($bdir)+1);
                $outfile = Path::Combine($l . $path ,  $_n);
                igk_is_debug() && Logger::info('convert ' . $f . ' => '.$outfile);
                switch ($type) {
                    case "js":
                        $outfile.='.js';
                        $tl = new VueSFCFile;
                        $m = $tl->template();
                        $m->load($g);
                        $rc = '';
                        if ($svg = igk_getv($m->getElementsByTagName('svg', true), 0)){
                            $svg->activate('v-pre');
                            $toptions = null;
                            $rc = VueSFCCompiler::ConvertToVueRenderMethod($svg, $toptions);                              
                            $rc = sprintf('export default {%s}', $rc);

                            $sb = new StringBuilder;
                            $sb->appendLine($rc);
                            $rc = $sb.'';
                        }
                        igk_io_w2file($outfile, $rc); 
                        break;
                    default:
                    $outfile.='.vue';
                        $n = new XmlNode('template');
                        $n->load($g);                   
                        igk_io_w2file($outfile, $n->render($options));
                        break;
                }
                $unlink && unlink($f);
                $c++;
            }
        }
        Logger::success("convert " . $c . " file" . (($c > 1) ? 's' : ''));
    }
}
