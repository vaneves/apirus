<?php 

namespace Vaneves\Apirus;

use \Parsedown;
use \Highlight\Highlighter;
use \League\CLImate\CLImate;
use Symfony\Component\Dotenv\Dotenv;
use \Symfony\Component\Yaml\Yaml;

class Processor
{
    protected $pathSrc;

    protected $pathDist;
    
    protected $pathTheme;

    protected $highlightTheme;

    protected $watch = false;

    protected $menu = [];

    protected $items = [];

    protected $parser = null;

    protected $console = null;

    protected $filesytem = null;

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

        $this->filesytem = new Filesystem($this->pathSrc);
    }

    protected function arguments()
    {
        $this->console->arguments->add([
            'help' => [
                'longPrefix'  => 'help',
                'description' => 'Prints a usage statement',
                'noValue'     => true,
            ],
            'watch' => [
                'longPrefix'  => 'watch',
                'description' => 'Watching files changes',
                'noValue'     => true,
            ],
            'src' => [
                'prefix'       => 's',
                'longPrefix'   => 'src',
                'description'  => 'Path the markdown files',
                'defaultValue' => env('SOURCE', 'docs'),
            ],
            'dist' => [
                'prefix'       => 'd',
                'longPrefix'   => 'dist',
                'description'  => 'Destination folder',
                'defaultValue' => env('DIST', 'public'),
            ],
            'theme' => [
                'prefix'       => 't',
                'longPrefix'   => 'theme',
                'description'  => 'Theme name',
                'defaultValue' => env('THEME', 'themes/default'),
            ],
            'highlight' => [
                'prefix'       => 'h',
                'longPrefix'   => 'highlight',
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

        $watch = $this->console->arguments->get('watch');
        if ($watch) {
            $this->watch = true;
        }

        $src = $this->console->arguments->get('src');
        $dist = $this->console->arguments->get('dist');
        $theme = $this->console->arguments->get('theme');
        $highlight = $this->console->arguments->get('highlight');

        $real_src = realpath($src);
        $real_dist = realpath($dist);
        $real_theme = realpath($theme);

        if ($real_src === false) {
            $this->console->error("Directory {$src} not found");
            exit;
        }
        if ($real_dist === false) {
            $this->console->error("Directory {$dist} not found");
            exit;
        }
        if ($real_theme === false) {
            $this->console->error("Directory {$theme} not found");
            exit;
        }

        $this->pathSrc = rtrim($real_src, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        $this->pathDist = rtrim($real_dist, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        $this->pathTheme = rtrim($real_theme, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'layout.php';
        $this->highlightTheme = $highlight;
    }

    public function run()
    {
        $this->console->comment('Building...');
        $this->build();
        if ($this->watch) {
            $this->watch();
        }
    }

    public function build()
    {
        if (!is_dir($this->pathSrc)) {
            $this->console->error("Directory {$this->pathSrc} not found");
            exit;
        }

        $this->console->whisper("Reading {$this->pathSrc}");
        $structure = $this->filesytem->getStructure();

        usort($structure, 'sort_by_name');

        $this->menu = [];
        $this->items = [];

        foreach ($structure as $folder) {
            $this->console->whisper("Processing folder {$folder['path']}");
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
        $this->runTosko();
        $this->buildHtml();

        if ($this->watch) {
            $this->console->whisper("Watching files for changes...");
        }
    }

    protected function watch()
    {
        $watcher = new Watcher($this->pathSrc);
        $watcher->onChange(function () {
            $this->console->comment('Rebuilding...');
            $this->build();
        });
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
            $yaml = Yaml::parse(trim($text));
            array_push($result, [
                'type' => ucfirst($type),
                'params' => $yaml,
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
        $highlighter = new Highlighter();

        $result = [];
        $first = true;
        foreach ($responses as $response) {
            $code = $response['code'];
            $language = strtolower($response['lang']);
            try {
                $highlighted = $highlighter->highlight($language, $response['body']);

                $body = "<pre><code class=\"hljs {$highlighted->language}\">";
                $body .=  $highlighted->value;
                $body .=  "</code></pre>";
            } catch (\Exception $e) {
                $markdown = "```{$language}\n{$response['body']}\n```";
                $body = $parsedown->text($markdown);

                if ($language) {
                    $this->console->comment("Highlight to lang {$language} not found");
                }
            }

            array_push($result, [
                'first' => $first,
                'hash' => 'response-' . $code .'-'. md5(uniqid(rand(0, 99999), true)),
                'code' => $code,
                'lang' => $response['lang'],
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

    protected function runTosko()
    {
        $tosko = './tosko.php';

        if (!file_exists($tosko)) {
            $this->console->whisper("Tosko disabled");
            return;
        }
        $this->console->whisper("Running tosko");

        include $tosko;
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

        $dist = $this->pathDist . 'index.html';

        $this->console->whisper("Writing output {$dist}");

        $ok = file_put_contents($dist, $html);
        if ($ok) {
            $this->console->info("Build successful");
        } else {
            $this->console->error("Error on write file {$dist}");
        }
    }
}