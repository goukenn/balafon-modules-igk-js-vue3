<?php


namespace igk\js\Vue3\Libraries;

use Closure;

/**
 * use to inject file content before app definition.
 * @package igk\js\Vue3\Libraries
 */
class VueFileLibrary extends VueLibraryBase{
    private $m_file;

    public function __construct(string $file)
    {
        parent::__construct($file);
        $this->m_file = $file;
    }

    public function useLibrary($option = null): array { 
        return [];
    }
    /**
     * render file contents
     * @param mixed $option 
     * @return null|string 
     */
    public function render($option=null):?string{
        return file_get_contents($this->m_file);
    }
}