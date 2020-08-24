<?php
/**
 * Created by PhpStorm.
 * User: Tianjun
 * Date: 2020/2/29
 * Time: 14:22
 */

namespace dadaochengwei\europebear\classes\db;


abstract class Db
{
    // 表名
    protected $tableName;
    // 主键默认id
    protected $pkId;
    // 字段列表
    protected $fieldList = [];
    // SQL语句中的字段列表，条件，排序，分组，聚合，限制范围共6个[关键字/子句]作为连贯查询的方法通过魔术方法__call组合处理
    protected $sql = [
        "field" => "*",
        "where" => "",
        "whereIn" => "",
        "whereNotIn" => "",
        "order" => "",
        "group" => "",
        "limit" => "",
        "having" => ""
    ];
    // 返回信息
    protected $dbMsg = [];

    /**
     * @name resetSql
     * 重置sql属性,where 仅支持数组
     */
    protected function resetSql()
    {
        $this->sql = [
            "field" => "*",
            "where" => "",
            "whereIn" => "",
            "whereNotIn" => "",
            "order" => "",
            "group" => "",
            "limit" => "",
            "having" => ""
        ];
    }

    /**
     * @name __call
     * 魔术方法 组合6个关键字和子句的
     * @param $name
     * @param $args
     * @return $this
     */
    public function __call($name, $args)
    {
        // 方法名转小写
        // $name = strtolower($name);
        // 判读方法名在不在预置属性的key中
        if (array_key_exists($name, $this->sql)) {
            if (empty($args[0]) || (is_string($args[0]) && trim($args[0]) === '')) {
                $this->sql[$name] = "";
            } else {
                $this->sql[$name] = $args;
            }
        }
        // 返回对象本身是本类和子类中方法能连贯执行到最后的关键一步
        return $this;
    }

    /**
     * @name subSql
     * @description 组合并返回sql子句
     * @return array
     */
    public function subSql()
    {
        // 处理字段列表，支持一维数组和字符串例如：'id,username,sex' 或数组 ['id','username','sex']
        $fields = is_array($this->sql["field"][0]) ? implode(",", $this->sql["field"][0]) : $this->sql["field"][0];
        // 按指定字段排序传字符串如 'id ASC' 或 'id DESC'
        $order = $this->sql["order"] != "" ? " ORDER BY {$this->sql["order"][0]}" : "";
        // 按指定字段进行分组传字符串如:'cat_id'等
        $group = $this->sql["group"] != "" ? " GROUP BY {$this->sql["group"][0]}" : "";
        // LIMIT子句限制查询记录数量以及偏移量。支持两种：1.限制记录数量使用整型如20 2.如分页场景将需要传入数组[10,20]
        $limit = $this->sql["limit"] != "" ? $this->comLimit($this->sql["limit"]) : "";
        // 对结果集进行聚合可使用聚合函数支持运算符，如SUM(population) > 1000000
        $having = $this->sql["having"] != "" ? " HAVING {$this->sql["having"][0]}" : "";
        // 查询条件的组合
        $where = $this->sql["where"] != "" ? $this->comWhere($this->sql['where']) : "";
        // 查询条件的组合IN
        $whereIn = $this->sql["whereIn"] != "" ? $this->comWhereIn($this->sql['whereIn']) : "";
        // 查询条件的组合NOT IN
        $whereNotIn = $this->sql["whereNotIn"] != "" ? $this->comWhereNotIn($this->sql['whereNotIn']) : "";
        $return = [
            'fields' => $fields,
            'order' => $order,
            'group' => $group,
            'limit' => $limit,
            'having' => $having,
            'where' => $where,
            'whereIn' => $whereIn,
            'whereNotIn' => $whereNotIn
        ];
        return $return;
    }

    /**
     * 插入数据
     * @param null $data
     * @return bool
     */
    public function insert($data = null)
    {
        if (!is_array($data)) {
            return false;
        }
        $sql = "INSERT INTO {$this->tableName} (" . implode(',', array_keys($data)) . ") VALUES (" . implode(',', array_fill(0, count($data), '?')) . ")";
        return $this->doQuery($sql, __METHOD__, array_values($data));
    }

    /**
     * 更新数据
     * @param null $data
     * @return bool
     */
    public function update($data = null)
    {
        if (!is_array($data)) {
            return false;
        }
        $dataStr = "";
        $i = 0;
        $count = count($data);
        foreach ($data as $k => $v) {
            $i++;
            if ($i < $count) {
                $dataStr .= $k . "= ?, ";
            } else {
                $dataStr .= $k . "= ? ";
            }
        }
        $subSql = $this->subSql();
        $sql = "UPDATE {$this->tableName} SET {$dataStr}{$subSql['where']}";
        $comData = array_values($data);
        foreach ($this->sql['where'][0] as $key => $value) {
            $val = is_array($value) ? $value[0] : $value;
            array_push($comData, $val);
        }
        return $this->doQuery($sql, __METHOD__, $comData);
    }

