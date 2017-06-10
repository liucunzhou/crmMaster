<?php
namespace KWS\Controller;


/**
 * 等级相关
 * 1、摄影师等级
 * 2、化妆师等级
 * Class GlassController
 * @package KWS\Controller
 */
class GlassController extends BaseController
{
    /**
     * 摄影师等级
     */
    public function photographer()
    {
        $this->display();
    }

    /**
     * 化妆师等级
     */
    public function dresser()
    {
        $this->display();
    }
}