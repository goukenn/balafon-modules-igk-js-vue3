<?php


namespace igk\js\Vue3\Libraries;

use Closure;

class VueLibrary extends VueLibraryBase{
    const DefineComponent = 'defineComponent';
    const DefineAsyncComponent = 'defineAsyncComponent';
    protected $m_module; 
    var $listener;

    public function __construct(string $name, ?string $module='Vue')
    {
        parent::__construct($name);
        $this->m_module = $module;
    }

    public function useLibrary($option = null): array {
        return [$this->m_name, $this->m_module];
    }

    public function render($option = null): ?string {
        if ($this->listener){
            $fc = Closure::fromCallable($this->listener)->bindTo($this);
            return $fc($option);
        }
        return "";
     }
}