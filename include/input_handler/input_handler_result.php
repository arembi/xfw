<?php

namespace Arembi\Xfw\Core;

class Input_Handler_Result {
    
    private $status;
    private $message;
    private $data;


    public function __construct(
        int $status = Input_Handler::DATA_NOT_PROCESSED,
        string $message = '',
        $data = null
    )
    {
        $this->status = $status;
        $this->message = $message;
        $this->data = $data;
    }

    
    public function status(?int $status = null)
    {
        if ($status === null) {
            return $this->status;
        }

        $this->status = $status;

        return $this;
    }


    public function message(?string $message = null)
    {
        if ($message === null) {
            return $this->message;
        }

        $this->message = $message;

        return $this;
    }


    public function data($data = null)
    {
        if ($data === null) {
            return $this->data;
        }

        $this->data = $data;

        return $this;
    }
}