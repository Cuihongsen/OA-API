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
        // dump($user);
        // die();
        // $this->UserModel->checkToken($user, I('post.token'));
        // if ($user['token'] !== I('post.token')) {
        //     $this->ajaxReturn(ReturnCodeModel::send(601));
        // }
        return $user;
    }

    /**
     * 检查参数是否正确
     * @param [type] $[name] [<description>]
     * @return [type] [description]
     */

    public function _checkParams($params)
    {
// dump($params);
        foreach ($params as $key => $value) {
            // dump($value);
            // dump(I("post.{$value}"));
            // dump($_POST[$value]);
            if (null === $_POST[$value]) {
                $this->ajaxReturn(ReturnCodeModel::send(400, "参数{$value}不存在"));
            }
        }
// die();

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
}
