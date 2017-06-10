<?php
namespace KWS\Model;

use Think\Model;

/**
 * 邀约咨费模型
 */
class PromotionModel extends Model
{

    protected $patchValidate = true;

    protected $_validate = [
        ['UtilityTime', 'require', '使用时间不能为空'],
        ['Storeid','checkStoreId','门店必选',1,'callback'],
        ['SourceFrom','checkSourceFrom','平台必选',1,'callback'],
        ['Charge','checkCharge','费用必选',1,'callback'],

    ];

    public function getAdCost($map,$field)
    {
        $res = $this->where($map)->sum($field);
        return $res;
    }

    protected function checkStoreId()
    {
        $storeId = I('StoreId');
        if($storeId<1){
            return false;
        }else{
            return true;
        }
    }

    protected function checkSourceFrom()
    {
        $SourceFrom = I('SourceFrom');
        if($SourceFrom<1){
            return false;
        }else{
            return true;
        }
    }

    protected function checkCharge()
    {
        $Charge = I('Charge');
        if($Charge<0){
            return false;
        }else{
            return true;
        }
    }
}