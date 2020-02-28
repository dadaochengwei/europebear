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
    protected $templatUrl;

    public function __construct($templatUrl = '')
    {
        $this->templatUrl = $templatUrl;
    }

    public function assign($name, $value)
    {
        $this->variables[$name] = $value;
    }

    public function view($fileName)
    {
        \extract($this->variables);
        echo $templatFile = APP_PATH . $fileName . '.php';
        // 判断视图文件是否存在
        if (\is_file($templatFile)) {
            include($templatFile);
        } else {
            echo "<p><strong>视图文件不存在！</strong></p>";
        }
    }
}