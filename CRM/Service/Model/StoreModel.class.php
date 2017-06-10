<?php
namespace Service\Model;


use Think\Model;

class StoreModel extends Model
{

    /**
     * @param bool|false $update
     * @return mixed
     */
    public function getAllStore($update = false)
    {
        $stores = S('Stores');
        if (empty($stores) || $update) {
            $stores = $this->order('BrandId,OrderNo')->getField('StoreId,StoreName,GroupId,BrandId,DepartId,SellIds,Users,Business');
            S('Stores', $stores);
        }

        return $stores;
    }

    /**
     * 获取指定门店信息
     * @param $id int 门店ID
     * @param $field string 获取门店的某一个字段名称
     * @return mixed
     */
    public function getStore($id, $field = '')
    {
        $stores = $this->getAllStore();

        $store = $stores[$id];

        if (!empty($field)) {
            return $store[$field];
        } else {
            return $store;
        }
    }
}