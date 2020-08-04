<?php
/* později se dá přidat symphony console*/

class DebugConsole
{

    CONST LOG_DIR = __DIR__ . '/app/log';
    CONST NO_PURGE = 'No logs to purge';

    public static function outputSettings() {
        echo PHP_EOL . 'Debug mode is ' . ((Config::getDebug()) ? 'on' : '*off*');
    }

    public static function clearLogs() {

            $max_age = strtotime(Config::LOG_MAX_AGE, 0);
            $list = array();
            $limit = time() - $max_age;
            $dir = realpath(self::LOG_DIR);

            if (!is_dir($dir)) {
                self::output(self::NO_PURGE);
                return null;
            }

            $dh = opendir($dir);
            if ($dh === false) {
                self::output(self::NO_PURGE);
                return null;
            }

            while (($file = readdir($dh)) !== false) {
                $file = $dir . '/' . $file;
                if (!is_file($file)) {
                    continue;
                }

                if (filemtime($file) < $limit) {
                    $list[] = $file;
                    unlink($file);
                }

            }
            closedir($dh);

            self::output("Deleted " . count($list) . " old log(s):\n" . implode("\n", $list) );
            return $list;
        }

    public static function output($output) {
       if(Config::getDebug()) {
           echo PHP_EOL.$output;
       }
    }

}