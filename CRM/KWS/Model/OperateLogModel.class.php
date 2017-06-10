<?php
namespace KWS\Model;

use Think\Model;

class OperateLogModel extends Model
{
    public function addLog($data)
    {

        return $this->add($data);
    }
}