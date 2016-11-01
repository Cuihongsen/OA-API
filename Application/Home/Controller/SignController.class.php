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
        $res       = $this->SignModel->where(array('year' => date('Y', time()), 'month' => date('m', time()), 'u_id' => $this->user['u_id']))->select();
        $res       = $this->SignModel->ProcessData($res);
        $thisMonth = date('m', time());
        $month     = strtotime(date('Y-m-1', time()));
        $arr       = array();
        while ($month < time()) {
            $map = array(
                'date'  => date('Y-m-d', $month),
                'state' => null,
            );
            foreach ($res as $key => $value) {
                if ($value['date'] == date('Y-m-d', $month)) {
                    $map['state'] = $value['state'];
                }
            }
            $arr[] = $map;
            $month += 60 * 60 * 24;
        }
        $today = $this->SignModel->where(array('year' => date('Y', time()), 'month' => date('m', time()), 'date' => date('d', time()), 'u_id' => $this->user['u_id']))->find();
        $op    = array();

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
            $this->ajaxReturn(ReturnCodeModel::send(203, '时间不正确'));
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
            $this->ajaxReturn(ReturnCodeModel::send(200, '你输入的数据有误'));
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
                $this->ajaxReturn(ReturnCodeModel::send(200, '无更改'));
            }
        }
    }

}
