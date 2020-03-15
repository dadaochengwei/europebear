<?php
/**
 * Created by PhpStorm.
 * User:  Tianjun Wang
 * Email: 602033365@qq.com
 * Date:  2018/7/31
 * Time:  20:22
 */

namespace dadaochengwei\europebear\kernel;

use dadaochengwei\europebear\classes\db\DbPdo;
use dadaochengwei\europebear\Container;

class Model
{
    public $db;
    protected $tableName;
    protected $pkId;

    public function __construct()
    {
        Container::bind('pdo', function () {
            return new DbPdo();
        });
        $dbGet = Container::make('pdo');
        $this->db = $dbGet->table($this->tabelName, $this->pkId);
    }

    /**
     * 统计记录数
     */
    public function count($where = [])
    {
        $data = $this->db->where($where)->count();
        return $data;
    }

    /**
     * 通过主键查找一条数据
     */
    public function find($id)
    {
        $data = $this->db->find($id);
        return $data;
    }

    /**
     * 通过WHERE查找一条数据 和yii2命名一致
     */
    public function findOne($where = [])
    {
        $data = $this->db->where($where)->find();
        return $data;
    }

    /**
     * 通过查找多条数据
     */
    public function findAll($where)
    {
        $data = $this->db->where($where)->select();
        return $data;
    }

    /**
     * 插入新记录
     */
    public function insert($data = []){
        if($data) {
            $ret = $this->db->insert($data);
        }else{
            $ret = 0;
        }
        return $ret;
    }

    /**
     * 更新数据
     */
    public function update($data = [], $where){
        if($data && $where) {
            $ret = $this->db->where($where)->update($data);
        }else{
            $ret = 0;
        }
        return $ret;
    }

    /**
     * 删除数据
     */
    public function delete($where){
        if($where && is_array($where)) {
            $ret = $this->db->where($where)->delete();
        }else{
            $ret = 0;
        }
        return $ret;
    }

    /**
     * 打印调试信息
     */
    public function printSql(){
        echo "<pre>";
        print_r($this->db->getDbMsg());
        echo "</pre>";
    }


}