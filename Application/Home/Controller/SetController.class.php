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
        $this->_isAdmin();
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
        // $this->_isAdmin();
        $this->_checkParams(array('date'));
        $year  = explode('-', I('post.date'))[0];
        $month = explode('-', I('post.date'))[1];
        if (!$year || !$month) {
            $this->ajaxReturn(ReturnCodeModel::send(400));
        }
        // var_dump($year, $month);
        $sql  = "SELECT `date` FROM  oa_sign WHERE `vacation` = 1 and c_id = " . $this->user['c_id'] . " GROUP BY `year` ,`month`, `date` HAVING `year` =" . $year . " AND `month`= " . $month;
        $res  = D()->query($sql);
        $date = array();
        foreach ($res as $key => $value) {
            $date[] = $value['date'];
        }
        $this->ajaxReturn(ReturnCodeModel::send(200, null, array('date' => $date)));
    }

    public function setDateToVacation()
    {
        $this->_isAdmin();
        $this->_checkParams(array('contents'));
        $this->SignModel->startTrans();
        try {
            foreach (I('post.contents') as $key => $value) {
                if (strtotime($key) < time()) {
                    $this->ajaxReturn(ReturnCodeModel::send(400));
                }
                switch ($value) {
                    case 'work': //设置成工作日
                        $deleteSQL = "DELETE FROM oa_sign WHERE c_id =" . $this->user['c_id'] . " and `year`= " . explode("-", $key)[0] . " and `month`=" . explode("-", $key)[1] . " and `date`=" . explode("-", $key)[2];
                        $this->SignModel->execute($deleteSQL);
                        break;
                    case 'vacation': //设置成假期
                        $dataList = array();
                        $dates    = array($key);
                        $sql      = "SELECT * from oa_company LEFT  JOIN oa_user on oa_company.c_id=oa_user.c_id WHERE oa_company.c_id= " . $this->user['c_id'];
                        $users    = D()->query($sql);
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
                        $this->SignModel->addAll($dataList);
                        // var_dump($dataList);
                        break;
                    default:
                        $this->ajaxReturn(ReturnCodeModel::send(400));
                        break;
                }

            }
            // 事务提交
            $this->SignModel->commit();
            $this->ajaxReturn(ReturnCodeModel::send(200));
        } catch (\Think\Exception $e) {
            // 异常回滚
            $this->SignModel->rollback();
            $this->ajaxReturn(ReturnCodeModel::send(400));
        }
    }

    public function addNewMember()
    {
        $this->_isAdmin();
        $this->_checkParams(
            array(
                'userType',
                'enterTime',
                'name',
                'position',
                'status',
                'tel',
                'username',
                'password',
            )
        );
        $map = array(
            'username'    => I('post.username'),
            'password'    => I('post.password'),
            'userType'    => I('post.userType'),
            'enterTime'   => I('post.enterTime'),
            'name'        => I('post.name'),
            'position'    => I('post.position'),
            'status'      => I('post.status'),
            'tel'         => I('post.tel'),
            'create_time' => date('Y-m-d H:i:s', time()),
            'c_id'        => $this->user['c_id'],
        );
        // dump($map);
        // die();
        $this->SignModel->startTrans();
        try {
            $newUserID = '';
            if (!$this->UserModel->create($map)) {
                $this->ajaxReturn(ReturnCodeModel::send(300, $this->UserModel->getError()));
            }
            $newUserID = $this->UserModel->add();
            $role_id   = 1;
            if ($map['userType'] == 'admin') {
                $role_id = 2;
            }
            $this->UserModel->execute("INSERT INTO `oa_user_role` (`user_id`, `role_id`) VALUES ('{$newUserID}', '{$role_id}')");
            $this->SignModel->commit();
            $this->ajaxReturn(ReturnCodeModel::send(200));

        } catch (\Think\Exception $e) {
            // 异常回滚
            $this->SignModel->rollback();
            $this->ajaxReturn(ReturnCodeModel::send(400));
        }
    }

    public function getMemberInfo()
    {
        $this->_checkParams(
            array(
                'toUser',
            )
        );
        $field = '';
        switch ($this->user['usertype']) {
            case 'user':
                $field = 'profile,motto,name,sex,tel,weChat,email,birth,birthPlace,enterTime,position,status';
                break;
            case 'admin':
                $field = 'profile,motto,name,sex,tel,weChat,email,birth,birthPlace,enterTime,position,status,bankName,bankNum,bankUser,ALiPay';
                break;
            default:
                $this->ajaxReturn(ReturnCodeModel::send(400));
                break;
        }
        $userInfo = $this->UserModel->field($field)->find(I('post.toUser'));

        $this->ajaxReturn(ReturnCodeModel::send(200, null, $userInfo));
    }
    public function setMemberInfo()
    {
        $map   = array();
        $where = array();
        switch ($this->user['usertype']) {
            case 'user':
                $p = array('profile', 'motto', 'name', 'sex', 'tel', 'weChat', 'email', 'birth', 'birthPlace', 'bankName', 'bankNum', 'bankUser', 'ALiPay');
                foreach ($_POST as $key => $value) {
                    if (in_array($key, $p)) {
                        $map[$key] = $value;
                    }
                }
                $where['u_id'] = $this->user['u_id'];
                break;
            case 'admin':
                $this->_checkParams(
                    array(
                        'toUser',
                    )
                );
                $p = array('enterTime', 'position', 'status');
                foreach ($_POST as $key => $value) {
                    if (in_array($key, $p)) {
                        $map[$key] = $value;
                    }
                }
                $where['u_id'] = I('post.toUser');

                $toUser = $this->UserModel->where($where)->find();
                // var_dump($toUser);
                if ($toUser['c_id'] != $this->user['c_id']) {
                    $this->ajaxReturn(ReturnCodeModel::send(400, '无权操作这个用户'));
                }
                // die();
                break;
            default:
                $this->ajaxReturn(ReturnCodeModel::send(400, '非法用户类型'));
                break;
        }
        if ($this->UserModel->where($where)->data($map)->save()) {
            $this->ajaxReturn(ReturnCodeModel::send(200));
        }
        $this->ajaxReturn(ReturnCodeModel::send(201, '无修改'));
    }

    public function deleteMember()
    {
        $this->_isAdmin();
        $this->_checkParams(array('toUser'));
        $this->SignModel->startTrans();
        $toUser = $this->SignModel->find(I('post.toUser'));
        if ($toUser['c_id'] != $this->user['c_id']) {
            $this->ajaxReturn(ReturnCodeModel::send(600, '无权操作'));
        }
        try {
            $sql1 = "DELETE FROM oa_user WHERE u_id = " . I('post.toUser');
            $sql2 = "DELETE FROM oa_user_role WHERE user_id = " . I('post.toUser');
            $re1  = $this->UserModel->execute($sql1);
            $re2  = $this->UserModel->execute($sql2);
            $this->UserModel->commit();
            if (!$re1 || !$re2) {
                $this->ajaxReturn(ReturnCodeModel::send(203, '无更改'));
            }
            // var_dump($re1, $re2, $toUser);
            $this->ajaxReturn(ReturnCodeModel::send(200));
        } catch (\Think\Exception $e) {
            // 异常回滚
            $this->SignModel->rollback();
            $this->ajaxReturn(ReturnCodeModel::send(400));
        }
    }

    public function getMemberEasyInfo()
    {
        // var_dump(I('post.lastID'));
        $r = $this->UserModel->field('u_id,name,sex,tel')->where(array('c_id' => $this->user['c_id'],'u_id'=>array('GT',I('post.lastID'))))->order('u_id asc')->limit(10)->select();
        $this->ajaxReturn(ReturnCodeModel::send(200,null,$r));
    }
}
