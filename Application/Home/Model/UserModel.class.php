<?php
namespace Home\Model;

use Think\Model;

/**
 * Class UserModel
 * @package Home\Model
 */
class UserModel extends Model
{
    // public $trueTableName = 'T_User';
    public $user_id;
    public $user_name;
    public $pass_word;
    public $nick_name;
    public $user_profile;

    /**
     * 自动验证规则
     * @var array
     */
    protected $_validate = array(
        // array('verify', 'require', '验证码必须！'), //默认情况下用正则进行验证
        // array('username', '', '手机号码已存在', 0, 'unique', 3), // 在新增的时候验证name字段是否唯一
        // array('username', "/^1[3|4|5|7|8][0-9]{9}$/", '手机号码格式错误', 0, 'regex', 3), // 当值不为空的时候判断是否在一个范围内
        // array('repassword', 'password', '确认密码不正确', 0, 'confirm'), // 验证确认密码是否和密码一致
        // array('password', 'checkPwd', '密码格式不正确', 0, 'function'), // 自定义函数验证密码格式
    );

    /**
     * 查询用户的资料信息
     * @param  int $user_id 用户id
     * @return bool
     */
    public function findUser($user_id)
    {
        // $user    = $this->find($user_id);
        $dbModel = new \Think\Model();
        $sql     = "SELECT
    *
FROM
    (
        SELECT
            a.*, oa_role_auth.auth_id
        FROM
            (
                SELECT
                    oa_user.*, oa_user_role.role_id
                FROM
                    oa_user
                JOIN oa_user_role ON oa_user.u_id = oa_user_role.user_id
            ) AS a
        JOIN oa_role_auth ON a.role_id = oa_role_auth.role_id
    ) AS b
JOIN oa_role ON b.role_id = oa_role.role_id
WHERE
    u_id = {$user_id}
ORDER BY
    oa_role.role_id ASC";
        $result = $dbModel->query($sql);
        if (!$result) {
            return false;
        }
        $user = array();
        foreach ($result as $key => $value) {
            $user['u_id']        = $value['u_id'];
            $user['username']    = $value['username'];
            $user['password']    = $value['password'];
            $user['create_time'] = $value['create_time'];
            // $user['role'][] = array(
            //     'role_id'   => $value['role_id'],
            //     'role_name' => $value['role_name'],
            //     'role_description' => $value['role_description'],
            // );
            $user['auth'][] = $value['auth_id'];
            $user['role'][] = $value['role_id'];
        }

 $user['auth']=array_unique($user['auth']);
        
        // dump($result);
        // dump($user);
        // die();
        // $T_User_RoleModel                = new \Think\Model();
        // $T_User_RoleModel->trueTableName = 'T_User_Role';
        // $T_User_Role                     = $T_User_RoleModel->where(array('user_id' => $user['user_id']))->find();

        // $T_RoleModel                     = new \Think\Model();
        // $T_RoleModel->trueTableName      = 'T_Role';
        // $T_Role                          = $T_RoleModel->where(array('role_id' => $T_User_Role['role_id']))->find();
        // $T_Role_AuthModel                = new \Think\Model();
        // $T_Role_AuthModel->trueTableName = 'T_Role_Auth';
        // $T_Role_Auth                     = $T_Role_AuthModel->where(array('role_id' => $T_Role['role_id']))->select();

        // $T_AuthorizationModel                = new \Think\Model();
        // $T_AuthorizationModel->trueTableName = 'T_Authorization';
        // foreach ($T_Role_Auth as $key => $value) {
        //     $T_Authorization         = $T_AuthorizationModel->where(array('auth_id' => $value['auth_id']))->find();
        //     $user['authorization'][] = (int) $T_Authorization['auth_id'];
        // }
        // // var_dump($T_Role);
        // // var_dump($user);
        // // die();
        // $user['Role'] = (int) $T_Role['role_id'];
        // unset($T_User_RoleModel);
        // unset($T_RoleModel);
        // unset($T_AuthorizationModel);
        return $user;
    }

    /**
     *
     */
    public function regUser()
    {

    }

    /**
     * 检查用户密码
     * @param $user 用户名
     * @param $password 密码
     * @return bool
     */
    public function checkPassword($user, $password)
    {
        if ($user['password'] == md5($password)) {
            return true;
        } else {
            // var_dump($user['password']);
            // var_dump(md5($password));
            // die();
            return false;
        }
    }

    /**
     * 通过手机号码查找用户
     * @param string $phone 手机号码
     * @return bool|mixed
     */
    public function findByPhone($phone = '')
    {
        $user = $this->where(array('user_name' => $phone))->find();
        if ($user) {
            return $user;
        }
        return false;
    }

    /**
     * 添加用户
     * @param $phone 手机号码
     * @param $password 密码
     * @return bool|mixed
     */
    public function createUser($phone, $password)
    {
        // 开启事务
        $this->startTrans();
        $map = array(
            'user_name'    => $phone,
            'password'     => md5($password),
            'create_time'  => time(),
            'user_profile' => C('DefaultPhoto'),
        );
        if (!$this->create($map, 1)) {
            // var_dump($this->getError());
            // die();
            // 事务回滚
            $this->rollback();
            return false;
        }
        $id   = $this->add();
        $user = $this->find($id);
        //添加角色用户属性
        $rol                      = array('role_id' => 2, 'user_id' => $id);
        $roleModel                = D();
        $roleModel->trueTableName = 'T_User_Role';
        $role_id                  = $roleModel->add($rol);
        if (!$role_id) {
            $this->rollback();
            return false;
        }
        // 事务提交
        $this->commit();
        return $user;
    }

    /**
     * 生成token
     * @param $user
     * @return bool
     */
    public function createToken($user)
    {
        $token = md5($user . time());
        $map   = array(
            'token'   => $token,
            'user_id' => $user['user_id'],
        );
        if (!$this->data($map)) {
            // var_dump($this->getError());
            return false;
        }
        $this->save();
        return $token;
    }
    /**
     * @param string $longitude <经度>
     * @param string $latitude <纬度>
     *刷新坐标
     */
    public function updatePoint($user, $longitude, $latitude)
    {
        $map = array(
            'longitude' => $longitude,
            'latitude'  => $latitude,
        );
        $res = $this->where(array('user_id' => $user['user_id']))->create($map);
        // dump($res);
        // dump($user);
        // die();
        if (!$res) {
            return false;
        }
        $this->save();
        return ture;
    }

}
