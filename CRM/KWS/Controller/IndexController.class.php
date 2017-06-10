<?php
namespace KWS\Controller;

use Think\Controller;

class IndexController extends BaseController
{
    public function index()
    {
        $controller = strtolower(CONTROLLER_NAME);
        $first = array_shift($this->menus[$controller]);
        sort($first);
        $this->redirect($first[0]['url']);
    }
}