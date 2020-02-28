<?php
/**
 * Created by PhpStorm.
 * User:  Tianjun Wang
 * Email: 602033365@qq.com
 * Date:  2018/7/31
 * Time:  20:22
 */

namespace dadaochengwei\europebear\kernel;


class View
{
    protected $variables = [];

    public function __construct()
    {

    }

    public function assign($name, $value)
    {
        $this->variables[$name] = $value;
    }

    public function view()
    {

    }
}