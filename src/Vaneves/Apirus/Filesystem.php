<?php 

namespace Vaneves\Apirus;

use \FilesystemIterator;
use \League\CLImate\CLImate;

class Filesystem
{
    protected $path;

    protected $ignoreRegex = '/^\./';

    protected $console;

    public function __construct($path)
    {
        $this->path = $path;

        $this->console = new CLImate();
    }

    public function getStructure()
    {
        $iterator = new FilesystemIterator($this->path);
        $structure = [];
        foreach($iterator as $folder) {
            if ($folder->isDir() && !preg_match($this->ignoreRegex, $folder->getFilename())) {
                if (!$folder->isReadable()) {
                    $this->console->error("Directory {$folder->getPathname()} not is readable");
                    exit;
                }

                $item = [
                    'name' => $folder->getFilename(),
                    'path' => $folder->getPathname(),
                ];

                $subiterator = new FilesystemIterator($folder->getPathname());
                $files = [];
                foreach($subiterator as $file) {
                    if ($file->isFile()) {
                        if (!$file->isReadable()) {
                            $this->console->error("File {$file->getPathname()} not is readable");
                            exit;
                        }
                        array_push($files, [
                            'name' => $file->getFilename(),
                            'path' => $file->getPathname(),
                        ]);
                    }
                }
                $item['files'] = $files;
                array_push($structure, $item);
            }
        }

        return $structure;
    }
}