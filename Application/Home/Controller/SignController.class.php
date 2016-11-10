<?php
namespace Home\Controller;

use Home\Controller\BasicController;
use Home\Model\SignModel;
use Think\Controller;
use \Home\Model\ReturnCodeModel;

class SignController extends BasicController
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

    }

    public function sign()
    {

        // 查找今天的记录验证是否已经签到
        $today = $this->SignModel->where(array('year' => date('Y', time()), 'month' => date('m', time()), 'date' => date('d', time()), 'u_id' => $this->user['u_id']))->find();
        $state = explode(',', $today['state']);
        if ($today['vacation']) {
            $this->ajaxReturn(ReturnCodeModel::send(201, '放假')); //超出可签到时间
        }
        if (in_array('sign', $state)) {
            $this->ajaxReturn(ReturnCodeModel::send(202, "重复操作"));
        }
        //签到操作
        if (time() < strtotime(date('Y-m-d 09:05:00', time()))) {
            $signResult = $this->SignModel->option($this->user['u_id'], 'sign'); //签到
            // die(time());
        } elseif (time() < strtotime(date('Y-m-d 09:15:00', time()))) {
            $signResult = $this->SignModel->option($this->user['u_id'], 'late'); //迟到
            // die(time());
        } else {
            $this->ajaxReturn(ReturnCodeModel::send(201, '超出可签到时间')); //超出可签到时间
        }

        switch ($signResult) {
            case 1:
                $this->ajaxReturn(ReturnCodeModel::send(200));
                break;
            case -1:
                $this->ajaxReturn(ReturnCodeModel::send(202, "重复操作"));
                break;
            default:
                $this->ajaxReturn(ReturnCodeModel::send(400));
                break;
        }
    }

    public function getMonth()
    {
        //如果没有toUser，则查询自己的信息
        if (I('toUser')) {
            $toUser = I('toUser');
        } else {
            $toUser = $this->user['u_id'];
        }
        //如果有month则查询month，否则查询当月
        if (I('post.month')) {
            // var_dump(explode('-', I('post.month'))[0]);
            $D      = date('Ymd', mktime(0, 0, 0, explode('-', I('post.month'))[1] + 1, 0, explode('-', I('post.month'))[0]));
            $endDay = strtotime($D);
            // var_dump($endDay);
            // die();
        } else {
            $endDay = time();
        }
/*获取时间段内的记录*/
        $res = $this->SignModel->where(array('year' => date('Y', $endDay), 'month' => date('m', $endDay), 'u_id' => $toUser))->select();
        // var_dump($res);
        // die();
        $res       = $this->SignModel->ProcessData($res);
        $thisMonth = date('m', $endDay); //当前月份
        $month     = strtotime(date('Y-m-01', $endDay)); //当前月份第一天时间戳
        $arr       = array();
        while ($month <= $endDay) {
            // die();
            $map = array(
                'date'  => date('Y-m-d', $month),
                'state' => null,
            );
            foreach ($res as $key => $value) {
                // var_dump($value['date']);
                // var_dump(date('Y-m-d', $month));
                if (strtotime($value['date']) == $month) {
                    $map['state'] = $value['state'];
                }
            }
            $arr[] = $map;
            $month += 60 * 60 * 24;
        }
        // var_dump($arr);
        // die();
        /*设置今天的可操作状态*/
        $today = $this->SignModel->where(array('year' => date('Y', time()), 'month' => date('m', time()), 'date' => date('d', time()), 'u_id' => $toUser))->find();
        $op    = array();
        // 今天放假
        if ($today['vacation']) {
            $this->ajaxReturn(ReturnCodeModel::send(200, null, array('signRecord' => $arr, 'operable' => null)));
        }

        if (time() < strtotime(date('Y-m-d 09:15:00', time())) && (!$today['state'])) {
            $op[] = 'signIn';
        }
        $state = explode(',', $today['state']);
        if (time() > strtotime(date('Y-m-d 18:50:00', time()))) {
            if ((in_array('sign', $state) && count($state) == 1) || (in_array('sign', $state) && (in_array('late', $state) && count($state) == 2))) {
                $op[] = 'signOff';
            }

            if (in_array('leaveHalf', $state) && count($state) == 1) {
                $op[] = 'signOff';
            }
        }
        // print_r($op);
        // die();
        // $month = date('Y-m-d H:i:s', $month);
        $this->ajaxReturn(ReturnCodeModel::send(200, null, array('signRecord' => $arr, 'operable' => $op)));
    }

    public function signOut()
    {
        $today = $this->SignModel->where(array('year' => date('Y', time()), 'month' => date('m', time()), 'date' => date('d', time()), 'u_id' => $this->user['u_id']))->find();
        $op    = array();

        if (time() < strtotime(date('Y-m-d 09:15:00', time())) && (!$today['state'])) {
            $op[] = 'signIn';
        }
        $state = explode(',', $today['state']);
        if (time() > strtotime(date('Y-m-d 18:50:00', time()))) {
            if (in_array('signOut', $state)) {
                $this->ajaxReturn(ReturnCodeModel::send(202, '已签退'));
            }

            if ((in_array('sign', $state) && count($state) == 1) || (in_array('sign', $state) && (in_array('late', $state) && count($state) == 2))) {
                $op[] = 'signOff';
            }

            if (in_array('leaveHalf', $state) && count($state) == 1) {
                $op[] = 'signOff';
            }

        } else {
            $this->ajaxReturn(ReturnCodeModel::send(201, '时间不正确'));
        }
        if (in_array('signOff', $op)) {
            if ($this->SignModel->option($this->user['u_id'], 'signOut')) {
                $this->ajaxReturn(ReturnCodeModel::send(200, null));
            }
            $this->ajaxReturn(ReturnCodeModel::send(500, null));
        } else {
            $this->ajaxReturn(ReturnCodeModel::send(204, '不可签退状态'));
        }
    }

    public function update()
    {
        $this->_checkParams(array('toUser', 'option', 'date'));
        $option = (array_unique(I('post.option')));
        $error  = 0;
        if (in_array('leaveFull', $option) && count($option) != 1) {
            $error = 1;
        }
        if (in_array('leaveHalf', $option)) {
            if (in_array('sign', $option) && in_array('signOut', $option)) {
                $error = 1;
            }
        }
        if (in_array('signOut', $option)) {
            if (!in_array('sign', $option) && !in_array('leaveHalf', $option)) {
                $error = 1;
            }
        }

        if (in_array('late', $option)) {
            if (!in_array('sign', $option)) {
                $error = 1;
            }
        }
        if ($error) {
            $this->ajaxReturn(ReturnCodeModel::send(400, '你输入的数据有误'));
        }
        $map = array(
            'u_id'     => I('post.toUser'),
            'year'     => explode('-', I('post.date'))[0],
            'month'    => explode('-', I('post.date'))[1],
            'date'     => explode('-', I('post.date'))[2],
            'state'    => implode(',', $option),
            'operator' => $this->user['u_id'],
        );
        if ($this->SignModel->create($map)) {
            $this->SignModel->add();
            $this->ajaxReturn(ReturnCodeModel::send(200));

        } else {
            $map = array(
                'u_id'  => I('post.toUser'),
                'year'  => explode('-', I('post.date'))[0],
                'month' => explode('-', I('post.date'))[1],
                'date'  => explode('-', I('post.date'))[2],
            );
            $res = $this->SignModel->where($map)->data(array('state' => implode(',', $option), 'operator' => $this->user['u_id']))->save();
            if ($res == 1) {
                $this->ajaxReturn(ReturnCodeModel::send(200));
            } elseif ($res == 0) {
                $this->ajaxReturn(ReturnCodeModel::send(203, '无更改'));
            }
        }
    }
    /**
     * 设置某个用户的某一天的状态为旷工
     */
    public function setAbsenteeism()
    {
        $this->_checkParams(array('toUser', 'date'));
        $map = array(
            'u_id'        => I('post.toUser'),
            'year'        => explode('-', I('post.date'))[0],
            'month'       => explode('-', I('post.date'))[1],
            'date'        => explode('-', I('post.date'))[2],
            'absenteeism' => 1,
            'operator'    => $this->user['u_id'],
            'c_id'        => $this->UserModel->findUser(I('post.toUser'))['c_id'],
        );
        if (!$this->SignModel->create($map)) {
            unset($map['absenteeism']);
            if ($this->SignModel->where($map)->data(array('absenteeism' => 1))) {
                $this->SignModel->save();
                $this->ajaxReturn(ReturnCodeModel::send(200));
            } else {
                $this->ajaxReturn(ReturnCodeModel::send(500, $this->SignModel->getError()));
            }

        } else {
            $this->SignModel->add();
            $this->ajaxReturn(ReturnCodeModel::send(200));
        }
    }

}
