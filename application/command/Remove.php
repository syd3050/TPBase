<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/3/5
 * Time: 15:03
 */

namespace app\command;

use think\Cache;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\Log;

class Remove extends Command
{
    protected function configure()
    {
        $this->setName('remove')->setDescription('Remove all pictures that are not needed.');
    }

    protected function execute(Input $input, Output $output)
    {
        //在该目录下操作
        $begin = $this->getMillisecond();
        $this->log('Begin.');
        
        $end = $this->getMillisecond();
        $cost = $end-$begin;
        $this->log("Done!Cost time(ms):".$cost.".");
        $this->log("-----------------------------");
    }


    private function getMillisecond() {
        list($t1, $t2) = explode(' ', microtime());
        return (float)sprintf('%.0f',(floatval($t1)+floatval($t2))*1000);
    }

    private function log($msg)
    {
        Log::info($msg);
    }
}