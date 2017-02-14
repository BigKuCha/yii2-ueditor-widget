<?php
namespace kucha\ueditor;

use Yii;
use yii\base\Action;
use yii\helpers\ArrayHelper;
use yii\web\Response;

class UEditorAction extends Action
{
    /**
     * @var array
     */
    public $config = [];


    public function init()
    {
        //close csrf
        Yii::$app->request->enableCsrfValidation = false;
        //默认设置
        $_config = require(__DIR__ . '/config.php');

        //添加图片默认root路径；
        $_config['imageRoot'] = Yii::getAlias('@webroot');
        $_config['scrawlRoot'] = Yii::getAlias('@webroot');
        $_config['videoRoot'] = Yii::getAlias('@webroot');
        $_config['fileRoot'] = Yii::getAlias('@webroot');

        //load config file
        $this->config = ArrayHelper::merge($_config, $this->config);
        parent::init();
    }

    public function run()
    {
        if (Yii::$app->request->get('callback',false)) {
            Yii::$app->response->format = Response::FORMAT_JSONP;
        } else {
            Yii::$app->response->format = Response::FORMAT_JSON;
        }
        return $this->handleAction();
    }

    /**
     * 处理action
     */
    protected function handleAction()
    {
        $action = Yii::$app->request->get('action');
        switch ($action) {
            case 'config':
                $result = $this->config;
                break;

            /* 上传图片 */
            case 'uploadimage':
                /* 上传涂鸦 */
            case 'uploadscrawl':
                /* 上传视频 */
            case 'uploadvideo':
                /* 上传文件 */
            case 'uploadfile':
                $result = $this->actionUpload();
                //处理返回的URL
                if (substr($result['url'], 0, 1) != '/') {
                    $result['url'] = '/' . $result['url'];
                }
                $result['url'] = Yii::getAlias('@web'.$result['url']);
                break;
            /* 列出图片 */
            case 'listimage':
                /* 列出文件 */
            case 'listfile':
                $result = $this->actionList();
                break;

            /* 抓取远程文件 */
            case 'catchimage':
                $result = $this->actionCrawler();
                break;

            default:
                $result = [
                    'state' => '请求地址出错'
                ];
                break;
        }
        /* 输出结果 */

        return $result;

    }

    /**
     * 上传
     * @return array
     */
    protected function actionUpload()
    {
        $base64 = "upload";
        switch (htmlspecialchars($_GET['action'])) {
            case 'uploadimage':
                $config = array(
                    "pathRoot" => ArrayHelper::getValue($this->config, "imageRoot", $_SERVER['DOCUMENT_ROOT']),
                    "pathFormat" => $this->config['imagePathFormat'],
                    "maxSize" => $this->config['imageMaxSize'],
                    "allowFiles" => $this->config['imageAllowFiles']
                );
                $fieldName = $this->config['imageFieldName'];
                break;
            case 'uploadscrawl':
                $config = array(
                    "pathRoot" => ArrayHelper::getValue($this->config, "scrawlRoot", $_SERVER['DOCUMENT_ROOT']),
                    "pathFormat" => $this->config['scrawlPathFormat'],
                    "maxSize" => $this->config['scrawlMaxSize'],
                    "allowFiles" => $this->config['scrawlAllowFiles'],
                    "oriName" => "scrawl.png"
                );
                $fieldName = $this->config['scrawlFieldName'];
                $base64 = "base64";
                break;
            case 'uploadvideo':
                $config = array(
                    "pathRoot" => ArrayHelper::getValue($this->config, "videoRoot", $_SERVER['DOCUMENT_ROOT']),
                    "pathFormat" => $this->config['videoPathFormat'],
                    "maxSize" => $this->config['videoMaxSize'],
                    "allowFiles" => $this->config['videoAllowFiles']
                );
                $fieldName = $this->config['videoFieldName'];
                break;
            case 'uploadfile':
            default:
                $config = array(
                    "pathRoot" => ArrayHelper::getValue($this->config, "fileRoot", $_SERVER['DOCUMENT_ROOT']),
                    "pathFormat" => $this->config['filePathFormat'],
                    "maxSize" => $this->config['fileMaxSize'],
                    "allowFiles" => $this->config['fileAllowFiles']
                );
                $fieldName = $this->config['fileFieldName'];
                break;
        }
        /* 生成上传实例对象并完成上传 */

        $up = new Uploader($fieldName, $config, $base64);
        /**
         * 得到上传文件所对应的各个参数,数组结构
         * array(
         *     "state" => "",          //上传状态，上传成功时必须返回"SUCCESS"
         *     "url" => "",            //返回的地址
         *     "title" => "",          //新文件名
         *     "original" => "",       //原始文件名
         *     "type" => ""            //文件类型
         *     "size" => "",           //文件大小
         * )
         */

        /* 返回数据 */
        return $up->getFileInfo();
    }

