<?php
namespace KWS\Controller;

/**
 * Class BrandController、
 * 品牌管理
 * @package KWS\Controller
 */
class TestController extends BaseController
{
    private $where = [];

    /**
     * 所有客户
     */
    public function index()
    {
        $count = S("Count-1574");
        var_dump($count);
    }

    public function info(){
        //print_r($this->stores[152]);
        $QueueName = "queue-department-" . 84 . '-store-' . 152;
        $queue = S($QueueName);
        echo $QueueName;
        print_r($queue);
    }

    public function test()
    {
       

    }


}