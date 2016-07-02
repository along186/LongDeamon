<?php
/**
 * @desc: DaemonExcepiton错误输出类
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
class DaemonException extends Exception{
    
    /**
     * (non-PHPdoc)
     * @see Exception::__toString()
     */
    public function __toString(){
        $r='<pre>'."\r\n";
        $r.='Exception: '."\r\n".'Message: '. $this->getMessage () . ''."\r\n".'File: ' . $this->getFile () . ''."\r\n".'Line: '. $this->getLine () . ''."\r\n".'Trace: ' ;
        $r.="\r\n";
        $r.=$this->getTraceAsString();
        $r.="\r\n".'</pre>';
        return $r;
    }
}