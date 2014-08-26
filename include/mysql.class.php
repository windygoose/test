<?php 
/** 
 * @author 至尊王者 
 * @author Link http://home.cnblogs.com/u/flying_bat/ 
 * @developer Zjmainstay 
 * @developer Link http://www.zjmainstay.cn 
 * @usage 
 * MysqlDump::dbDump('localhost', 'zjmainstay', '', 'test', 't_trade', 'tmp.sql'); 
 */ 
class MysqlDump { 
    /** 
     * 数据库内容导出 
     * @param $host         database host 
     * @param $user         username 
     * @param $pwd          password 
     * @param $db           database name 
     * @param $table        only dump one table 
     * @param $filename     custom file to write output content 
     */ 
    public static function dbDump($host, $user, $pwd, $db, $table = null, $filename = null) { 
        $mysqlconlink = mysql_connect($host, $user, $pwd, true); 
        if (!$mysqlconlink) 
            echo sprintf('No MySQL connection: %s',mysql_error())."<br/>"; 
        mysql_set_charset( 'utf8', $mysqlconlink ); 
        $mysqldblink = mysql_select_db($db,$mysqlconlink); 
        if (!$mysqldblink) 
            echo sprintf('No MySQL connection to database: %s',mysql_error())."<br/>"; 
        $tabelstobackup = array(); 
        $result = mysql_query("SHOW TABLES FROM `$db`"); 
        if (!$result) 
            echo sprintf('Database error %1$s for query %2$s', mysql_error(), "SHOW TABLE STATUS FROM `$db`;")."<br/>"; 
        while ($data = mysql_fetch_row($result)) { 
            if(empty($table)) { 
                $tabelstobackup[] = $data[0]; 
            } else if(strtolower($data[0]) == strtolower($table)){  //only dump one table 
                $tabelstobackup[] = $data[0]; 
                break; 
            } 
        } 
        if (count($tabelstobackup)>0) { 
            $result=mysql_query("SHOW TABLE STATUS FROM `$db`"); 
            if (!$result) 
                echo sprintf('Database error %1$s for query %2$s', mysql_error(), "SHOW TABLE STATUS FROM `$db`;")."<br/>"; 
            while ($data = mysql_fetch_assoc($result)) { 
                $status[$data['Name']]=$data; 
            } 
            if(!isset($filename)) { 
                $date = date('YmdHis'); 
                $filename = "{$db}.{$date}.sql"; 
            } 
            if ($file = fopen($filename, 'wb')) { 
                fwrite($file, "-- ---------------------------------------------------------\n"); 
                fwrite($file, "-- Database Name: $db\n"); 
                if(empty($table)) {    //if not only dump single table, dump database create sql  
                    self::_db_dump_create_database($db, $file); 
                } 
                fwrite($file, "-- ---------------------------------------------------------\n\n"); 
                fwrite($file, "/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;\n"); 
                fwrite($file, "/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;\n"); 
                fwrite($file, "/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;\n"); 
                fwrite($file, "/*!40101 SET NAMES '".mysql_client_encoding()."' */;\n"); 
                fwrite($file, "/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;\n"); 
                fwrite($file, "/*!40103 SET TIME_ZONE='".mysql_result(mysql_query("SELECT @@time_zone"),0)."' */;\n"); 
                fwrite($file, "/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;\n"); 
                fwrite($file, "/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;\n"); 
                fwrite($file, "/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;\n"); 
                fwrite($file, "/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;\n\n"); 
                foreach($tabelstobackup as $table) { 
                    echo sprintf('Dump database table "%s"',$table)."<br/>"; 
                    self::need_free_memory(($status[$table]['Data_length']+$status[$table]['Index_length'])*3); 
                    self::_db_dump_table($table,$status[$table],$file); 
                } 
                fwrite($file, "\n"); 
                fwrite($file, "/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;\n"); 
                fwrite($file, "/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;\n"); 
                fwrite($file, "/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;\n"); 
                fwrite($file, "/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;\n"); 
                fwrite($file, "/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;\n"); 
                fwrite($file, "/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;\n"); 
                fwrite($file, "/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;\n"); 
                fwrite($file, "/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;\n"); 
                fclose($file); 
                echo 'Database dump done!'."<br/>"; 
            } else { 
                echo 'Can not create database dump!'."<br/>"; 
            } 
        } else { 
            echo 'No tables to dump'."<br/>"; 
        } 
    } 

