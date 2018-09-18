<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/12/5
 * Time: 19:17
 */

namespace app\common\model;


class Type
{
    //status
    const SUCCESS        = 0;
    const DB_ERROR       = 1;
    const NOT_FOUND      = 2;
    const PWD_ERROR      = 3;
    const PARAM_ERROR    = 4;
    const DISABLE        = 5;
    const SIZE_ERROR     = 6;
    const EXT_ERROR      = 7;
    const UNKNOWN_ERROR  = 8;
    const NEED_LOGIN     = 9;
    const FAIL           = 10;



    const REGISTER   = 'register';
    const CODE_IMAGE = 'code_image';
    const CODE_SMS   = 'ifc';
    const RELEASE    = 'release_url';
    const RELEASE_HOST    = 'release_host';


    const ROLE_ADMIN = 1;
    const ROLE_USER  = 0;

    const CK_RSC_CACHE = 'ck_rsc_cache_'; //Cache缓存资源信息的key
    const CK_CUR_CACHE = 'ck_cur_cache_'; //Cache缓存当前活动资源的key

    //资源类型
    const R_PIC = 1; //图片

}