<?php

declare(strict_types=1);

namespace Mxgraph\Util;

/**
 * Copyright (c) 2006-2013, Gaudenz Alder.
 */
class mxLog
{
    /**
     * Class: mxLog.
     *
     * Logging facility.
     *
     * Variable: level_fine
     *
     * Specifies the fine logging level.
     */
    public static $level_fine = true;

    /**
     * Variable: level_debug.
     *
     * Specifies the debug logging level.
     */
    public static $level_debug = true;

    /**
     * Variable: level_info.
     *
     * Specifies the info logging level.
     */
    public static $level_info = true;

    /**
     * Variable: level_warn.
     *
     * Specifies the warn logging level.
     */
    public static $level_warn = true;

    /**
     * Variable: level_error.
     *
     * Specifies the error logging level.
     */
    public static $level_error = true;

    /**
     * Variable: current.
     *
     * Default is true.
     */
    public static $current = [];

    /**
     * Variable: tab.
     *
     * Default is true.
     */
    public static $tab = '';

    /**
     * Variable: logfiles.
     *
     * Holds the array of logfiles.
     */
    public static $logfiles = [];

    /**
     * Variable: printLog.
     *
     * Specifies if the log should be printed out.
     */
    public static $printLog = false;

    /**
     * Function: addLogfile.
     *
     * Adds a file for logging.
     *
     * @param mixed $filename
     */
    public static function addLogfile($filename): void
    {
        $fh = fopen($filename, 'a');
        self::$logfiles[] = $fh;
    }

    /**
     * Function: enter.
     *
     * Logs a method entry.
     *
     * @param mixed $method
     * @param mixed $text
     */
    public static function enter($method, $text = ''): void
    {
        self::writeln("{$method}: { {$text}");
        $t0 = microtime(true);
        self::$current[] = $t0;
        self::$tab .= '    ';
    }

    /**
     * Function: leave.
     *
     * Logs a method exit.
     *
     * @param mixed $text
     */
    public static function leave($text = ''): void
    {
        $t0 = array_pop(self::$current);
        $tab = self::$tab;
        self::$tab = substr($tab, 0, \strlen($tab) - 4);
        $dt = '(dt='.(microtime(true) - $t0).')';
        self::writeln("} {$dt} {$text}");
    }

    /**
     * Function: fine.
     *
     * Logs a fine trace.
     *
     * @param mixed $text
     */
    public static function fine($text): void
    {
        if (self::$level_fine) {
            self::writeln($text);
        }
    }

    /**
     * Function: debug.
     *
     * Logs a debug trace.
     *
     * @param mixed $text
     */
    public static function debug($text): void
    {
        if (self::$level_debug) {
            self::writeln($text);
        }
    }

    /**
     * Function: info.
     *
     * Logs an info trace.
     *
     * @param mixed $text
     */
    public static function info($text): void
    {
        if (self::$level_info) {
            self::writeln($text);
        }
    }

    /**
     * Function: warn.
     *
     * Logs a warn trace.
     *
     * @param mixed $text
     */
    public static function warn($text): void
    {
        if (self::$level_warn) {
            self::writeln($text);
            error_log($text);
        }
    }

    /**
     * Function: error.
     *
     * Logs an error trace.
     *
     * @param mixed $text
     */
    public static function error($text): void
    {
        if (self::$level_error) {
            self::writeln($text);
            error_log($text);
        }
    }

    /**
     * Function: writeln.
     *
     * Writes a line with a linefeed to the log.
     *
     * @param mixed $text
     */
    public static function writeln($text): void
    {
        self::write("{$text}\n");
    }

    /**
     * Function: write.
     *
     * Writes a line to the log.
     *
     * @param mixed $text
     */
    public static function write($text): void
    {
        $msg = date('Y-m-d H:i:s').': '.self::$tab.$text;
        foreach (self::$logfiles as $fh) {
            fwrite($fh, $msg);
        }
        if (self::$printLog) {
            $msg = str_replace(' ', '&nbsp;', $msg);
            echo "{$msg}<br>";
        }
    }

    /**
     * Function: close.
     *
     * Closes all open logfiles.
     */
    public static function close(): void
    {
        foreach (self::$logfiles as $fh) {
            fclose($fh);
        }
    }
}
