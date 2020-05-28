<?php 

/*
use Vaneves\Tosko\Pipeline;
use Vaneves\Tosko\Src;
use Vaneves\Tosko\Dist;
use Vaneves\Tosko\Concat;

$js = (new Pipeline)
    ->pipe(new Concat('all.js'))
    ->pipe(new Dist('public/js/'));

$js->process(new Src([
    'vendor/components/jquery/jquery.slim.min.js',
    'vendor/twbs/bootstrap/dist/js/bootstrap.min.js',
]));


$css = (new Pipeline)
    ->pipe(new Concat('all.css'))
    ->pipe(new Dist('public/css/'));

$css->process(new Src([
    'vendor/twbs/bootstrap/dist/css/bootstrap.min.css',
    'themes/default/css/style.css',
]));

$img = (new Pipeline)
    ->pipe(new Dist('public/img/'));

$img->process(new Src([
    'themes/default/img/*',
]));
*/