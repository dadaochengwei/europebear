<?php
/**
 * Created by PhpStorm.
 * User: Tianjun
 * Date: 2020/2/29
 * Time: 14:27
 */

namespace dadaochengwei\europebear\classes\db;

use dadaochengwei\europebear\Core;
use PDO;
use PDOException;

class DbPdo extends Db
{
    // pdo对象
    private static $pdo = null;
    // Mysql连接配置
    private static $dbConfig = [];

    public function __construct($config = [])
    {
        // 如果没有传入配置参数就加载配置文
        if (empty($config))
            self::$dbConfig = require APP_PATH . 'config/database.php';
        else
            self::$dbConfig = $config;
    }

    /**
     * @name table
     * 设置表名和主键
     * @param $tableName
     * @param string $pkId
     * @return $this
     */
    public function table(string $tableName, string $pkId = 'id'): object
    {
        $this->tableName = $tableName;
        $this->pk($pkId);
        return $this;
    }

    /**
     * @name pk
     * 设置表主键字段
     * @param $pkId
     */
    public function pk($pkId): void
    {
        $this->pkId = $pkId;
    }

    /**
     * 连接数据库
     * @return object
     */
    public static function connect(): object
    {
        if (is_null(self::$pdo)) {
            try {
                // 构造连接字符串
                $dsn = "mysql:host=" . self::$dbConfig['host'] . ";dbname=" . self::$dbConfig['dbName'];
                // 实例化PDO对象，ATTR_PERSISTENT是否使用长连接，MYSQL_ATTR_USE_BUFFERED_QUERY使用缓冲查询
                $pdo = new PDO($dsn, self::$dbConfig['user'], self::$dbConfig['password'], [PDO::ATTR_PERSISTENT => false, PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true]);
                // 设置属性:ATTR_ERRMODE 错误处理方式，ERRMODE_EXCEPTION 异常模式，如果出错会采用异常来处理（PDOException）
                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                self::$pdo = $pdo;
                return $pdo;
            } catch (PDOException $e) {
                die($e->getMessage());
            }
        } else {
            return self::$pdo;
        }
    }

    /**
     * 执行sql语句
     * @param $sql
     * @param $method
     * @param $values
     * @return |null
     */
    public function doQuery($sql, $method, $values)
    {
        $methods = explode('::', $method);
        $method = $methods[1];
        $result = null;
        $beginTime = microtime(true);
        try {
            $pdo = self::connect();
            $stmt = $pdo->prepare($sql);
            $stmt->execute($values);
            if ($method == 'insert') {
                $result = $pdo->lastInsertId();
            } elseif ($method == 'update' || $method == 'delete') {
                $result = $stmt->rowCount();
            } elseif ($method == 'count') {
                $result = $stmt->rowCount();
            } elseif ($method == 'sum') {
                $result = $stmt->fetchColumn();
            } elseif ($method == 'select') {
                $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } elseif ($method == 'join') {
                $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } elseif ($method == 'find') {
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
            }
            $debug = $stmt->queryString;
            // 如果开启SQL调试模式，将会输出调试参数
            if (SQL_DEBUG) {
                echo "debugDumpParams:<br />";
                $stmt->debugDumpParams();
            }
            $endTime = microtime(true);
            $executionTime = round(($endTime - $beginTime), 4);
            $sqlDebug = ['sqlType' => $method, 'executionTime' => $executionTime, 'debug' => $debug];
            $this->setDbMsg('sequenceQuery', $sqlDebug);
            $this->resetSql();
            return $result;
        } catch (\PDOException $e) {
            Core::outPutJson(['sqlType' => $method, 'code' => $e->getCode(), 'error' => $e->getMessage()]);
        }
    }

    /**
     * 执行原生sql查询
     * @param $sql
	 * @param $returnType 1 返回全部 2 返回一条 3 sum
     * @return array
     */
    public function Query($sql, $returnType = 1)
    {
        $beginTime = microtime(true);
        $pdo = self::connect();
        $stmt = $pdo->query($sql);
        $data = [];
		if (returnType == 1 || returnType == 2) {
			if ($stmt->rowCount() == 1) {
				$data = $stmt->fetch(PDO::FETCH_ASSOC);
			} elseif ($stmt->rowCount() > 1) {
				$data = $stmt->fetchAll(PDO::FETCH_ASSOC);
			}
		} elseif(returnType == 3) {
			$data = $stmt->fetchColumn();
		}
        $debug = $stmt->queryString;
        $endTime = microtime(true);
        $executionTime = round(($endTime - $beginTime), 4);
        $sqlDebug = ['sqlType' => 'NativeQuery', 'executionTime' => $executionTime, 'debug' => $debug];
        $this->setDbMsg('sequenceQuery', $sqlDebug);
        return $data;
    }

    /**
     * 执行无结果集返回的sql
     * @param $sql
     * @return mixed
     */
    public function Exec($sql): int
    {
        $pdo = self::connect();
        $count = $pdo->exec($sql);
        return $count;
    }

    /**
     * 事务开始
     */
    public function beginTransaction()
    {
        $pdo = self::connect();
        $pdo->setAttribute(PDO::ATTR_AUTOCOMMIT, 0);
        $pdo->beginTransaction();
    }

    /**
     * 事务提交
     */
    public function commit()
    {
        $pdo = self::connect();
        $pdo->commit();
        $pdo->setAttribute(PDO::ATTR_AUTOCOMMIT, 1);
    }

    /**
     * 事务回滚
     */
    public function rollBack()
    {
        $pdo = self::connect();
        $pdo->rollBack();
        $pdo->setAttribute(PDO::ATTR_AUTOCOMMIT, 1);
    }

    /**
     * @name dbSize
     * 获取数据库的大小
     * @return string
     */
    public function dbSize()
    {
        $sql = "SHOW TABLE STATUS FROM " . self::$dbConfig['dbName'];
        $pdo = self::connect();
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $size = 0;
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $size += $row["Data_length"] + $row["Index_length"];
        }
        return Core::toSize($size);
    }

    /**
     * @name dbVersion
     * 获取数据库版本
     * @return string
     */
    public function dbVersion(): string
    {
        $pdo = self::connect();
        return $pdo->getAttribute(PDO::ATTR_SERVER_VERSION);
    }

    public function __destruct()
    {
        self::$pdo = null;
    }
}