    /**
     * 查询数据
     * @return mixed
     */
    public function select()
    {
        $subSql = $this->subSql();
        $sql = "SELECT {$subSql['fields']} FROM {$this->tableName} {$subSql['where']} ";
        $sql .= "{$subSql['whereIn']} {$subSql['whereNotIn']} ";
        $sql .= "{$subSql['group']} {$subSql['having']} {$subSql['order']} {$subSql['limit']}";
        $comData = [];
        if (is_array($this->sql['where'])) {
            foreach ($this->sql['where'][0] as $key => $value) {
                $val = is_array($value) ? $value[0] : $value;
                array_push($comData, $val);
            }
        }
        return $this->doQuery($sql, __METHOD__, $comData);
    }

    /**
     * 统计记录数量
     * @param string $pk
     * @return mixed
     */
    public function count($pk = 'id')
    {
        $subSql = $this->subSql();
        $sql = "SELECT {$pk} FROM {$this->tableName} {$subSql['where']} {$subSql['group']} {$subSql['having']}";
        $comData = [];
        if (is_array($this->sql['where'])) {
            foreach ($this->sql['where'][0] as $key => $value) {
                $val = is_array($value) ? $value[0] : $value;
                array_push($comData, $val);
            }
        }
        return $this->doQuery($sql, __METHOD__, $comData);
    }

    /**
     * 字段求和
     * @param string $field
     * @return mixed
     */
    public function sum($field = 'id')
    {
        $subSql = $this->subSql();
        $sql = "SELECT SUM({$field}) FROM {$this->tableName} {$subSql['where']} {$subSql['group']} {$subSql['having']}";
        $comData = [];
        if (is_array($this->sql['where'])) {
            foreach ($this->sql['where'][0] as $key => $value) {
                $val = is_array($value) ? $value[0] : $value;
                array_push($comData, $val);
            }
        }
        return $this->doQuery($sql, __METHOD__, $comData);
    }

    /**
     * 联表查询
     * @param $joinString
     * @return mixed
     */
    public function join($joinString)
    {
        $subSql = $this->subSql();
        $sql = "SELECT {$subSql['fields']} FROM {$this->tableName} {$joinString} {$subSql['where']} {$subSql['group']} {$subSql['having']} {$subSql['order']} {$subSql['limit']}";
        $comData = [];
        if (is_array($this->sql['where'])) {
            foreach ($this->sql['where'][0] as $key => $value) {
                $val = is_array($value) ? $value[0] : $value;
                array_push($comData, $val);
            }
        }
        return $this->doQuery($sql, __METHOD__, $comData);
    }

    /**
     * 查询单条记录
     * 返回一个一维关联数组
     * @param int $pkId
     * @return mixed
     */
    public function find($pkId = 0)
    {
        $fields = $this->sql["field"] != "" ? $this->sql["field"][0] : implode(",", $this->fieldList);
        if ($pkId) {
            $where = " WHERE {$this->pkId} = {$pkId}";
            $sql = "SELECT {$fields} FROM {$this->tableName} {$where}";
            $comData = [$pkId];
        } else {
            $subSql = $this->subSql();
            $sql = "SELECT {$fields} FROM {$this->tableName} {$subSql['where']}";
            $comData = [];
            if (is_array($this->sql['where'])) {
                foreach ($this->sql['where'][0] as $key => $value) {
                    $val = is_array($value) ? $value[0] : $value;
                    array_push($comData, $val);
                }
            }
        }
        return $this->doQuery($sql, __METHOD__, $comData);
    }

    /**
     * 删除记录
     * @param int $pkId
     * @return mixed
     */
    public function delete($pkId = 0)
    {
        if ($pkId) {
            $sql = "DELETE FROM {$this->tableName} WHERE {$this->pkId} = ?";
            $comData = [$pkId];
        } else {
            $subSql = $this->subSql();
            $sql = "DELETE FROM {$this->tableName} {$subSql['where']}";
            $comData = [];
            if (is_array($this->sql['where'])) {
                foreach ($this->sql['where'][0] as $key => $value) {
                    $val = is_array($value) ? $value[0] : $value;
                    array_push($comData, $val);
                }
            }
        }
        return $this->doQuery($sql, __METHOD__, $comData);
    }

