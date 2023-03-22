<?php
chdir(__DIR__);

$loader = __DIR__ . '/../vendor/autoload.php';

if (file_exists($loader)) {
    include $loader;
} else {// Attempt to include class directly
    include __DIR__ . '/../src/Zenithies/Toolkit/ReadKey/Interceptor.php';
}

$keys = \Zenithies\Toolkit\ReadKey\Interceptor::I();

if (!$keys->init()) {
    throw new \Exception('Key reading is not available');
}

while (true) {
    $key = $keys->intercept();

    switch ($key) {
        case 113: // q to quit
        case 81:  // Q to quit
            \Zenithies\Toolkit\ReadKey\Interceptor::eprintln("Quit registered: {$key}");
            exit(0);
            break;
        case \Zenithies\Toolkit\ReadKey\Interceptor::KEY_UP:
            \Zenithies\Toolkit\ReadKey\Interceptor::eprintln('Arrow key up');
            break;
        case \Zenithies\Toolkit\ReadKey\Interceptor::KEY_DOWN:
            \Zenithies\Toolkit\ReadKey\Interceptor::eprintln('Arrow key down');
            break;
        case \Zenithies\Toolkit\ReadKey\Interceptor::KEY_LEFT:
            \Zenithies\Toolkit\ReadKey\Interceptor::eprintln('Arrow key left');
            break;
        case \Zenithies\Toolkit\ReadKey\Interceptor::KEY_RIGHT:
            \Zenithies\Toolkit\ReadKey\Interceptor::eprintln('Arrow key right');
            break;
        default:
            \Zenithies\Toolkit\ReadKey\Interceptor::eprintln("Keycode: {$key}");
    }
}