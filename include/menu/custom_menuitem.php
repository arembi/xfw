<?php

namespace Arembi\Xfw\Inc;

class CustomMenuitem {
    
    private $content;


    public function __construct(?string $content = null)
    {
        $this->content = $content;
    }


    public function __toString()
    {
        return $this->content ?? '';
    }

    
    public function content(?string $data = null)
    {
        if ($data === null) {
            return $this->content;
        } else {
            $this->content = $data;
        }
    }
}