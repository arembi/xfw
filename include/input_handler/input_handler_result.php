<?php

namespace Arembi\Xfw\Core;

class Input_Handler_Result {
    
    private $handlerModule;
    private $handlerMethod;
    private $status;
    private $message;
    private $data;


    public function __construct(
        string $handlerModule = '',
        string $handlerMethod = '',
        int $status = Input_Handler::DATA_NOT_PROCESSED,
        string $message = '',
        $data = null
    )
    {
        $this->handlerModule = $handlerModule;
        $this->handlerMethod = $handlerMethod;
        $this->status = $status;
        $this->message = $message;
        $this->data = $data;
    }


    public function handlerModule(?string $handlerModule = null)
    {
        if ($handlerModule === null) {
            return $this->handlerModule;
        }

        $this->handlerModule = $handlerModule;

        return $this;
    }


    public function handlerMethod(?string $handlerMethod = null)
    {
        if ($handlerMethod === null) {
            return $this->handlerMethod;
        }

        $this->handlerMethod = $handlerMethod;

        return $this;
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