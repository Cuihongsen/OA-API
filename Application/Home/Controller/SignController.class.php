<?php
namespace Home\Controller;

use Home\Controller\BasicController;
use Think\Controller;

class SignController extends BasicController
{
    public function index()
    {

           $this->user= $this->_checkToken();
           var_dump($this->user);

        $this->ajaxReturn(array(session_id() . 'asdfjhasjhdf'));
    }
}
