<?php 

namespace Vaneves\Apirus;

class Section
{
    private $meta;
    private $content;
    private $params;
    private $requests;
    private $responses;

    public function __construct(
        $meta
        , $content
        , $params
        , $requests
        , $responses
    ) {
        $this->meta = $meta;
        $this->content = $content;
        $this->params = $params;
        $this->requests = $requests;
        $this->responses = $responses;
    }

    public function meta()
    {
        return $this->meta;
    }

    public function content()
    {
        return $this->content;
    }

    public function params()
    {
        return $this->params;
    }

    public function requests()
    {
        return $this->requests;
    }

    public function responses()
    {
        return $this->responses;
    }
}