<?php 

namespace Vaneves\Apirus;

use \Parsedown;
use \FilesystemIterator;
use \Highlight\Highlighter;
use \League\CLImate\CLImate;
use Symfony\Component\Dotenv\Dotenv;

class Processor
{
    protected $pathSrc;

    protected $pathDist;
    
    protected $pathTheme;

    protected $highlightTheme;

    protected $menu = [];

    protected $items = [];

    protected $parser = null;

    protected $console = null;

    public function __construct()
    {
        $this->parser = new Markdown();
        $this->console = new CLImate();

        $file_env = realpath(__DIR__ . '/../../../.env');
        if (!file_exists($file_env)) {
            $this->console->error("File .env not found");
            exit;
        }

        $dotenv = new Dotenv();
        $dotenv->load($file_env);

        $this->arguments();
    }

    protected function arguments()
    {
        $this->console->arguments->add([
            'help' => [
                'longPrefix'  => 'help',
                'description' => 'Prints a usage statement',
                'noValue'     => true,
            ],
            'src' => [
                'prefix'       => 's',
                'description'  => 'Path the markdown files',
                'defaultValue' => env('SOURCE', 'docs'),
            ],
            'dist' => [
                'prefix'       => 'd',
                'description'  => 'Destination folder',
                'defaultValue' => env('DIST', 'public'),
            ],
            'theme' => [
                'prefix'       => 't',
                'description'  => 'Theme name',
                'defaultValue' => env('THEME', 'themes/default'),
            ],
            'highlight' => [
                'prefix'       => 'h',
                'description'  => 'Highlight style',
                'defaultValue' => env('HIGHTLIGHT', 'dark'),
            ],
        ]);
        $this->console->arguments->parse();

        $help = $this->console->arguments->get('help');

        if ($help) {
            $this->console->usage();
            exit;
        }

        $src = $this->console->arguments->get('src');
        $dist = $this->console->arguments->get('dist');
        $theme = $this->console->arguments->get('theme');
        $highlight = $this->console->arguments->get('highlight');

        $this->pathSrc = rtrim(realpath($src), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        $this->pathDist = rtrim(realpath($dist), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        $this->pathTheme = rtrim(realpath($theme), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'layout.php';
        $this->highlightTheme = $highlight;
    }

    public function build()
    {
        $this->console->whisper('Building...');

        if (!is_dir($this->pathSrc)) {
            $this->console->error("Directory {$this->pathSrc} not found");
            exit;
        }

        $this->console->whisper("Reading {$this->pathSrc}");
        $iterator = new FilesystemIterator($this->pathSrc);
        $structure = [];
        foreach($iterator as $folder) {
            if ($folder->isDir() && !preg_match('/^\./', $folder->getFilename())) {
                $folder_path = rtrim(realpath($this->pathSrc . $folder->getFilename()), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

                if (!$folder->isReadable()) {
                    $this->console->error("Directory {$folder_path} not is readable");
                    exit;
                }

                $item = [
                    'name' => $folder->getFilename(),
                    'path' => $folder_path,
                ];

                $this->console->whisper("Reading {$folder_path}");

                $subiterator = new FilesystemIterator($item['path']);
                $files = [];
                foreach($subiterator as $file) {
                    if ($file->isFile()) {
                        $file_path = $item['path'] . $file->getFilename();

                        if (!$file->isReadable()) {
                            $this->console->error("File {$file_path} not is readable");
                            exit;
                        }

                        $this->console->whisper("Reading file {$file_path}");

                        array_push($files, [
                            'name' => $file->getFilename(),
                            'path' => $item['path'] . $file->getFilename(),
                        ]);
                    }
                }
                $item['files'] = $files;
                array_push($structure, $item);
            }
        }

        usort($structure, 'sort_by_name');

        foreach ($structure as $folder) {
            $submenu = [];

            $files = $folder['files'];
            usort($files, 'sort_by_name');

            foreach ($files as $file) {
                $item = $this->processFile($file);
                $method = isset($item['meta']['method']) ? strtoupper($item['meta']['method']) : null;

                array_push($this->items, $item);
                array_push($submenu, [
                    'title' => $item['meta']['title'],
                    'slug' => $item['slug'],
                    'method' => $method,
                ]);
            }
            array_push($this->menu, [
                'title' => $this->extractName($folder['name']),
                'submenu' => $submenu,
            ]);
        }
        $this->buildHtml();
    }

    protected function processFile($file)
    {
        $this->console->whisper("Processing file {$file['path']}");

        $markdown = file_get_contents($file['path']);
        $section = $this->parser->parse($markdown);

        $meta = $section->meta();
        $content = $section->content();
        $params = $section->params();
        $requests = $section->requests();
        $responses = $section->responses();

        $params = $this->reprocessParams($params);
        $requests = $this->reprocessRequests($requests);
        $responses = $this->reprocessResponses($responses);

        $name = $this->extractName($file['name']);

        if (!isset($meta['title'])) {
            $meta['title'] = $name;
        }
        $slug = $this->extractSlug($meta['title']);

        return [
            'slug' => $slug,
            'meta' => $meta,
            'content' => $content,
            'params' => $params,
            'requests' => $requests,
            'responses' => $responses,
        ];
    }

    protected function reprocessParams($params)
    {
        $parsedown = new Parsedown();

        $result = [];
        foreach ($params as $type => $text) {
            $table = $parsedown->text($text);
            array_push($result, [
                'type' => ucfirst($type),
                'table' => $table,
            ]);
        }
        return $result;
    }

    protected function reprocessRequests($requests)
    {
        $parsedown = new Parsedown();
        $highlighter = new Highlighter();

        $result = [];
        $first = true;
        foreach ($requests as $lang => $text) {
            $language = strtolower($lang);
            try {
                $l = $language == 'curl' ? 'bash' : $language;
                $highlighted = $highlighter->highlight($l, $text);

                $body = "<pre><code class=\"hljs {$highlighted->language}\">";
                $body .=  $highlighted->value;
                $body .=  "</code></pre>";
            } catch (\Exception $e) {
                $markdown = "```{$language}\n{$text}\n```";
                $body = $parsedown->text($markdown);

                $this->console->comment("Highlight to lang {$language} not found");
            }
            
            array_push($result, [
                'first' => $first,
                'hash' => 'request-' . $language .'-'. md5(uniqid(rand(0, 99999), true)),
                'lang' => $lang,
                'body' => $body,
            ]);
            $first = false;
        }
        return $result;
    }

    protected function reprocessResponses($responses)
    {
        $parsedown = new Parsedown();

        $result = [];
        $first = true;
        foreach ($responses as $code => $text) {
            $markdown = "```{$code}\n{$text}\n```";
            $body = $parsedown->text($markdown);
            array_push($result, [
                'first' => $first,
                'hash' => 'response-' . $code .'-'. md5(uniqid(rand(0, 99999), true)),
                'code' => $code,
                'body' => $body,
            ]);
            $first = false;
        }
        return $result;
    }

    protected function extractName($text) 
    {
        $regex = '/^([\d]{0,3}([\s\-]+)?)?([^.]+)(\.md)?$/i';
        $matches = [];
        if (preg_match($regex, $text, $matches)) {
            if (isset($matches[3])) {
                return $matches[3];
            }
        }
        return $text;
    }
    
    protected function extractSlug($text) 
    {
        $text = preg_replace('~[^\pL\d]+~u', '-', $text);
        $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
        $text = preg_replace('~[^-\w]+~', '', $text);
        $text = trim($text, '-');
        $text = preg_replace('~-+~', '-', $text);
        $text = strtolower($text);
        if (empty($text)) {
            return 'n-a';
        }
        return $text;
    }

    protected function buildHtml()
    {
        if (!file_exists($this->pathTheme)) {
            $this->console->error("Theme not found in {$this->pathTheme}");
            exit;
        }

        $this->console->whisper("Building theme {$this->pathTheme}");
        
        $menu = $this->menu;
        $items = $this->items;

        $highlight_css = \HighlightUtilities\getStyleSheet($this->highlightTheme);

        ob_start();

        include $this->pathTheme;

        $html = ob_get_contents();
        $html = preg_replace('/<table>/', '<table class="table">', $html);
        ob_end_clean();

        if (!is_dir($this->pathDist)) {
            $this->console->error("Directory {$this->pathDist} not found");
            exit;
        }

        $this->pathDist .= 'index.html';

        $this->console->whisper("Writing output {$this->pathDist}");

        $ok = file_put_contents($this->pathDist, $html);
        if ($ok) {
            $this->console->info("Build successful");
        } else {
            $this->console->error("Error on write file {$this->pathDist}");
        }
    }
}