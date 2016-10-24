<?php
namespace Home\Controller;

use Home\Controller\BasicController;
use Think\Controller;

class SignController extends BasicController
{
    public function index()
    {
        $this->_checkToken();
        $this->ajaxReturn(array(session_id() . 'asdfjhasjhdf'));
    }
}
