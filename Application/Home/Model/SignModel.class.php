<?php
namespace Home\Model;

use Think\Model;

/**
 * Class UserModel
 * @package Home\Model
 */
class SignModel extends Model
{
    protected $_validate = array(
        // array('verify', 'require', '验证码必须！'), //默认情况下用正则进行验证
        // array('year,month', '', '手机号码已存在', 0, 'unique', 3), // 在新增的时候验证name字段是否唯一
        // array('username', "/^1[3|4|5|7|8][0-9]{9}$/", '手机号码格式错误', 0, 'regex', 3), // 当值不为空的时候判断是否在一个范围内
        array('year,month,date,u_id,operator', 'check', '请勿重复操作', 1, 'unique', 3),
        // array('repassword', 'password', '确认密码不正确', 0, 'confirm'), // 验证确认密码是否和密码一致
        // array('password', 'checkPwd', '密码格式不正确', 0, 'function'), // 自定义函数验证密码格式
    );
    public function option($u_id, $option, $operator = null)
    {
        if (!$operator) {
            $operator = $u_id;
        }
        $map = array(
            'u_id'     => $u_id,
            'year'     => date('Y', time()),
            'month'    => date('m', time()),
            'date'     => date('d', time()),
            'operator' => $operator,
        );
        switch ($option) {
            case 'late':
                $map['state'] = 'sign,late';
                if ($this->create($map)) {
                    $this->add();
                    return 1;
                } else {
                    // return -1;
                     $map['state'] = '|1|16';
                }
                break;
            case 'leaveFull':
                $map['state'] = '|2';
                break;

            case 'leaveHalf':
                $map['state'] = '|4';
                break;

            case 'signOut':
                $map['state'] = '|8';
                break;

            case 'sign':
                $map['state'] = 'sign';
                if ($this->create($map)) {
                    $this->add();
                    return 1;
                } else {
                    $map['state'] = '|16';
                    // return -1;
                }
                break;
            default:
                return false;
                break;

        }
        $sql = "UPDATE oa_sign SET state = state " . $map['state'] . " WHERE `year` = " . $map['year'] . " AND `month` = " . $map['month'] . " AND `date` = " . $map['date'] . " AND u_id =" . $map['u_id'];
        // var_dump($sql);
        // die();
        $res = $this->execute($sql);
        if (!$res) {
            return -1;
        }
        return 1;
    }

    public function ProcessData($input)
    {
        $res = array();
        foreach ($input as $key => $value) {
            $res[$key]['date'] = $value['year'] . '-' . $value['month'] . '-' . $value['date'];
            $state             = explode(',', $value['state']);

            // 旷工
            if ($value['absenteeism']) {
                $res[$key]['state'][] = 'absenteeism';
            }
            
//请一天假
            if (in_array('leaveFull', $state)) {
                $res[$key]['state'][] = 'leaveFull';
            }
//半天假正常
            if (in_array('leaveHalf', $state) && in_array('sign', $state)) {
                $res[$key]['state'][] = 'leaveHalf';
                if (in_array('late', $state)) {
                    $res[$key]['state'][] = 'late';
                }
            }
//半天假正常
            if (in_array('leaveHalf', $state) && in_array('signOut', $state)) {
                $res[$key]['state'][] = 'leaveHalf';
                if (in_array('late', $state)) {
                    $res[$key]['state'][] = 'late';
                }
                $res[$key]['state'][] = 'signOut';
            }
//半天异常
            if (in_array('leaveHalf', $state) && count($state) == 1) {
                $res[$key]['state'][] = 'leaveHalf';
                $res[$key]['state'][] = 'notSignOut';
            }
//签到，或迟到
            if (in_array('sign', $state) && in_array('signOut', $state)) {
                if (in_array('late', $state)) {
                    $res[$key]['state'][] = 'late';
                } else {
                    $res[$key]['state'][] = 'sign';

                }
                 $res[$key]['state'][] = 'signOut';
            }
//没签退
            if (in_array('sign', $state) && (!in_array('signOut', $state)) && count($state) == 1) {
                if (in_array('late', $state)) {
                    $res[$key]['state'][] = 'late';
                } else {
                    $res[$key]['state'][] = 'sign';
                }

                $res[$key]['state'][] = 'notSignOut';
            }

        }
        return $res;
    }
}
