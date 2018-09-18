<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/3/12
 * Time: 10:12
 */

namespace app\common\model;


use app\common\service\CacheService;
use app\model\Resourceuser;
use think\Cache;
use think\Db;
use think\Exception;
use think\Log;
use think\Session;

/**
 * 图片上传接口交互规则：
 * 1.需要立即将图片信息存到数据库的图片：参数中带type
 *    1.1 图片库公共图片，type值为1
 * 2.只需缓存图片生成路径，等后续用户保存信息时再存数据库的图片，可传任意参数
 * Class Picture
 * @package app\common\model
 */
class Picture extends ResourceFile
{
    /**
     * 需要保存到数据库的图片类型  类型type=>表名，所有的表必需有
     * 'src','resource_type','createtime'三个字段
     * 如果需要扩展持久化的图片类型，只需要配置这个数组即可
     * @var array
     */
    public static $needTable = [
        1 =>'resourceuser',
    ];
    public $type;

    public function __construct($file, $rule=[], $others=null)
    {
        $this->type = isset($others['type']) ? intval($others['type']) : null;
        $this->dest = SCENE_PATH;
        //有type参数的图片都保存到GALLERY_PATH中
        empty($this->type) || $this->dest = GALLERY_PATH;
        parent::__construct($file, $rule, $others, $this->dest);
    }

    public function saveInfo()
    {
        if(empty($this->type)) {
            return $this->cache();
        }
        return $this->persistent();
    }

    public function getProperty()
    {
        $size = getimagesize($this->absolutePath);
        $r = array(
            'width'  => $size[0],
            'height' => $size[1],
            'src'    => $this->relativePath,
        );
        return $r;
    }

    /**
     * 图片信息立即存到数据库
     */
    private function persistent()
    {
        if(empty(self::$needTable[$this->type])) {
            return ['status'=>Type::FAIL];
        }
        $table = self::$needTable[$this->type];
        try{
            Db::table($table)->insert(['src'=>$this->relativePath,
                'resource_type'=>$this->type,
                'createtime'=> date("Y-m-d H:i:s" ,time())]
            );
        }catch (Exception $e) {
            Log::error("In ".__CLASS__."::".__FUNCTION__.",异常信息：".$e->getMessage());
            return ['status'=>Type::DB_ERROR,'msg'=>'数据库错误'];
        }
        return ['status'=>Type::SUCCESS];
    }

    /**
     * 图片信息立即存到缓存
     * @return array
     */
    private function cache()
    {
        CacheService::setPic($this->relativePath,$this->originalName) ? $r = Type::SUCCESS : $r = Type::FAIL;
        return ['status'=>$r];
    }

}