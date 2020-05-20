<?php 

namespace Vaneves\Apirus;

use \Parsedown;
use \Symfony\Component\Yaml\Yaml;

class Markdown
{
    private $regex = [
        'meta' => '/^(---(?s)(.*?)---)/i',
        'request' => '/(```request(\:([\w]+))(?s)(.*?)```)/i',
        'response' => '/(```response(\:([\d]{3}))(?s)(.*?)```)/i',
        'variable' => '/({{\s?([A-Z_]+)\s?}})/',
    ];

    public function parse($markdown)
    {
        $markdown = $this->variable($markdown);

        $md = $this->removeMeta($markdown);
        $md = $this->removeRequests($md);
        $md = $this->removeResponses($md);

        $parsedown = new Parsedown();

        $content = $parsedown->text($md);
        $meta = $this->meta($markdown);
        $requests = $this->requests($markdown);
        $responses = $this->responses($markdown);

        return new Section($meta, $content, $requests, $responses);
    }
    
    protected function variable($text)
    {
        $text = preg_replace_callback($this->regex['variable'], function ($matches) {
            if (isset($matches[2])) {
                return env($matches[2]);
            }
            return $matches[1];
        }, $text);
        return $text;
    }

    protected function meta($text) 
    {
        preg_match($this->regex['meta'], $text, $matches);
        if(isset($matches[2])) {
            return Yaml::parse(trim($matches[2]));
        }
        return [];
    }

    protected function requests($text) 
    {
        preg_match_all($this->regex['request'], $text, $matches);
        if(isset($matches[3]) && isset($matches[4])) {
            if (count($matches[3]) == count($matches[4]))
            return array_combine($matches[3], $matches[4]);
        }
        return [];
    }

    protected function responses($text) 
    {
        preg_match_all($this->regex['response'], $text, $matches);
        if(isset($matches[3]) && isset($matches[4])) {
            if (count($matches[3]) == count($matches[4]))
            return array_combine($matches[3], $matches[4]);
        }
        return [];
    }
    
    protected function removeMeta($text)
    {
        return preg_replace($this->regex['meta'], '', $text);
    }

    protected function removeRequests($text)
    {
        return preg_replace($this->regex['request'], '', $text);
    }

    protected function removeResponses($text)
    {
        return preg_replace($this->regex['response'], '', $text);
    }
}