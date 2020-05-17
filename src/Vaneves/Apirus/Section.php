<?php 

namespace Vaneves\Apirus;

class Section
{
    private $meta;
    private $content;
    private $requests;
    private $responses;

    public function __construct($meta, $content, $requests, $responses)
    {
        $this->meta = $meta;
        $this->content = $content;
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

    public function requests()
    {
        return $this->requests;
    }

    public function responses()
    {
        return $this->responses;
    }
}