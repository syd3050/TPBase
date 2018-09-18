<?php
namespace app\behavior;

use app\common\model\Type;
use app\common\service\UserService;


class CheckPrivilege extends Behavior
{
    /**
     * 操作权限配置
     * controller => [
     *      action1 => [role1,role2...],
     *      action2 => [role1,role2...]
     * ]
     * @var array
     */
    public $privilege = [
        'xxx' => [
            'addUser'    => [Type::ROLE_ADMIN,],
            'add'        => [Type::ROLE_ADMIN,],
            'resetPwd'   => [Type::ROLE_ADMIN,],
            'doResetPwd' => [Type::ROLE_ADMIN,],
            'enable'     => [Type::ROLE_ADMIN,],
        ],
    ];

    public function __construct()
    {
        parent::__construct();
        $this->lower($this->privilege,true);
    }

    /**
     * @param $param
     * @return bool 没有权限返回false，否则返回true
     */
    public function check($param)
    {
        //不需要校验，直接返回true，表明拥有权限
        if(!isset($this->privilege[$this->controller][$this->action])) return true;
        //需要校验权限，且拥有权限，校验通过
        if(in_array($param['role'],$this->privilege[$this->controller][$this->action])) return true;
        //其他情况，返回false，没有权限
        return false;
    }

    /**
     * 入口函数
     * !!!!!!!!返回值不表明验证是否通过，而是表明是否该终止在该监听标签的所有后续监听函数!!!!!!!!
     * @param $params
     * @return bool false则中断后续监听函数的执行，true则继续
     */
    public function run(&$params)
    {
        if(UserService::isGuest()) return true;
        $role = UserService::getLoginUser()->role;
        $params['role'] = $role;
        if(!$this->check($params)) {
            request()->isAjax() && $this->result([],Type::FAIL, '没有权限添加用户!');
            $this->redirect('xxx/forbidden');
        }
        unset($params['role']);
        return true;
    }

}