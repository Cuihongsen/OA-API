<?php
namespace Home\Model;

/**
 *
 */
/**
 * Class ReturnCodeModel
 * @package Home\Model
 */
class ReturnCodeModel
{
    /**
     * 全局的状态码
     * @var array
     */
    protected static $http_codes = array(
        200 => 'OK',
        400 => 'Bad Request',
        401 => '未经授权',
        404 => 'Not Found',
        500 => '内部服务器错误',
        601 => 'Invalid access token',


        
        // 401 => 'Unauthorized',
        // 403 => 'Forbidden',
        // 500 => 'Internal Server Error',
        // 602 => 'Invalid or Expired confirmation code',
        603 => 'The phone or password you have provided does not match our records',
        604 => 'This phone already exists',
        607 => 'The phone number is not exist',
        // 605 => 'File can not be accessed',
        // 606 => 'General configuration error',
        // 608 => 'The version is too low',

        // 200 => '确定',
        // 400 => '坏要求',
        403 => '禁止403，',
        // 404 => '未找到',
        // 601 => '无效访问令牌',
        602 => '无效或过期的确认代码',
        // 603 => '您提供的电话或密码与我们的记录不匹配',
        // 604 => '这个电话已经存在',
        605 => '无法访问文件',
        606 => '一般配置错误',
        // 607 => '电话号码是不存在的',
        608 => '版本太低',

    );

    /**
     * @param $code
     * @param $message
     * @param $contents
     * @return array
     */
    public static function send($code, $message, $contents)
    {
        if (null == $message) {
            $message = static::$http_codes[$code];
        }
        $send = array(
            'code'    => $code,
            'message' => $message,
        );
        if (null !== $contents) {
            $send['contents'] = $contents;
        }
        return $send;
    }
}