    /**
     * 字段自增
     * @param $field
     * @param int $quantity
     * @return mixed
     */
    public function increment($field, $quantity = 1)
    {
        $subSql = $this->subSql();
        $dataStr = "$field = $field + $quantity";
        $sql = "UPDATE {$this->tableName} SET {$dataStr} {$subSql['where']} {$subSql['whereIn']} {$subSql['whereNotIn']} ";
        $comData = [];
        if($this->sql['where']) {
            foreach ($this->sql['where'][0] as $key => $value) {
                $val = is_array($value) ? $value[0] : $value;
                array_push($comData, $val);
            }
        }
        return $this->doQuery($sql, __METHOD__, $comData);
    }

    /**
     * 字段自减
     * @param $field
     * @param int $quantity
     * @return mixed
     */
    public function decrement($field, $quantity = 1)
    {
        $subSql = $this->subSql();
        $dataStr = "$field = $field - $quantity";
        $sql = "UPDATE {$this->tableName} SET {$dataStr} {$subSql['where']} {$subSql['whereIn']} {$subSql['whereNotIn']} ";
        $comData = [];
        if($this->sql['where']) {
            foreach ($this->sql['where'][0] as $key => $value) {
                $val = is_array($value) ? $value[0] : $value;
                array_push($comData, $val);
            }
        }
        return $this->doQuery($sql, __METHOD__, $comData);
    }

    /**
     * @name comWhere
     * 组合WHERE条件
     * where() 支持数组参数如： id = 1 可以传 ['id' => 1]
     * 如果 id > 1 则需要将条件对应的value改写成数组 如：['id' => [1, '>']
     * 如果 id > 1 且 sex = 1 如：['id' => [1, '>'], 'sex' => 1]
     * 逻辑运算符请在val第三个元素中设置如：['id' => 1, 'sex' => [1, '>', 'OR']]
     * 支持字符串如 'id > 1 and sex = 1' 或 "id > 8 or city = 'xian'" 字符串更加灵活，条件参数复杂时推荐使用字符串，但未做预处理
     * @param $args
     * @param bool $prepare
     * @return string
     */
    private function comWhere($args)
    {
        $where = " WHERE ";
        if (is_array($args[0])) {
            $k = 0;
            foreach ($args[0] as $key => $val) {
                $operator = "=";
                $logicalOperator = " AND ";
                if (is_array($val)) {
                    $operator = $val[1];
                    $logicalOperator = empty($val[2]) ? $logicalOperator : " {$val[2]} ";
                }
                // 如果是第一个参数则不需要加逻辑符，其他都要加上
                if ($k == 0) {
                    $where .= $key . " $operator ? ";
                } else {
                    $where .= $logicalOperator . $key . " $operator ? ";
                }
                $k++;
            }
            return $where;
        } elseif ($args[0]) {
            return $where . $args[0];
        } else {
            return '';
        }
    }

    /**
     * IN 范围组合
     * @param $args
     * @return string
     */
    private function comWhereIn($args)
    {
        if(!$this->sql['where'])
            $where = ' WHERE ';
        else
            $where = ' AND ';
        return $where . $args[0] . ' IN (' . implode(',', $args[1]) . ')';
    }

    /**
     * NOT IN 范围组合
     * @param $args
     * @return string
     */
    private function comWhereNotIn($args)
    {
        if(!$this->sql['where'])
            $where = ' WHERE ';
        else
            $where = ' AND ';
        return $where . $args[0] . ' NOT IN (' . implode(',', $args[1]) . ')';
    }

    /**
     * @name comLimit
     * 组合LIMIT子句
     * LIMIT子句限制查询记录数量以及偏移量。支持两种：1.限制记录数量使用整型如20 2.如分页场景将需要传入数组[10,20]
     * @param $args
     * @return string
     */
    private function comLimit($args)
    {
        if (is_array($args[0])) {
            return " LIMIT {$args[0][0]},{$args[0][1]}";
        } else if (is_int($args[0])) {
            return " LIMIT {$args[0]}";
        } else {
            return "";
        }
    }

    /**
     * 设置sql信息
     * @param $key
     * @param $value
     */
    protected function setDbMsg($key, $value)
    {
        $this->dbMsg[$key] = $value;
    }

    /**
     * 获取sql信息
     * @return array
     */
    public function getDbMsg()
    {
        return $this->dbMsg;
    }

    // 事务开始
    abstract function beginTransaction();

    // 提交业务
    abstract function commit();

    // 回滚
    abstract function rollBack();

    // 返回数据库大小
    abstract function dbSize();

    // 返回数据库系统版本
    abstract function dbVersion();

}