<?php
namespace Zenithies\Toolkit\ReadKey;

class Interceptor
{
    // Windows Sequence: 224 + 72,77,80,75
    // Nix Sequence: 27 + 91 + 65,67,66,68

    const KEY_LEFT  = 28;
    const KEY_RIGHT = 29;
    const KEY_UP    = 30;
    const KEY_DOWN  = 31;

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
    }

    public function init(): bool
    {
        if (self::isWindows()) {
            if (!\class_exists('COM')) {
                self::eprintln('Warning: COM extension si require On Windows: extension=php_com_dotnet (PHP 8.1)');
                return false;
            }

            try {
                $this->dll = new \COM('ZenithiesCLIKeys.ReadKey');
            } catch (\Throwable $e) {
                self::eprintln("Unable to initialize ZenithiesCLIKeys.ReadKey, make sure it is registered by regsvr32 and you've picked architecture matching your PHP installation: {$e->getMessage()}");
                return false;
            }

            $this->sequences = [
                224 => [
                    72 => self::KEY_UP, // Up
                    77 => self::KEY_RIGHT, // Right
                    80 => self::KEY_DOWN, // Down
                    75 => self::KEY_LEFT, // Left
                ],
                0 => [
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
        \readline_callback_handler_install('', function(){});

        $sequence = null;

        while (true) {
            $s = [STDIN]; $w = null; $e = null;

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
            self::$__instance = new $class;
        }

        return self::$__instance;
    }
}