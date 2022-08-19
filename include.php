<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

CJSCore::RegisterExt('welpodron.reviews', [
    'css' => '/local/modules/welpodron.reviews/css/style.css',
    'js' => '/local/modules/welpodron.reviews/js/script.js',
    'rel' => ['ajax'],
    'skip_core' => false
]);

CJSCore::Init(['welpodron.reviews']);
