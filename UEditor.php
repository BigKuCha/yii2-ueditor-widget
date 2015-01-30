<?php

/**
 * @link http://ueditor.baidu.com/website/index.html
 */
namespace kucha\ueditor;

use Yii;
use yii\helpers\ArrayHelper;
use yii\helpers\Json;
use yii\helpers\Url;
use yii\web\View;
use yii\widgets\InputWidget;

class UEditor extends InputWidget
{
    //配置选项，参阅Ueditor官网文档(定制菜单等)
    public $jsOptions = [];

    //预设配置
    protected $_options;

    /**
     * @throws \yii\base\InvalidConfigException
     */
    public function init()
    {
        $this->_options = [
            'serverUrl' => Url::to(['upload']),
        ];
        $this->jsOptions = ArrayHelper::merge($this->_options, $this->jsOptions);
        parent::init();
    }

    public function run()
    {
        $this->registerClientScript();
        if ($this->hasModel()) {
            $this->value = $this->model->getAttributes($this->attribute);
        }
        echo '<script id="' . $this->id . '" type="text/plain" style="width:1024px;height:500px;">' . $this->value . '</script>';
    }

    /**
     * 注册客户端脚本
     */
    protected function registerClientScript()
    {
        UEditorAsset::register($this->view);
        $jsonOptions = Json::encode($this->jsOptions);
        $script = "UE.getEditor('" . $this->id . "', " . $jsonOptions . ")";
        $this->view->registerJs($script, View::POS_READY);
    }
}