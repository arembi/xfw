<?php

namespace Arembi\Xfw\Inc;

class CustomMenuitem {
    
    private $content;


    public function __construct(string $content = '')
    {
        $this->content = $content;
    }


    public function __toString()
    {
        return $this->content;
    }

    
    public function content(?string $content = null): string|CustomMenuitem
    {
        if ($content === null) {
            return $this->content;
        }
        $this->content = $content;
        return $this;
    }
}