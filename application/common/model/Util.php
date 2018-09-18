<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/12/5
 * Time: 9:48
 */
namespace app\common\model;

use think\Config;
use think\Exception;
use think\Log;

class Util
{
    /**
     * CURL 发送请求
     * @param string $url     地址
     * @param array $headers  头信息
     * @param array $postData post请求数据
     * @param bool  $header_response  是否需要返回头头信息
     * @param bool  $status  是否仅需获取状态码
     * @return mixed
     */
    public static function sendRequest($url,$headers,$postData = [],$header_response = false,$status = false)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        if($status) {
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_exec($ch);
            $curl_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            return ['status'=>$curl_code];
        }else{
            //设置头文件的信息作为数据流输出
            curl_setopt($ch, CURLOPT_HEADER, $header_response);
        }

        //设置获取的信息以文件流的形式返回，而不是直接输出。
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        //设置Header
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        //curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 60);
        if(!empty($postData))
        {
            //这是post请求，post的变量
            curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        }
        $response = curl_exec($ch);
        $result = [];
        if($header_response)
        {
            // 获得响应结果里的：头大小
            $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
            // 根据头大小去获取头信息内容
            $responseHead = substr($response, 0, $headerSize);
            $response = substr($response,$headerSize);

            //获取返回的Cookie
            $cookie = '';
            $headArr = explode("\r\n", $responseHead);
            foreach ($headArr as $loop) {
                $pos = strpos($loop, "Cookie");
                if($pos !== false){
                    $tmp = substr($loop,$pos+7);
                    $tmp .= ';';
                    $cookie .= $tmp;
                }
            }
            if(!empty($cookie))
            {
                $result['cookie'] = rtrim($cookie,';');
            }
            $result['header'] = $responseHead;
        }
        curl_close($ch);
        $result['content'] = $response;
        return $result;
    }

    public static function checkUrl($urls)
    {
        $mh = curl_multi_init();
        $conn = array();
        $res = array();
        foreach ($urls as $i => $url) {
            $conn[$i] = curl_init($url);
            curl_setopt($conn[$i],CURLOPT_RETURNTRANSFER,true);
            curl_setopt($conn[$i], CURLOPT_TIMEOUT, 2);
            curl_setopt($conn[$i], CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($conn[$i], CURLOPT_SSL_VERIFYHOST, false);
            curl_multi_add_handle($mh,$conn[$i]);
        }

        do {
            $mrc = curl_multi_exec($mh,$active);
        } while ($mrc == CURLM_CALL_MULTI_PERFORM);

        while ($active && $mrc == CURLM_OK) {
            //select可防止CPU空转，在select函数内部会监听FD，当在FD上有数据时才读取，其他情况下休眠
            if (curl_multi_select($mh) === -1) {
                //当select返回-1时，主动休眠100微秒再检测
                usleep(100);
            }
            //有数据进入后，在各个请求中不断接受数据
            do {
                $mrc = curl_multi_exec($mh, $active);
            } while ($mrc == CURLM_CALL_MULTI_PERFORM);
        }

        foreach ($urls as $i => $url) {
            $status = curl_getinfo($conn[$i], CURLINFO_HTTP_CODE);
            //资源未就位，访问失败
            if($status != '200' && $status != '304') {
                Log::error("url:$url");
                Log::error("status:$status");
                $res[] = $url;
            }
            curl_close($conn[$i]);
        }
        return $res;
    }

    public static function paramParser($param,$delimiter)
    {
        $r = '';
        foreach ($param as $value)
        {
            $poss = strpos($value,$delimiter);
            if($poss !== false)
            {
                $ck = explode('_',$value);
                if(count($ck) == 2)
                {
                    $r .= $ck[0].'='.$ck[1].';';
                }
            }
        }
        return $r;
    }

    public static function recurse_copy($src,$dest)
    {
        try{
            $dir = opendir($src);
            if(!is_dir($dest)) {
                mkdir($dest,0777,true);
            }
            while(false !== ( $file = readdir($dir)) ) {
                if ( $file == '.'  ||  $file == '..' ) {
                    continue;
                }
                if ( is_dir($src . '/' . $file) ) {
                    self::recurse_copy($src.'/'.$file,$dest.'/'.$file);
                } else {
                    copy($src.'/'.$file,$dest.'/'.$file);
                }
            }
            closedir($dir);
        }catch (Exception $e) {
            Log::error(__CLASS__.'::'.__FUNCTION__.",复制异常，原因：{$e->getMessage()}");
            throw new  Exception('复制文件异常');
        }
    }

    public static function copyPic($picArr,$dest)
    {
        try{
            $dir = ROOT_PATH . 'public';
            foreach ($picArr as $pic) {
                if(empty($pic)) {
                    continue;
                }
                $last = strrpos($pic,'/');
                $path = substr($pic,0,$last+1);
                $name = substr($pic,$last+1);
                $path = $dest.$path;
                if(!is_dir($path)) {
                    mkdir($path,0777,true);
                }
                copy($dir.$pic,$path.$name);
            }
        }catch (Exception $e) {
            Log::error(__CLASS__.'::'.__FUNCTION__.",复制异常，原因：{$e->getMessage()}");
            throw new  Exception('复制文件异常');
        }
        return true;
    }

    /**
     * 获取活动发布目录
     * @param $englishName
     * @return mixed|string
     * @throws Exception
     */
    public static function getReleasePath($englishName)
    {
        //从配置文件中获取活动生成文件的存放目录
        $release_path = Config::get('release_path');
        $release_path = rtrim($release_path,'/');
        if(!is_dir($release_path)) {
            Log::error("发布活动的目录{$release_path}不存在,无法生成活动相关文件，活动名称{$englishName}");
            throw new Exception('发布活动的目录不存在，无法生成活动相关文件');
        }
        $release_path .= DS.$englishName;
        return $release_path;
    }

    public static function recurse_rm($path)
    {
        if(!is_dir($path) && is_file($path))
        {
            unlink($path);
        }
        $dir = opendir($path);
        while(false !== ( $file = readdir($dir)) ) {
            if (( $file != '.' ) && ( $file != '..' )) {
                if ( is_dir($path . '/' . $file) ) {
                    self::recurse_rm($path . '/' . $file);
                }
                else {
                    unlink($path. '/' . $file);
                }
            }
        }
        closedir($dir);
        rmdir($path);
    }

    /**
     * @deprecated
     * @param $name
     * @return bool
     */
    public static function parse_log($name)
    {
        $log = fopen($name, "r");
        $files = array();
        $i = 0;
        //输出文本中所有的行，直到文件结束为止。
        while(!feof($log))
        {
            $files[$i]= fgets($log);//从文件指针中读取一行
            $i++;
        }
        fclose($log);

        $exists = false;
        foreach ($files as $k=>$file) {
            $file = str_replace(PHP_EOL, '', $file);
            $url = Config::getEnv(ENVIRONMENT,'url',Type::RELEASE_HOST).$file;
            $count = 0;
            do {
                $exists = self::access_url($url);
                //usleep(5000);
                $count++;
            } while ($count < 100 && !$exists);
        }
        return $exists;
    }

    public static function access_url($url)
    {
        $result = self::sendRequest($url,[],[],false,true);
        if(!isset($result['status'])) {
            return false;
        }
        $status = $result['status'];
        if($status == '200' || $status == '304') {
            return true;
        }
        return false;
    }

    public static function scan_all ($dir,$layer=1){
        if(!is_dir($dir)) {
            return false;
        }
        if($layer > 100) {
            return false;
        }
        $files = array();
        $handle = opendir($dir);
        if($handle){
            while(($fl = readdir($handle)) !== false) {
                $temp = $dir.DIRECTORY_SEPARATOR.$fl;
                if($fl == '.' || $fl == '..') {
                    continue;
                }
                if(is_dir($temp)) {
                    $files = array_merge($files,self::scan_all($temp,++$layer));
                }else{
                    $files[] = $temp;
                }
            }
        }
        closedir($handle);
        return $files;
    }

    /**
     * @param array $dirArr
     * @param array $files
     * @return array|\Closure
     */
    public static function scanFiles($dirArr,$files) {
        if(empty($dirArr)) {
            return $files;
        }
        foreach ($dirArr as $k=>$dir) {
            $handle = opendir($dir);
            if($handle){
                unset($dirArr[$k]);
                while(($fl = readdir($handle)) !== false) {
                    $temp = $dir . DIRECTORY_SEPARATOR . $fl;
                    if (is_dir($temp) && $fl != '.' && $fl != '..') {
                        $dirArr[] = $temp;
                    } else if($fl != '.' && $fl != '..'){
                        $files[] = $temp;
                    }
                }
            }
        }

        return function() use($dirArr, $files) {
            return self::scanFiles($dirArr, $files);
        };
    }

    /**
     * @param array $param 需要校验的数组，必需为一维数组
     * @param string|array $keys 数组中是否包含该key
     * @return bool 成功返回true
     */
    public static function paramVerify($param,$keys)
    {
        if(empty($param))
            return false;
        $kernel = $param;
        $element = array_pop($param);
        //格式不对
        if(is_array($element)) {
            return false;
        }
        if(is_array($keys)) {
            foreach ($keys as $key) {
                if(!isset($kernel[$key])){
                    return false;
                }
            }
        }
        if(!$kernel[$keys]) {
            return false;
        }
        return true;
    }
}