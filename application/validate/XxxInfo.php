<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/3/9
 * Time: 10:25
 */

namespace app\validate;


use think\Db;
use think\Validate;

class XxxInfo extends Validate
{
    public $seq;
    public $english;
    public $title;
    public $scontent;
    public $content;
    public $name;
    public $platform;
    public $backgroundColor;
    public $modestr;
    public $coms='';
    public $share_pic;
    public $pageNum = 1;

    protected $rule =   [
        'seq'       => 'require|max:12',
        'name'      => 'require|max:30',
        'english'   => 'require|max:50',
        'title'     => 'require|max:30',
        'scontent'  => 'require|max:36',
        'platform'  => 'require|number',
        'share_pic' => 'require|max:60',
    ];

    protected $message  =   [
        'seq.require'   => '序列号seq必需',
        'seq.max'       => '序列号seq最多不能超过12个字符',
        'name.require' => '名称必需',
        'name.max'     => '名称最多不能超过30个字符',
        'english.require'   => '英文名必需',
        'english.max'   => '英文名最多不能超过50个字符',
        'title.require' => '标题必需',
        'title.max'     => '标题最多不能超过30个字符',
        'scontent.require'   => '分享内容必需',
        'scontent.max'       => '分享内容最多不能超过36个字符',
        'platform.require'   => '平台ID必需',
        'platform.number'    => '平台ID必需是数字',
        'share_pic.require'  => '分享图标必需',
        'share_pic.max'      => '分享图标最多不能超过60个字符',
    ];

    protected $scene = [
        'save'   =>  ['seq','name','english','title','scontent','platform','share_pic'],
        'common' =>  ['seq'],
        'english'=>  ['seq','english'],
    ];

}