    protected static function _db_dump_create_database($dbname, $file) { 
        $sql = "SHOW CREATE DATABASE `".$dbname."`"; 
        $result=mysql_query($sql); 
        if (!$result) { 
            echo sprintf('Database error %1$s for query %2$s', mysql_error(), $sql)."<br/>"; 
            return false; 
        } 
        $dbstruc=mysql_fetch_assoc($result); 
        fwrite($file, str_ireplace('CREATE DATABASE', 'CREATE DATABASE IF NOT EXISTS', $dbstruc['Create Database']).";\n"); 
        fwrite($file, "USE `{$dbname}`;\n"); 
    } 

    protected static function _db_dump_table($table,$status,$file) { 
        fwrite($file, "\n"); 
        fwrite($file, "--\n"); 
        fwrite($file, "-- Table structure for table $table\n"); 
        fwrite($file, "--\n\n"); 
        fwrite($file, "DROP TABLE IF EXISTS `" . $table .  "`;\n"); 
        fwrite($file, "/*!40101 SET @saved_cs_client     = @@character_set_client */;\n"); 
        fwrite($file, "/*!40101 SET character_set_client = '".mysql_client_encoding()."' */;\n"); 
        $result=mysql_query("SHOW CREATE TABLE `".$table."`"); 
        if (!$result) { 
            echo sprintf('Database error %1$s for query %2$s', mysql_error(), "SHOW CREATE TABLE `".$table."`")."<br/>"; 
            return false; 
        } 
        $tablestruc=mysql_fetch_assoc($result); 
        fwrite($file, $tablestruc['Create Table'].";\n"); 
        fwrite($file, "/*!40101 SET character_set_client = @saved_cs_client */;\n"); 
        $result=mysql_query("SELECT * FROM `".$table."`"); 
        if (!$result) { 
            echo sprintf('Database error %1$s for query %2$s', mysql_error(), "SELECT * FROM `".$table."`")."<br/>"; 
            return false; 
        } 
        fwrite($file, "--\n"); 
        fwrite($file, "-- Dumping data for table $table\n"); 
        fwrite($file, "--\n\n"); 
        if ($status['Engine']=='MyISAM') 
            fwrite($file, "/*!40000 ALTER TABLE `".$table."` DISABLE KEYS */;\n"); 
        while ($data = mysql_fetch_assoc($result)) { 
            $keys = array(); 
            $values = array(); 
            foreach($data as $key => $value) { 
                if($value === NULL) 
                    $value = "NULL"; 
                elseif($value === "" or $value === false) 
                    $value = "''"; 
                elseif(!is_numeric($value)) 
                    $value = "'".mysql_real_escape_string($value)."'"; 
                $values[] = $value; 
            } 
            fwrite($file, "INSERT INTO `".$table."` VALUES ( ".implode(", ",$values)." );\n"); 
        } 
        if ($status['Engine']=='MyISAM') 
            fwrite($file, "/*!40000 ALTER TABLE ".$table." ENABLE KEYS */;\n"); 
    } 
    protected static function need_free_memory($memneed) { 
        if (!function_exists('memory_get_usage')) 
            return; 
        $needmemory=@memory_get_usage(true) + self::inbytes($memneed); 
        if ($needmemory > self::inbytes(ini_get('memory_limit'))) { 
            $newmemory=round($needmemory/1024/1024)+1 .'M'; 
            if ($needmemory>=1073741824) 
                $newmemory=round($needmemory/1024/1024/1024) .'G'; 
            if ($oldmem=@ini_set('memory_limit', $newmemory)) 
                echo sprintf('Memory increased from %1$s to %2$s','backwpup',$oldmem,@ini_get('memory_limit'))."<br/>"; 
            else 
                echo sprintf('Can not increase memory limit is %1$s','backwpup',@ini_get('memory_limit'))."<br/>"; 
        } 
    } 
     
    protected static function inbytes($value) { 
        $multi = strtoupper(substr(trim($value), -1)); 
        $bytes = abs((int)trim($value)); 
        if ($multi=='G') 
            $bytes=$bytes*1024*1024*1024; 
        if ($multi=='M') 
            $bytes=$bytes*1024*1024; 
        if ($multi=='K') 
            $bytes=$bytes*1024; 
        return $bytes; 
    } 
} 

//highlight_file 
highlight_file(__FILE__);
