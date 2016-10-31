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

        $res = $this->SignModel->select();
        // $this->_checkParams(array('operation'));
        // var_dump(time(), strtotime(date('Y-m-d 09:05:00', time())));
        // die();
        if (time() < strtotime(date('Y-m-d 09:05:00', time()))) {
            $signResult = $this->SignModel->option($this->user['u_id'], 'sign');
            // die(time());
        } elseif (time() < strtotime(date('Y-m-d 09:15:00', time()))) {
            $signResult = $this->SignModel->option($this->user['u_id'], 'late');
            // die(time());
        } else {
            $this->ajaxReturn(ReturnCodeModel::send(201, '过时'));
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
        // echo I('post.option');
        // $this->ajaxReturn(ReturnCodeModel::send('123123', $this->SignModel->getError()), 'jsonp');
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
            // phpinfo();
        }
        $state = explode(',', $today['state']);
        // var_dump($today['state']);
        // var_dump(count($state) == 0);
        // var_dump(!in_array('sign', $state));
        // var_dump(time() <strtotime(date('Y-m-d 09:15:00', time())));
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
            // phpinfo();
        }
        $state = explode(',', $today['state']);
        // var_dump($today['state']);
        // var_dump(count($state) == 0);
        // var_dump(!in_array('sign', $state));
        // var_dump(time() <strtotime(date('Y-m-d 09:15:00', time())));
        if (time() > strtotime(date('Y-m-d 18:50:00', time()))) {
            if (in_array('signOut', $state)) {
                $this->ajaxReturn(ReturnCodeModel::send(200, '已签退'));
            }

            if ((in_array('sign', $state) && count($state) == 1) || (in_array('sign', $state) && (in_array('late', $state) && count($state) == 2))) {
                $op[] = 'signOff';
            }

            if (in_array('leaveHalf', $state) && count($state) == 1) {
                $op[] = 'signOff';
            }

        } else {
            $this->ajaxReturn(ReturnCodeModel::send(200, '时间不正确'));
        }

        // var_dump($today['state']);
        if (in_array('signOff', $op)) {
            if ($this->SignModel->option($this->user['u_id'], 'signOut')) {
                $this->ajaxReturn(ReturnCodeModel::send(200, null));
            }
            $this->ajaxReturn(ReturnCodeModel::send(500, null));
        } else {
            $this->ajaxReturn(ReturnCodeModel::send(200, '不可签退状态'));
        }
    }

}
