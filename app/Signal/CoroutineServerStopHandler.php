<?php

declare(strict_types=1);

namespace App\Signal;

use Hyperf\AsyncQueue\Driver\Driver;
use Hyperf\Contract\ConfigInterface;
use Hyperf\Process\ProcessManager;
use Hyperf\Server\ServerManager;
use Hyperf\Signal\Annotation\Signal;
use Hyperf\Signal\SignalHandlerInterface;
use Psr\Container\ContainerInterface;


//#[Signal]
class CoroutineServerStopHandler implements SignalHandlerInterface
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var ConfigInterface
     */
    protected $config;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->config = $container->get(ConfigInterface::class);
    }

    public function listen(): array
    {
        // 协程风格只会存在一个 Worker 进程，故这里只需要监听 WORKER 即可
        return [
            [self::WORKER, SIGTERM],
            [self::WORKER, SIGINT],
        ];
    }

    public function handle(int $signal): void
    {
        var_dump($signal);
        ProcessManager::setRunning(false);

        foreach (ServerManager::list() as [$type, $server]) {
            // 循环关闭开启的服务
            var_dump($type);
            $server->shutdown();
        }
    }
}