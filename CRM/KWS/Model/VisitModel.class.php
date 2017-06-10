<?php
namespace KWS\Model;

use Think\Model;

class VisitModel extends Model
{
    /**获取备注
     * @param $custId
     */
    public function getVisitRemark($custId)
    {

        $map['CustId'] = $custId;
        $map['Remark'] != '';
        $res = M('Visit')->field('Remark')->where($map)->order("InsertTime desc")->select();
        $remark = [];
        foreach($res as $k=>$v){

            !empty($v['remark'])&&$remark[] = $v['remark'];
        }
        $proRemark = M('customer')->where(['CustId'=>$custId])->getField('Remark');
        $remarks = implode('/',$remark);
        return $remarks;
    }

    /**
     * 每次的回访时间+回访状态
     *
     */
    public function getVisitTimeAndStatus($custId)
    {
        $map['CustId'] = $custId;
        $res = M('Visit')->field('Status,InsertTime')->where($map)->order("InsertTime desc")->select();
        $status = [];
        $statusType = D('StatusType')->getAllStatus();
        foreach($res as $k=>$v){
            $v['status']&&$status[] = $statusType[$v['status']].'回访时间'.$v['inserttime'];
        }
        // $proStatus = M('customer')->where(['CustId'=>$custId])->getField('Status');
        if(!empty($status)){
            $statuss = implode('/',$status);
        }else{
            //$statuss = $status;
        }
        return $statuss;
    }
}