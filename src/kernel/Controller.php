<?php
/**
 * Created by PhpStorm.
 * User:  Tianjun Wang
 * Email: 602033365@qq.com
 * Date:  2018/7/31
 * Time:  20:20
 */

namespace dadaochengwei\europebear\kernel;

class Controller
{
    private $view = null;
    public function __construct($controllerName, $actionName)
    {

    }

    public function assign($name, $value)
    {
        if ($this->view == null) {
            $this->view = new View();
        }
        $this->view->assign($name, $value);
    }

    public function view()
    {
        if ($this->view == null) {
            $this->view = new View();
        }
        $this->view->view();
    }

}