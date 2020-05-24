<?php 

namespace Vaneves\Apirus;

use \FilesystemIterator;
use \League\CLImate\CLImate;

class Watcher
{
    protected $filesytem;

    protected $console;

    protected $hashs = [];

    protected $callback;

    public function __construct($path)
    {
        $this->filesytem = new Filesystem($path);
        $this->console = new CLImate();
        $this->console->style->addCommand('changed', ['underline', 'green', 'bold']);
    }

    protected function getFiles()
    {
        $hashs = [];
        $structure = $this->filesytem->getStructure();
        foreach ($structure as $folder) {
            foreach ($folder['files'] as $file) {
                $path = $file['path'];
                $hash = md5(file_get_contents($path));

                $hashs[$path] = $hash;
            }
        }
        return $hashs;
    }

    protected function checkDoc()
    {
        $rebuild = false;
        $checkeds = [];

        $files = $this->getFiles();
        foreach ($files as $path => $hash) {
            if (!isset($this->hashs[$path])) {
                $this->printChanged('File created', $path);
                $this->hashs[$path] = $hash;
                $rebuild = true;
            }
            if ($this->hashs[$path] != $hash) {
                $this->printChanged('File changed', $path);
                $this->hashs[$path] = $hash;
                $rebuild = true;
            }
            $checkeds[$path] = $hash;
        }

        $deleteds = array_diff_assoc($this->hashs, $checkeds);
        if (count($deleteds) > 0) {
            foreach ($deleteds as $p => $deleted) {
                $this->printChanged('File deleted', $p);
            }
            $rebuild = true;
        }
        $this->hashs = $checkeds;

        if ($rebuild) {
            $callback = $this->callback;
            $callback();
        }
    }

    protected function printChanged($title, $file)
    {
        $this->console->whisper("<magenta>{$title}:</magenta> {$file}");
    }

    public function onChange($callback)
    {
        if (!is_callable($callback)) {
            $this->console->error("Param is not callable");
            exit;
        }
        $this->callback = $callback;

        $this->hashs = $this->getFiles();

        while(true) {
            $this->checkDoc();
            sleep(2);
        }
    }
}