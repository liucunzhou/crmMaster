<?php
namespace KWS\Model;

use Think\Model;

class StatusTypeModel extends Model
{
    /**
     * 获取所有有效状态
     * @return mixed
     */
    public function getAllStatus()
    {
        $status = $this->where(['display'=>1])->getField('SID,Sname');
        return $status;
    }
}