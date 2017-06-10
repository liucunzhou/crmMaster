<?php


namespace KWS\Model;

use Think\Model;

class PromoterModel extends Model
{
   // protected  $autoCheckFields = false;
    protected  $_validate =[
        ['CustNo','require','不能为空'],
       // ['Sex','require','不能为空'],
        ['StoreId','require','不能为空'],

    ];


}