    /**
     * 获取已上传的文件列表
     * @return array
     */
    protected function actionList()
    {
        /* 判断类型 */
        switch ($_GET['action']) {
            /* 列出文件 */
            case 'listfile':
                $allowFiles = $this->config['fileManagerAllowFiles'];
                $listSize = $this->config['fileManagerListSize'];
                $path = $this->config['fileManagerListPath'];
                break;
            /* 列出图片 */
            case 'listimage':
            default:
                $allowFiles = $this->config['imageManagerAllowFiles'];
                $listSize = $this->config['imageManagerListSize'];
                $path = $this->config['imageManagerListPath'];
        }
        $allowFiles = substr(str_replace(".", "|", join("", $allowFiles)), 1);

        /* 获取参数 */
        $size = isset($_GET['size']) ? htmlspecialchars($_GET['size']) : $listSize;
        $start = isset($_GET['start']) ? htmlspecialchars($_GET['start']) : 0;
        $end = (int)$start + (int)$size;

        /* 获取文件列表 */
        $path = $_SERVER['DOCUMENT_ROOT'] . (substr($path, 0, 1) == "/" ? "" : "/") . $path;
        $files = $this->getfiles($path, $allowFiles);
        if (!count($files)) {
            return [
                "state" => "no match file",
                "list" => array(),
                "start" => $start,
                "total" => count($files)
            ];
        }

        /* 获取指定范围的列表 */
        $len = count($files);
        for ($i = min($end, $len) - 1, $list = array(); $i < $len && $i >= 0 && $i >= $start; $i--) {
            $list[] = $files[$i];
        }
//倒序
//for ($i = $end, $list = array(); $i < $len && $i < $end; $i++){
//    $list[] = $files[$i];
//}

        /* 返回数据 */
        return [
            "state" => "SUCCESS",
            "list" => $list,
            "start" => $start,
            "total" => count($files)
        ];
    }

    /**
     * 抓取远程图片
     * @return array
     */
    protected function actionCrawler()
    {
        /* 上传配置 */
        $config = array(
            "pathRoot" => ArrayHelper::getValue($this->config, "catcherRoot", $_SERVER['DOCUMENT_ROOT']),
            "pathFormat" => $this->config['catcherPathFormat'],
            "maxSize" => $this->config['catcherMaxSize'],
            "allowFiles" => $this->config['catcherAllowFiles'],
            "oriName" => "remote.png"
        );
        $fieldName = $this->config['catcherFieldName'];

        /* 抓取远程图片 */
        $list = array();
        if (isset($_POST[$fieldName])) {
            $source = $_POST[$fieldName];
        } else {
            $source = $_GET[$fieldName];
        }
        foreach ($source as $imgUrl) {
            $item = new Uploader($imgUrl, $config, "remote");
            $info = $item->getFileInfo();
            array_push($list, [
                "state" => $info["state"],
                "url" => $info["url"],
                "size" => $info["size"],
                "title" => htmlspecialchars($info["title"]),
                "original" => htmlspecialchars($info["original"]),
                "source" => htmlspecialchars($imgUrl)
            ]);
        }

        /* 返回抓取数据 */
        return [
            'state' => count($list) ? 'SUCCESS' : 'ERROR',
            'list' => $list
        ];
    }

    /**
     * 遍历获取目录下的指定类型的文件
     * @param $path
     * @param $allowFiles
     * @param array $files
     * @return array|null
     */
    protected function getfiles($path, $allowFiles, &$files = array())
    {
        if (!is_dir($path)) return null;
        if (substr($path, strlen($path) - 1) != '/') $path .= '/';
        $handle = opendir($path);
        while (false !== ($file = readdir($handle))) {
            if ($file != '.' && $file != '..') {
                $path2 = $path . $file;
                if (is_dir($path2)) {
                    $this->getfiles($path2, $allowFiles, $files);
                } else {
                    if (preg_match("/\.(" . $allowFiles . ")$/i", $file)) {
                        $files[] = array(
                            'url' => substr($path2, strlen($_SERVER['DOCUMENT_ROOT'])),
                            'mtime' => filemtime($path2)
                        );
                    }
                }
            }
        }
        return $files;
    }
}
