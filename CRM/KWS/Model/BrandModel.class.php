<?php
namespace KWS\Model;

use Think\Model;

class BrandModel extends Model
{
    /**
     * 获取指定品牌的信息
     * @param $id
     * @return mixed
     */
    public function getBrand($id)
    {

        $brands = $this->getAllBrand();

        return $brands[$id];
    }

    /**
     * 获取所有品牌
     * @param $update bool 是否更新缓存
     * @return mixed
     */
    public function getAllBrand($update = false)
    {
        $brands = S('Brands');
        if (empty($brands) || $update) {
            $brands = $this->order("Department,Business")->getField('BrandId,BrandName,Department,Business');
            S('Brands', $brands);
        }
        return $brands;
    }
}