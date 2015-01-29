<?php
namespace kucha\ueditor;


use yii\web\AssetBundle;
use yii\web\View;

class UEditorAsset extends AssetBundle
{
    public $sourcePath = '@app/widgets/ueditor/assets';
    public $js = [
        'js/ueditor.config.js',
        'js/ueditor.all.js',
        'lang/zh-cn/zh-cn.js'
    ];
    public $jsOptions = [
        'position' =>View::POS_HEAD,
    ];
}