<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/12/5
 * Time: 11:28
 */
namespace app\common\service;

use app\common\model\Type;
use app\model\User;
use think\Cache;
use think\Cookie;
use think\Db;
use think\Exception;
use think\Log;
use think\Session;

class UserService
{

    const DISABLE = 0;
    const ENABLE = 1;
    const FORBID = 2;

    /**
     * 注册用户
     * @param array $params  User表的字段名及对应值
     * @return array
     */
    public static function register($params)
    {
        if(!isset($params['username']) || !isset($params['password']) )
        {
            return ['status'=>Type::PARAM_ERROR,'msg'=>'缺少必要参数:username或password!'];
        }
        $params['password'] = password_hash($params['password'],PASSWORD_DEFAULT);
        $params['createtime'] = date("Y-m-d H:i:s" ,time());
        $params['status'] = 1;
        $params['gid'] = 1;  //默认分组1
        $params['seq'] = substr($params['password'],8,12);
        $user = new User($params);
        try{
            $r = $user->save();
            if($r) {
                return ['status'=>Type::SUCCESS,'msg'=>'注册成功！','id'=>$user->seq];
            }
        }catch (Exception $exception){
            Log::error($exception->getMessage());
            return ['status'=>Type::DB_ERROR,'msg'=>'注册失败！数据库错误！'];
        }

        return ['status'=>Type::DB_ERROR,'msg'=>'注册失败！数据库错误！'];
    }

    /**
     * 修改密码
     * @param string $seq   用户序列号
     * @param string $password 用户在数据库中的加密密码
     * @param string $originPwd 用户旧的明文密码
     * @param string $newPwd 新的明文密码
     * @return array
     */
    public static function updatePwd($seq,$password,$originPwd,$newPwd)
    {
        if(!password_verify($originPwd , $password)) {
            return ['status'=>Type::PARAM_ERROR,'msg'=>'原密码输入错误'];
        }
        $newPwd = password_hash($newPwd,PASSWORD_DEFAULT);
        if(self::update(['seq'=>$seq],['password'=>$newPwd])) {
            //清除session强制重新登录
            self::logout();
            return ['status'=>Type::SUCCESS];
        }
        return ['status'=>Type::FAIL,'msg'=>'修改失败，可能没有该用户'];
    }

    private static function pwdErrorNum($username)
    {
        $num = Cache::get($username.'_login_num');
        if(!$num) {
            $num = 0;
            Cache::set($username.'_login_num',$num,172800);
        }
        return $num;
    }

    private static function checkStatus($status)
    {
        switch ($status) {
            case self::DISABLE:
                return ['status'=>self::DISABLE,'msg'=>'该账户已被禁用！'];
            case self::FORBID:
                return ['status'=>self::DISABLE,'msg'=>'输入密码错误次数达到30次，该账户已被禁用！'];
        }
        return ['status'=>self::ENABLE,'msg'=>''];
    }

    private static function query($whereArr,$column=null)
    {
        $field = key($whereArr);
        $value = $whereArr[$field];
        if($column) {
            return Db::table('user')->where($field,$value)->column(implode(',',$column));
        }
        return Db::table('user')->where($field,$value)->select();
    }

    public static function rememberMe($rememberMe, $username, $password)
    {
        if(!empty($rememberMe) && intval($rememberMe)==1) {
            Cookie::set('passport',base64_encode($username.'|'.$password));
        }
    }

    public static function verifyPwd($username,$password,$callback=null)
    {
        $users = self::query(['username'=>$username]);
        if(empty($users))
            return ['status'=>Type::PWD_ERROR,'msg'=>'验证失败！用户名或密码错误！'];
        $user = $users[0];
        $r = self::checkStatus($user['status']);
        if($r['status'] != self::ENABLE)
            return $r;
        $right = password_verify($password,$user['password']);
        is_callable($callback) && call_user_func_array($callback,[$user,$right]);
        if($right)
            return ['status'=>Type::SUCCESS,'id'=>$user['seq'],'username'=>$user['username'],'role'=>$user['role']];
        return ['status'=>Type::FAIL, 'msg'=>'用户名或密码错误！'];
    }

    /**
     * 用户登录
     * @param $username
     * @param $password
     * @param $rememberMe
     * @return array
     */
    public static function login($username,$password,$rememberMe)
    {
        if(empty($username) || empty($password)) {
            return ['status'=>Type::PARAM_ERROR,'msg'=>'缺少必要参数:username或password！'];
        }
        $num = self::pwdErrorNum($username);
        if($num > 2) {
            self::update(['username'=>$username],['status'=>self::FORBID]);
            return ['status'=>Type::PWD_ERROR,'msg'=>'密码错误次数达到30次，账号被禁用！'];
        }
        $r = self::verifyPwd($username,$password, function($user,$right) use ($rememberMe,$num){
            if(!$right) {
                Cache::set($user['username'].'_login_num',++$num);
                return;
            }
            self::setPassport($user);
            self::rememberMe($rememberMe,$user['username'],$user['password']);
            Cache::rm($user['username'].'_login_num');
        });
        return $r;
    }

    /**
     * 重置密码
     * @param $username
     * @return int|string
     */
    public static function reset($username,$password)
    {
        $password = password_hash($password,PASSWORD_DEFAULT);
        return self::update(['username'=>$username],['password'=>$password]);
    }

    /**
     * 修改用户信息
     * @param $username
     * @return bool
     */
    public static function enable($username)
    {
        empty($username) || self::update(['username'=>$username],['status'=>self::ENABLE]);
        Cache::rm($username.'_login_num');
        return true;
    }

    public static function update($whereArr,$param)
    {
        $field = key($whereArr);
        $value = $whereArr[$field];
        return Db::table('user')->where($field,$value)->update($param);
    }

    private static function setPassport($user)
    {
        Session::set('seq',$user['seq']);
        Session::set('id',$user['id']);
        Session::set('username',$user['username']);
        Session::set('password',$user['password']);
        Session::set('role',$user['role']);
    }

    /**
     * “记住我”登录
     * @param string $username 用户名
     * @param string $password 加密后的密码
     * @return bool 成功返回true，失败返回false
     */
    public static function rememberLogin($username,$password)
    {
        $users = self::query(['username'=>$username]);
        if(empty($users) || $users[0]['password'] != $password)
            return false;
        self::setPassport($users[0]);
        return true;
    }

    public static function logout()
    {
        Session::clear();
        Cookie::delete('passport');
    }

    public static function isGuest()
    {
        $seq = Session::get('seq');
        //die($seq);
        if($seq)
            return false;
        if(Cookie::has('passport')) {
            //查看是否有Cookie，如果有，则判断其中的用户名密码是否正确
            $passport = Cookie::get('passport');
            $passport = base64_decode($passport);
            list($username,$password) = explode('|',$passport);
            return !self::rememberLogin($username,$password);
        }
        return true;
    }

    /**
     * 获取已登录用户信息
     * @return User|null
     */
    public static function getLoginUser()
    {
        if(self::isGuest())
        {
            return null;
        }
        $user = new User();
        $user->id       = Session::get('id');
        $user->seq      = Session::get('seq');
        $user->username = Session::get('username');
        $user->password = Session::get('password');
        $user->role     = Session::get('role');
        return $user;
    }
}