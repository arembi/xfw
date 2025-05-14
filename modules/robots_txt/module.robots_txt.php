<?php

namespace Arembi\Xfw\Module;

use Arembi\Xfw\Core\App;
use Arembi\Xfw\Core\ModuleCore;
use Arembi\Xfw\Core\Settings;

class Robots_TxtBase extends ModuleCore {
    protected static $hasModel = false;

    protected $dictionary;

    protected function main()
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