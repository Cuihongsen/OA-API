<?php

namespace Home\Controller;

use Think\Controller;
use \Home\Model\ReturnCodeModel;

class BasicController extends Controller
{
    /**
     * @var \Home\Model\UserModel
     */
    public $UserModel;
    /**
     * @var bool
     */
    public $user;

    /**
     * BasicController constructor.
     */
    public function __construct()
    {
        //开启缓存
        // S(array('expire' => 60));
        parent::__construct();
        $this->UserModel = new \Home\Model\UserModel();
        // $this->ajaxReturn(ReturnCodeModel::send(400, '无参数'));
        // 非post提交
        if (!IS_POST) {
            $this->ajaxReturn(ReturnCodeModel::send(400, 'not Post'));
        }
        // 是post提交，但是无数据
        if (!empty(file_get_contents('php://input'))) {
            // 无数据
            $data = json_decode(file_get_contents('php://input'), 1);
            if ($data) {
                $_POST = json_decode(file_get_contents('php://input'), 1);
            }
        } else {
            if (!$_POST) {
                $this->ajaxReturn(ReturnCodeModel::send(400, '无参数'));
            }
        }
    }
    /**
     *验证token
     */
    public function _checkToken()
    {
        $this->_checkParams(array('u_id', 'token'));
        $user = $this->UserModel->findUser(I('post.u_id'));
        // var_dump($use);
        // die();
        if (!$user) {
            $this->ajaxReturn(ReturnCodeModel::send(601));
            return null;
        }
        $user_1 = D('token')->find(I('post.u_id'));
        if ($user_1['token'] !== I('post.token')) {
            $this->ajaxReturn(ReturnCodeModel::send(601));
        }
        return $user;
    }

    /**
     * 检查参数是否正确
     * @param [type] $[name] [<description>]
     * @return [type] [description]
     */

    public function _checkParams($params)
    {
        foreach ($params as $key => $value) {
            if (null === $_POST[$value]) {
                $this->ajaxReturn(ReturnCodeModel::send(400, "参数{$value}不存在"));
            }
        }

    }

    /**
     * @param $authorization
     */
    public function _checkAuthorization($authorization)
    {
        if (!in_array($authorization, $this->user['authorization'])) {
            $this->ajaxReturn(ReturnCodeModel::send(401));
        };
    }

    public function _empty()
    {
        $this->ajaxReturn(ReturnCodeModel::send(404));
    }

    public function _isAdmin()
    {
        if ($this->user['usertype'] != 'admin') {
            $this->ajaxReturn(ReturnCodeModel::send(600, '不具有管理权限'));
        }
    }
}
