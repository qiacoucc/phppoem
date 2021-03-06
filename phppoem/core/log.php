<?php
namespace poem;
class log {
    const ERR    = 'ERR';
    const WARN   = 'WARN';
    const NOTICE = 'NOTICE';
    const INFO   = 'INFO';
    const DEBUG  = 'DEBUG';
    static $test = 1;

    private static $info  = array(); // 日志信息
    private static $trace = array(); // 日志信息

    /**
     * 插入日志队列
     * @param string $str 日志信息
     * @param string $lvl 日志级别
     * @return null
     */
    static function push($str, $lvl = self::DEBUG) {
        array_push(self::$info, "{$lvl}: {$str}");
        self::trace($lvl, $str);
    }

    /**
     * 日志追踪
     * @param string $key 键
     * @param string $value 值
     * @return null
     */
    static function trace($key, $value) {
        if (!config('debug_trace')) {
            return;
        }

        if (isset(self::$trace[$key]) && count(self::$trace[$key]) > 50) {
            return;
        }

        self::$trace[$key][] = $value;
    }

    /**
     * 请求结束,由框架保存
     * @return null
     */
    static function show() {
        $trace_tmp = self::$trace;
        $files     = get_included_files();
        foreach ($files as $key => $file) {
            $files[$key] = $file . ' ( ' . number_format(filesize($file) / 1024, 2) . ' KB )';
        }
        $cltime           = T('POEM_TIME', -1);
        $trace_tmp['SYS'] = array(
            "请求信息"  => $_SERVER['REQUEST_METHOD'] . ' ' . strip_tags($_SERVER['REQUEST_URI']) . ' ' . $_SERVER['SERVER_PROTOCOL'] . ' ' . date('Y-m-d H:i:s', $_SERVER['REQUEST_TIME']),
            "总吞吐量"  => number_format(1 / $cltime, 2) . ' req/s',
            "总共时间"  => number_format($cltime, 5) . ' s',
            "框架加载"  => number_format(($cltime - T('POEM_EXEC_TIME', -1)), 5) . ' s (func:' . number_format(T('POEM_FUNC_TIME', -1) * 1000, 2) . 'ms conf:' . number_format(T('POEM_CONF_TIME', -1) * 1000, 2) . 'ms route:' . number_format(T('POEM_ROUTE_TIME', -1) * 1000, 2) . 'ms)',
            "App时间" => number_format(T('POEM_EXEC_TIME', -1), 5) . ' s (compile:' . number_format(T('POEM_COMPILE_TIME', -1) * 1000, 2) . ' ms)',
            "内存使用"  => number_format(memory_get_usage() / 1024 / 1024, 5) . ' MB',
            '文件加载'  => count($files),
            '会话信息'  => 'SESSION_ID=' . session_id(),
        );

        $trace_tmp['FILE'] = $files;

        $arr = array(
            'SYS'   => '基本',
            'FILE'  => '文件',
            'ERR'   => '错误',
            'SQL'   => '数据库',
            'DEBUG' => '调试',
        );
        foreach ($arr as $key => $value) {
            $num = 50;
            $len = 0;
            if (is_array($trace_tmp[$key]) && ($len = count($trace_tmp[$key])) > $num) {
                $trace_tmp[$key] = array_slice($trace_tmp[$key], 0, $num);
            }
            $trace[$value] = $trace_tmp[$key];
            if ($len > $num) {
                $trace[$value][] = "...... 共 $len 条";
            }

        }
        $totalTime = number_format($cltime, 3);
        include CORE_PATH . 'tpl/trace.php';
    }
}
