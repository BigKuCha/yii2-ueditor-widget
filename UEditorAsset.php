<?php
namespace kucha\ueditor;


use yii\web\AssetBundle;
use yii\web\View;

class UEditorAsset extends AssetBundle
{
    public $js = [
        'js/ueditor.config.js',
        'js/ueditor.all.js',
        'lang/zh-cn/zh-cn.js'
    ];
    public $jsOptions = [
        'position' =>View::POS_HEAD,
    ];
    public function init()
    {
        $this->sourcePath = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'assets';
    }
}