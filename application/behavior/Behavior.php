<?php
namespace app\behavior;

use traits\controller\Jump;

class Behavior
{
    use Jump;

    public $controller = null;
    public $action = null;

    public function __construct()
    {
        $this->controller = strtolower(request()->controller());
        $this->action = strtolower(request()->action());
    }

    /**
     * 将配置中的controller及action转换为小写
     * @param $arr
     * @param bool $key 是否只处理数组中的key，因为key可能对应了action
     * @return array
     */
    public function lower(&$arr,$key=false)
    {
        $arr = array_change_key_case($arr);
        if($key) {
            return $arr = array_map('array_change_key_case',$arr);
        }
        foreach ($arr as $k=>$v) {
            foreach ($v as $kk=>$vv){
                $v[$kk] = strtolower($vv);
            }
            $arr[$k] = $v;
        }
        return $arr;
    }
}