<?php
namespace Home\Controller;

use Home\Controller\BasicController;
use Home\Model\ReturnCodeModel;
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
        /*
        //获取某目录下所有文件、目录名（不包括子目录下文件、目录名）
        $handler = opendir(getcwd() . '\Application\Home\Controller');
        var_dump(getcwd() . '\Application\Home');
        var_dump($handler);
        while (($filename = readdir($handler)) !== false) {
        //务必使用!==，防止目录下出现类似文件名“0”等情况
        if ($filename != "." && $filename != "..") {
        $files[] = $filename;
        }
        }
        //打印所有文件名
        foreach ($files as $value) {
        echo $value . "<br />";
        }
        closedir($handler);

        $file = file_get_contents('C:\Users\cui94\Desktop\CUI-SVN\project\OA\code\api\Application\Home\Controller\BasicController.class.php');
        // $file=fopen('C:\Users\cui94\Desktop\CUI-SVN\project\OA\code\api\Application\Home\BasicController.class.php','w');
        var_dump($file);
         */

        $a = get_class_methods('Home\Controller\BasicController');
        $b = get_class_methods($this);
        foreach ($a as $key => $v1) {
            echo $key . "=>" . $v1 . "<br />";
            foreach ($b as $key2 => $v2) {
                if ($v1 == $v2) {
                    unset($a[$key]); //删除$a数组同值元素
                    unset($b[$key2]); //删除$b数组同值元素
                }
            }
        }
        var_dump($b);
    }

    public function setVacation()
    {
        $this->_checkParams(array('date'));
        $week = I('post.date');
        foreach ($week as $key => $value) {
            if (!in_array($value, array(0, 1, 2, 3, 4, 5, 6))) {
                $this->ajaxReturn(ReturnCodeModel::send(400));
            }

        }
        // die();
        // var_dump($this->user['c_id']);
        // $company=D()->query("select * from oa_company where c_id = ".$this->user['c_id']);
        $select_max_year = D()->query("SELECT `year` FROM oa_sign WHERE c_id = " . $this->user['c_id'] . " ORDER BY `year` desc LIMIT 1;");
        $max_year        = $select_max_year[0]['year'];
        $deleteLsit      = array();
        $companyModel    = D('company');
        $company         = $companyModel->where(array('c_id' => $this->user['c_id']))->find();
        $dates           = week_to_date($week, date('Y-m-d H:i:s', time() + 24 * 60 * 60), $company['end_time']);
        $dataList        = array();

        // var_dump( $dates );
        // var_dump(week_to_date(array(1, 2, 3), '2016-11-14','2016-12-31'));
        // die();
        $sql   = "SELECT * from oa_company LEFT  JOIN oa_user on oa_company.c_id=oa_user.c_id WHERE oa_company.c_id= " . $this->user['c_id'];
        $users = D()->query($sql);
        foreach ($users as $key => $value) {
            foreach ($dates as $k => $v) {
                $dataList[] = array(
                    'year'     => explode('-', $v)[0],
                    'month'    => explode('-', $v)[1],
                    'date'     => explode('-', $v)[2],
                    'u_id'     => $value['u_id'],
                    'c_id'     => $value['c_id'],
                    'operator' => $this->user['u_id'],
                    'vacation' => 1,
                );
            }
        }

        $deleteSQL = "
        DELETE
        FROM
        oa_sign
        WHERE
        c_id = " . $this->user['c_id'] . "
        and `year` > " . date('Y', time()) . "
        or (
            c_id = " . $this->user['c_id'] . "
            and `year` = " . date('Y', time()) . "
            and `month` > " . date('m', time()) . "
        )
        or (
            c_id = " . $this->user['c_id'] . "
            and `year` = " . date('Y', time()) . "
            and `month` = " . date('m', time()) . "
            and `date` > " . date('d', time()) . "
        )";
        try {
            $signModel = new SignModel();
            $signModel->startTrans();
            $signModel->execute($deleteSQL);
            // var_dump($deleteSQL);
            // var_dump($deleteLsit);
            $signModel->addAll($dataList);
            $signModel->commit();
            $this->ajaxReturn(ReturnCodeModel::send(200));
        } catch (\Think\Exception $e) {
            $this->setVacation();
            $this->ajaxReturn(ReturnCodeModel::send(500, '账户异常'));
            $signModel->rollback();
        }
    }

    public function getVacation()
    {
        $this->_checkParams(array('date'));
        $year  = explode('-', I('post.date'))[0];
        $month = explode('-', I('post.date'))[1];
        if (!$year || !$month) {
            $this->ajaxReturn(ReturnCodeModel::send(400));
        }
        // var_dump($year, $month);
        $sql  = "SELECT `date` FROM  oa_sign WHERE `vacation` = 1 GROUP BY `year` ,`month`, `date` HAVING `year` =" . $year . " AND `month`= " . $month;
        $res  = D()->query($sql);
        $date = array();
        foreach ($res as $key => $value) {
            $date[] = $value['date'];
        }
       $this->ajaxReturn(ReturnCodeModel::send(200,null,array('date'=>$date)));
    }

}
