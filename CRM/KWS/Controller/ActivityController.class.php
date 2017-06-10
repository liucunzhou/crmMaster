<?php
namespace KWS\Controller;

class ActivityController extends BaseController
{
    public function admin()
    {
        !empty($_GET['id']) && $map['id'] = I('id');
        !empty($_GET['mobile']) && $map['Mobile'] = I('mobile');
        !empty($_GET['province']) && $map['Province'] = I('province');
        !empty($_GET['city']) && $map['City'] = I('city');

        $startInsertTime = !empty($_GET['StartInsertTime']) ? strtotime($_GET['StartInsertTime']) : 0;
        $endInsertTime = !empty($_GET['EndInsertTime']) ? strtotime($_GET['EndInsertTime']) : strtotime('tomorrow');
        $map['InsertTime'] = ['between', [$startInsertTime, $endInsertTime]];

        $this->page('WeddingSeason', [
            'map'   => $map,
            'order' => 'InsertTime desc'
        ]);
        $this->display('season');
    }

    /**
     * 婚礼季,活动列表页面
     */
    public function season()
    {
        $allProvince = $this->_getKProvince();
        $kid = $this->user['kid'];
        !empty($_GET['id']) && $map['id'] = I('id');
        !empty($_GET['mobile']) && $map['Mobile'] = I('mobile');
        !empty($_GET['city']) && $map['City'] = I('city');
        $map['Status'] = 3;

        if($this->user['roleid'] == '6') {
            $provinces = $allProvince[$kid];
            if (!empty($_GET['province'])) {
                in_array($provinces, $_GET['province']) && $map['Province'] = I('province');
            } else {
                $map['Province'] = ['in', $provinces];
            }
        } else {
            !empty($_GET['province']) && $map['Province'] = I('province');
        }

        $startInsertTime = !empty($_GET['StartInsertTime']) ? strtotime($_GET['StartInsertTime']) : 0;
        $endInsertTime = !empty($_GET['EndInsertTime']) ? strtotime($_GET['EndInsertTime']) : strtotime('tomorrow');
        $map['InsertTime'] = ['between', [$startInsertTime, $endInsertTime]];

        $this->page('WeddingSeason', [
            'map'   => $map,
            'order' => 'InsertTime desc'
        ]);
        $this->display();
    }

    /**
     * 获取各K对应的省份
     */
    private function _getKProvince()
    {
        $provinces = [
            2 => [
                '上海','湖北','四川','云南','江苏','浙江','广东'
            ],
            3 => [
                '北京','天津','河北','山东','辽宁','吉林','湖南','河南','内蒙古','山西','陕西','江苏','浙江'
            ],
            4 => [
                '重庆','广西','海南'
            ],
            6 => [
                '安徽','福建'
            ],
        ];

        return $provinces;
    }
}