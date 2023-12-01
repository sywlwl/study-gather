<?php

declare(strict_types=1);
/**
 * This file is part of Hyperf.
 *
 * @link     https://www.hyperf.io
 * @document https://hyperf.wiki
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */
namespace App\Server;

use App\Codec\Decoder;
use App\Server\AuthedChannel;
use App\Ws\WsAuthedChannelMemory;
use Hyperf\Contract\OnReceiveInterface;
use Hyperf\Di\Annotation\Inject;
use Hyperf\Utils\ApplicationContext;
use Psr\EventDispatcher\EventDispatcherInterface;
use Swoole\Server;

class TcpServer implements OnReceiveInterface
{
    #[Inject]
    public LocalChannel $localChannel;

    #[Inject]
    public AuthedChannel $authedChannel;

    public function __construct(protected EventDispatcherInterface $eventDispatcher)
    {
    }

    public function onReceive($server, int $fd, int $reactorId, string $data): void
    {
        $clientId = $this->localChannel->getClientIdByFd($fd);
        $this->eventDispatcher->dispatch(new Decoder($data, $clientId));
    }

    public function onConnect(Server $server, $fd, int $reactorId)
    {
        $info = $server->getClientInfo($fd, $reactorId);
        $client = new TcpClientInfo();
        $client->fd = $fd;
        $client->server_port = $info['server_port'];
        $client->server_fd = $info['server_fd'];
        $client->socket_fd = $info['socket_fd'];
        $client->socket_type = $info['socket_type'];
        $client->remote_port = $info['remote_port'];
        $client->remote_ip = $info['remote_ip'];
        $client->reactor_id = $info['reactor_id'];
        $client->connect_time = $info['connect_time'];
        $client->server_ip = swoole_get_local_ip();

        $clientId = md5(serialize($client));
        $this->localChannel->addClient($clientId, $client);


        echo '连接', "\n";
        $wschannel = ApplicationContext::getContainer()->get(WsAuthedChannelMemory::class);
        $wschannel->send("aaaa", "123");
    }

    public function onClose($server, $fd)
    {

        $clientId = $this->localChannel->getClientIdByFd($fd);
        if ($clientId) {
            $this->localChannel->removeClient($clientId);
        }
        echo '关闭', "\n";
        $this->authedChannel->removeChannelByClientId($clientId);
    }
}
