<?php
namespace app\common\model;


use think\File;

class ResourceFile
{
    const STATUS = 'status';
    const MSG = 'msg';
    const ORIGINAL_NAME = 'filename';
    const SAVE_NAME = 'save_name';

    /**
     * @var File $file
     */
    protected $file;
    protected $dest;
    protected $absolutePath;
    protected $relativePath;
    protected $originalName;
    protected $rule;
    protected $others;

    public function __construct($file, $rule=[], $others=null, $dest=SCENE_PATH)
    {
        $this->file   = $file;
        $this->dest = $dest;
        $this->rule = $rule;
        $this->others = $others;
    }

    public function upload()
    {
        //如果新生成的文件目录不存在则新建
        if(!is_dir($this->dest)) {
            mkdir($this->dest);
        }
        $saveName = substr(md5(microtime(true)),0,18);
        $this->originalName = $this->file->getInfo('name');
        $info = $this->file->validate($this->rule)->move($this->dest, $saveName);
        if(!$info) {
            return [self::STATUS =>Type::FAIL, self::MSG =>$this->file->getError()];
        }
        //成功上传后 获取上传信息
        $saveName = str_replace('\\','/',$info->getSaveName());
        $this->absolutePath = $this->dest. $saveName;
        $this->relativePath = substr($this->absolutePath, strpos($this->absolutePath,DS.'upload'));
        $this->absolutePath = str_replace('\\','/',$this->absolutePath);
        $this->relativePath = str_replace('\\','/',$this->relativePath);
        return [self::STATUS=>Type::SUCCESS, self::SAVE_NAME=>$saveName, self::ORIGINAL_NAME=>$this->originalName];
    }

    public function saveInfo(){return [];}

    public function getProperty(){return [];}

    protected function check($keys) {
        return Util::paramVerify($this->others,$keys);
    }

}