<?php

namespace Arembi\Xfw\Module;

use Arembi\Xfw\Core\ModuleBase;
use Arembi\Xfw\Core\Settings;

class Robots_TxtBase extends ModuleBase {
    protected static $hasModel = false;

    protected $dictionary;

    protected function init()
    {   
        $this->dictionary = [
            'u'=>'user-agent',
            'a'=>'allow',
            'd'=>'disallow'
        ];

        Debug::suppress();
        header('Content-Type: text/plain');
        
        $source = Settings::get('robotsTxt');
        $contents = '';
        
        foreach ($source as $row) {
            if (!isset($this->dictionary[$row[0]])) {
                return false;
            }
            
            $contents .= ($contents ? PHP_EOL : '') . $this->dictionary[$row[0]] . ':' . $row[1]; 
        }

        $this->lv('contents', $contents);
    }
}