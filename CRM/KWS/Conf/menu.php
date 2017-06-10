<?php
return array(
    'Nav' => array(
        'Index' => array(
                'text' => '系统设置',
                'url' => 'Index/index',
                'ctrl' => array('index', 'brand', 'source', 'hall','intention','store','department','user','auth','operatelog')
        ),
        'Customer' => array(
            'text' => '客户管理',
            'url' => 'Customer/index',
            'ctrl' => array('customer', 'seller', 'promoter', 'order', 'assign', 'promotion', 'washing', 'activity')
        ),
        'Count' => array(
            'text' => '数据统计',
            'url' => 'Count/index',
            'ctrl' => array('count','exp')
        ),
    ),

    'Menu' => array(
        'index' => array(
            '基本设置' => array(
                array(
                    'text' => '品牌管理',
                    'url' => 'Brand/index',
                    'icon' => 'glyphicon glyphicon-registration-mark'
                ),
                array(
                    'text' => '意向状态',
                    'url' => 'Intention/index',
                    'icon' => 'glyphicon glyphicon-credit-card'
                ),
                array(
                    'text' => '平台来源',
                    'url' => 'Source/index',
                    'icon' => 'glyphicon glyphicon-transfer'
                ),
                array(
                    'text' => '婚礼堂管理',
                    'url' => 'Hall/index',
                    'icon' => 'glyphicon glyphicon-cutlery'
                ),
            ),
            '部门管理' => array(
                array(
                    'text' => '门店管理',
                    'url' => 'Store/index',
                    'icon' => 'glyphicon glyphicon-qrcode'
                ),
                array(
                    'text' => '部门管理',
                    'url' => 'Department/index',
                    'icon' => 'glyphicon glyphicon-bookmark'
                ),
                array(
                    'text' => '用户管理',
                    'url' => 'User/index',
                    'icon' => 'glyphicon glyphicon-user'
                )
            ),
            '权限管理'  => array(
                array(
                    'text'  => '功能列表',
                    'url'   => 'Auth/index',
                    'icon'  => 'glyphicon glyphicon-align-justify'
                ),
                /*array(
                    'text'  => '角色管理',
                    'url'   => 'Auth/group',
                    'icon'  => 'glyphicon glyphicon-fire'
                ),*/
                array(
                    'text'  => '操作日志',
                    'url'   => 'OperateLog/index',
                    'icon'  => 'glyphicon glyphicon-fire'
                )
            ),
            '个人中心' => array(
                array(
                    'text'  => '个人资料',
                    'url'   => 'User/info',
                    'icon'  => 'glyphicon glyphicon-star'
                ),
                array(
                    'text'  => '重置密码',
                    'url'   => 'User/repassword',
                    'icon'  => 'glyphicon glyphicon-lock'
                )
            )
        ),

        'customer' => array(
            '邀约手'  => array(
                array(
                    'text' => '客户信息',
                    'url'  => 'Promoter/index',
                    'icon' => 'glyphicon glyphicon-plus'
                ),
                array(
                    'text' => '推广资费',
                    'url' => 'Promotion/index',
                    'icon' => 'glyphicon glyphicon-plus'
                ),
                array(
                    'text'  => '婚礼季',
                    'url'   => 'Activity/season',
                    'icon'  => 'glyphicon glyphicon-adjust'
                )
            ),
            '销售回访'  => array(
                array(
                    'text' => '客户信息',
                    'url'  => 'Seller/index',
                    'icon' => 'glyphicon glyphicon-plus'
                ),
                array(
                    'text' => '订单管理',
                    'url'  => 'Order/index',
                    'icon' => 'glyphicon glyphicon-plus'
                ),
                array(
                    'text' => '进店管理',
                    'url'  => 'Seller/gotoStore',
                    'icon' => 'glyphicon glyphicon-plus'
                ),
                array(
                    'text' => '手动分配',
                    'url'  => 'Assign/menuAssign',
                    'icon' => 'glyphicon glyphicon-plus'
                ),
                array(
                    'text' => '离职管理',
                    'url'  => 'Seller/dimission',
                    'icon' => 'glyphicon glyphicon-plus'
                )
            ),
            '分配洗单' => array(
                array(
                    'text' => '分配客资',
                    'url' => 'Washing/k7assign',
                    'icon' => 'glyphicon glyphicon-plus'
                ),
                array(
                    'text' => '洗单平台数据',
                    'url' => 'Washing/fineReport',
                    'icon' => 'glyphicon glyphicon-plus'
                ),
                array(
                    'text' => '洗单销售数据',
                    'url' => 'Washing/salseReport',
                    'icon' => 'glyphicon glyphicon-plus'
                ),
                array(
                    'text' => '洗单实时数据',
                    'url' => 'Washing/realReport',
                    'icon' => 'glyphicon glyphicon-plus'
                ),
                /*array(
                    'text' => '洗单',
                    'url'  => 'Washing/index',
                    'icon' => 'glyphicon glyphicon-plus'
                )*/

            ),
        ),

        'count' => array(
            '报表统计' => array(
                /*
                array(
                    'text' => '来源统计',
                    'url' => 'Count/platform',
                    'icon' => 'glyphicon glyphicon-leaf'
                ),
                */
                array(
                    'text' => '平台统计',
                    'url' => 'Count/platform',
                    'icon' => 'glyphicon glyphicon-leaf'
                )
            ),

            '部门统计'  => array(
                /**
                array(
                    'text'  => '部门台账',
                    'url'   => 'Count/account',
                    'icon'  => 'glyphicon glyphicon-th-large'
                ),
                */
                array(
                    'text'  => '平台咨询数据',
                    'url'   => 'Count/fineReport',
                    'icon'  => 'glyphicon glyphicon-th-large'
                ),
                array(
                    'text'  => '销售数据',
                    'url'   => 'Count/salseReport',
                    'icon'  => 'glyphicon glyphicon-th-large'
                ),
                array(
                    'text'  => '每小时数据',
                    'url'   => 'Count/realReport',
                    'icon'  => 'glyphicon glyphicon-th-large'
                ),
                array(
                    'text'  => '门店咨询数据',
                    'url'   => 'Count/storeReport',
                    'icon'  => 'glyphicon glyphicon-th-large'
                ),
                /*array(
                    'text'  => '业绩统计',
                    'url'   => 'Count/score',
                    'icon'  => 'glyphicon glyphicon glyphicon-stats'
                ),
                array(
                    'text'  => '客服排名',
                    'url'   => 'Count/sort',
                    'icon'  => 'glyphicon glyphicon-file'
                ),
                array(
                    'text'  => '转化率统计',
                    'url'   => 'Count/convert',
                    'icon'  => 'glyphicon glyphicon-file'
                )*/
            ),
            '订单比较'=>array(
                array(
                    'text'  => '比较订单',
                    'url'   => 'Exp/upExcel',
                    'icon'  => 'glyphicon glyphicon glyphicon-stats'
                ),
            ),
        )
    ),
);


