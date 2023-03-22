<?php

namespace Zenithies\Toolkit\ReadKey;

class Interceptor
{
    // The escape code for Windows environments is chr 0 or 224
    // The (standard) ansi escape sequence is 27 + 91 (\e[])

    public const KEY_LEFT  = 28;
    public const KEY_RIGHT = 29;
    public const KEY_UP    = 30;
    public const KEY_DOWN  = 31;

    public const PHP_REQUIRED_MAJOR_VERSION = 8;
    public const PHP_REQUIRED_MINOR_VERSION = 1;
    public const WINDOWS_REQUIRED_VERSION = 10; # Note that Windows 11 still identifies as 10
    public const WINDOWS_ARCHITECTURE = 64; # We need a 64-bit architecture

    private static $__instance = false;
    private $dll;
    private bool $isInitialized = false;

    protected $sequences;

    /**
     * @throws \Exception
     */
    private function __construct()
    {
        if (!self::isCLI()) {
            throw new \Exception('Interceptor has to run in CLI environment');
        }
        // check PHP version
        if (
            PHP_MAJOR_VERSION >= self::PHP_REQUIRED_MAJOR_VERSION &&
            PHP_MINOR_VERSION >= self::PHP_REQUIRED_MINOR_VERSION
        ) {
        } else {
            throw new \Exception('PHP version must be ' . self::PHP_REQUIRED_MAJOR_VERSION . '.' . self::PHP_REQUIRED_MINOR_VERSION . ' or greater, currently using ' . PHP_VERSION);
        }
        // Windows specific checking
        if (self::isWindows()) {
            // are we running (at least) windows 10?
            if ((float)php_uname('r') < self::WINDOWS_REQUIRED_VERSION) {
                throw new \Exception('Windows version ' . self::WINDOWS_REQUIRED_VERSION . ' or higher required.');
            }
            // are we on a 64-bit architecture?
            if (strpos(php_uname('m'), self::WINDOWS_ARCHITECTURE) === false) {
                throw new \Exception('This script requires a ' . self::WINDOWS_ARCHITECTURE . ' bit architecture, you are currently on ' . php_uname('m'));
            }
            // does the COM class exist?
            if (!\class_exists('COM')) {
                self::eprintln('Warning: COM extension is required on windows. Please set extension=php_com_dotnet in your php.ini and register the DLL found in this repo.');
                return false;
            }
            // can we load the DLL?
            try {
                $this->dll = new \COM('ZenithiesCLIKeys.ReadKey');
            } catch (\Throwable $e) {
                self::eprintln("Unable to initialize ZenithiesCLIKeys.ReadKey, make sure it is registered by regsvr32 and you've picked architecture matching your PHP installation: {$e->getMessage()}");
                return false;
            }
        }
    }

    public function init(): bool
    {
        if (self::isWindows()) {
            $this->sequences = [
                224 => [ // normal arrow keys
                    72 => self::KEY_UP, // Up
                    77 => self::KEY_RIGHT, // Right
                    80 => self::KEY_DOWN, // Down
                    75 => self::KEY_LEFT, // Left
                ],
                0 => [ // numpad arrow keys
                    72 => self::KEY_UP, // Up
                    77 => self::KEY_RIGHT, // Right
                    80 => self::KEY_DOWN, // Down
                    75 => self::KEY_LEFT, // Left
                ],
            ];
        } else {
            $this->sequences = [
                27 => [
                    91 => [
                        65 => self::KEY_UP, // Up
                        67 => self::KEY_RIGHT, // Right
                        66 => self::KEY_DOWN, // Down
                        68 => self::KEY_LEFT, // Left
                    ],
                ],
            ];
        }

        $this->isInitialized = true;

        return true;
    }

    /**
     * @return int
     *
     * @throws \Exception
     */
    public function intercept(): int
    {
        if (!$this->isInitialized) {
            throw new \Exception('Read Key is not initialized');
        }

        return $this->dll === null ? $this->interceptNIX() : $this->interceptWIN();
    }

    private function interceptWIN(): int
    {
        $sequence = null;

        while (true) {
            $key = new \VARIANT(null, \VT_I8);
            $this->dll->GetKey($key);
            $key = (int)$key;

            if (isset($this->sequences[$key])) {
                $sequence = $this->sequences;
            }

            if (isset($sequence[$key])) {
                if (\is_array($sequence[$key])) {
                    $sequence = $sequence[$key];
                } else {
                    return $sequence[$key];
                }
            } else {
                return $key;
            }
        }
    }

    private function interceptNIX(): int
    {
        \readline_callback_handler_install('', function () {
        });

        $sequence = null;

        while (true) {
            $s = [STDIN];
            $w = null;
            $e = null;

            if (stream_select($s, $w, $e, null)) {
                $key = \ord(\stream_get_contents(STDIN, 1));
                if (isset($this->sequences[$key])) {
                    $sequence = $this->sequences;
                }

                if (isset($sequence[$key])) {
                    if (\is_array($sequence[$key])) {
                        $sequence = $sequence[$key];
                    } else {
                        \readline_callback_handler_remove();
                        return $sequence[$key];
                    }
                } else {
                    \readline_callback_handler_remove();
                    return $key;
                }
            }
        }
    }

    public static function isWindows(): bool
    {
        return (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN');
    }

    public static function isCLI(): bool
    {
        switch (true) {
            case defined('STDIN'):
            case php_sapi_name() == 'cli':
            case (empty($_SERVER['REMOTE_ADDR']) && !isset($_SERVER['HTTP_USER_AGENT']) && count($_SERVER['argv']) > 0):
                return true;
                break;
            default:
                return false;
        }
    }

    public static function eprintln($value): void
    {
        if (self::isCLI() && defined('STDERR')) {
            fwrite(STDERR, $value . PHP_EOL);
        } else {
            echo $value;
        }
    }

    /**
     * Returns instance
     *
     * @return \Zenithies\Toolkit\ReadKey\Interceptor
     */
    public static function I(): Interceptor
    {
        $class = __CLASS__;

        if (self::$__instance === false) {
            self::$__instance = new $class();
        }

        return self::$__instance;
    }
}
