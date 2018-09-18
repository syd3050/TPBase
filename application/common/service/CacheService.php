<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/3/7
 * Time: 10:22
 */

namespace app\common\service;


use app\common\model\Type;
use think\Cache;
use think\Exception;
use think\Log;

class CacheService
{
    public static function getUid()
    {
        $user = UserService::getLoginUser();
        $uid = null;
        $user && $uid = $user->id;
        return $uid;
    }

    public static function setPic($src,$original='')
    {
        try{
            //获取活动对应的图片缓存，并缓存当前图片
            $cache = Cache::get(Type::CK_RSC_CACHE.self::getUid());
            $cache[$src] = $original;
            Cache::set(Type::CK_RSC_CACHE.self::getUid(),$cache);
        }catch (Exception $e) {
            Log::error("In ".__CLASS__."::".__FUNCTION__.",异常信息：".$e->getMessage());
            return false;
        }
        return true;
    }

    public static function getPic()
    {
        try{
            //获取该用户图片缓存
            return Cache::get(Type::CK_RSC_CACHE.self::getUid());
        }catch (Exception $e) {
            Log::error("In ".__CLASS__."::".__FUNCTION__.",异常信息：".$e->getMessage());
            return null;
        }
    }

    public static function updatePic($arr)
    {
        try{
            return Cache::set(Type::CK_RSC_CACHE.self::getUid(),$arr);
        }catch (Exception $e) {
            Log::error("In ".__CLASS__."::".__FUNCTION__.",异常信息：".$e->getMessage());
            return null;
        }
    }

    /**
     * @param $arr
     * @param null|string $seq 业务序列号
     * @return bool
     */
    public static function setCurrentPic($arr,$seq = null)
    {
        try{
            if($seq) {
                //更新该业务对应所有可用资源缓存
                $cache = Cache::get(Type::CK_CUR_CACHE.self::getUid());
                $cache[$seq] = $arr;
                return Cache::set(Type::CK_CUR_CACHE.self::getUid(),$cache);
            }
            return Cache::set(Type::CK_CUR_CACHE.self::getUid(),$arr);
        }catch (Exception $e) {
            Log::error("In ".__CLASS__."::".__FUNCTION__.",异常信息：".$e->getMessage());
            return false;
        }
    }

    /**
     * @param null|string $seq 业务序列号
     * @return mixed|null
     */
    public static function getCurrentPic($seq = null)
    {
        try{
            $cache = Cache::get(Type::CK_CUR_CACHE.self::getUid());
            //获取该业务对应所有可用资源缓存
            $seq && isset($cache[$seq]) ? $cache = $cache[$seq] : $cache = null;
            return $cache;
        }catch (Exception $e) {
            Log::error("In ".__CLASS__."::".__FUNCTION__.",异常信息：".$e->getMessage());
            return null;
        }
    }

    public static function clearScenePic($seq)
    {
        try{
            return Cache::rm(Type::CK_RSC_CACHE.$seq);
        }catch (Exception $e) {
            Log::error("In ".__CLASS__."::".__FUNCTION__.",异常信息：".$e->getMessage());
            return false;
        }
    }

    public static function getPreview($seq)
    {
        $cache = Cache::get("preview_$seq");
        if(empty($cache))
            return null;
        return unserialize($cache);
    }

    public static function rmPreview($seq)
    {
        return Cache::rm("preview_$seq");
    }

}