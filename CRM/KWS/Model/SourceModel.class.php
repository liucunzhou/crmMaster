<?php
namespace KWS\Model;

use Think\Model;

class SourceModel extends Model
{

    /**
     * 获取指定来源的数据
     * @param $id 指定来源的ID
     * @param string $field 获取指定来源的指定字段值
     * @return mixed
     */
    public function getSource($id, $field = '')
    {
        $sources = $this->getAllSource();

        if (empty($field)) {
            return $sources[$id];
        } else {
            return $sources[$id][$field];
        }
    }

    /**
     * 获取所有来源
     * @param $update bool 是否更新缓存
     * @return array 所有来源
     */
    public function getAllSource($update = false)
    {
        $sources = S('Sources');
        if(empty($sources) || $update) {
            $sources = $this->getField('SourceId,SourceName,Platform');
            S('Sources', $sources);
        }

        return $sources;
    }

    /**
     * @param $sourceType
     * @return mixed
     */
    public function getBigCatSource($sourceType)
    {
        $bigSources = S('bigSource');
        if(empty($bigSources)){
            $sources = $this->getAllSource();
            foreach($sources as $k=>$v){
                if($v['Platform']==$sourceType){
                    $bigSources[$sourceType][] = $v['sourceId'];
                }
            }
            S('bigSource',$bigSources);
        }
        return $bigSources;
    }

    /**
     * 邀约手所属来源
     * @return array
     */
    public function getPromoterSource()
    {
        $sources = [];

         $sources = S('PromoterSource-'.$this->user['userid']);
        if(!$sources){

            $rows = M('source')->order('OrderNo')->select();
            foreach ($rows as $key => $val) {
                if($val['sourcename']){
                    $sources[$val['platform']][] = $val;
                }
            }
            S('PromoterSource-'.$this->user['userid'],$sources);
        }
        return $sources;
    }
}