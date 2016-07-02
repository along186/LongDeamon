<?php
/**
 * @desc: PHP守护进程核心预定义文件
 * ==============================================
 * along.com Lib
 * 版权所有 @2015 along.com
 * ----------------------------------------------
 * 这不是一个自由软件，未经授权不许任何使用和传播。
 * ----------------------------------------------
 * 权限（全局限制访问）
 * ==============================================
 * @date: 2016年5月19日 上午9:16:18
 * @author:liufeilong<alonglovehome@163.com>
 * @version: 1.0.0.0
 */
if(!defined('DAEMON_PATH')){
    define('DAEMON_PATH', rtrim(dirname(dirname(__FILE__)),'/'));
}
define('DS', '/');

//基础错误异常文件
require('DaemonException.class.php');

//基础核心文件
require('Daemon.class.php');
