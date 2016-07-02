<?php 
/**
 * @desc: PHP守护进程Dem文件
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
header("Content-type: text/html; charset=utf-8");
ini_set('display_errors',1);
ini_set('date.timezone','Asia/Shanghai');
include('core/DaemonDefine.php');
$Daemon = new Daemon();
$Daemon->main($argv);