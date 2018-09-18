<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/3/17
 * Time: 10:56
 */

namespace app\behavior;

use app\common\model\Type;
use app\common\service\UserService;


class CheckLogin extends Behavior
{
    /**
     * 登录校验例外情况配置
     * 记录不需要登录校验的 控制器=>方法，方法数组为空表明该控制器下所有方法都需要校验
     * @var array
     */
    private $except = [
        'Scene'=>[
            'login','preshow','preview','register','doRegister',
            'getCodeImg','getIdentifyingCode'
        ],
    ];

    public function __construct()
    {
        parent::__construct();
        $this->lower($this->except);
    }

    /**
     * @param $param
     * @return bool 校验通过返回true，否则返回false
     */
    private function check($param)
    {
        //已经登录无需校验
        if(!UserService::isGuest()) return true;
        //该控制器不在校验规则中，不需要校验
        if (!isset($this->except[$this->controller])) return true;
        //控制器该方法已被排除，不需要校验
        if (in_array($this->action,$this->except[$this->controller])) return true;
        //校验失败，需要登录
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
        if(!$this->check($params)) {
            request()->isAjax() && $this->result([],Type::NEED_LOGIN, '还未登录!');
            $this->redirect('xxx/login');
        }
        return true;
    }

}