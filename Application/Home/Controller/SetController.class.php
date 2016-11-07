<?php
namespace Home\Controller;

use Home\Controller\BasicController;
use Home\Model\SignModel;
use Think\Controller;

class SetController extends BasicController
{
    public $SignModel;

    public function __construct()
    {
        parent::__construct();
        $this->SignModel = new SignModel();
        $this->user      = $this->_checkToken();
    }
    public function index()
    {
        //获取某目录下所有文件、目录名（不包括子目录下文件、目录名）
        $handler = opendir(getcwd());
        while (($filename = readdir($handler)) !== false) {
//务必使用!==，防止目录下出现类似文件名“0”等情况
            if ($filename != "." && $filename != "..") {
                $files[] = $filename;
            }
        }
    }
    // closedir($handler);

//打印所有文件名
    foreach ($filens as $value) {
        echo $value . "<br />";
    }
}
