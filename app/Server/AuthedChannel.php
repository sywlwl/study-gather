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

use App\Codec\Encoder;
use App\Codec\Frame;
use Hyperf\Di\Annotation\Inject;
use Hyperf\Redis\Redis;
use Psr\EventDispatcher\EventDispatcherInterface;

class AuthedChannel
{
    protected string $key = 'channels';

    #[Inject]
    protected LocalChannel $localChannel;

    #[Inject]
    protected Redis $redis;

    public function __construct(protected EventDispatcherInterface $eventDispatcher)
    {
//        $this->config = $config->get('channel');
//        $key = $this->config['type'];
//        $this->key = $this->config[$key]['identifier'];
    }

    public function addChannel(string $pile, string $clientId): void
    {
        $value = [
            'pile' => $pile,
            'clientId' => $clientId,
            'timestamp' => strval(time()),
        ];
        $this->redis->hSet($this->key, $pile, json_encode($value));
    }

    public function getChannel(string $pile): ?string
    {
        $value = $this->redis->hGet($this->key, $pile);
        if ($value === false) {
            return null;
        }
        $ret = json_decode($value, true);
        return $ret['clientId'];
    }

    public function getChannels(): array
    {
        $ret = $this->redis->hGetAll($this->key);
        $map = [];
        if ($ret) {
            foreach ($ret as $pile => $value) {
                $value = json_decode($value, true);
                $map[$pile] = $value;
            }
        }
        return $map;
    }

    public function removeChannel(string $pile): void
    {
        $this->redis->hDel($this->key, $pile);
    }

    public function removeChannelByClientId(string $clientId): void
    {
        $ret = $this->redis->hGetAll($this->key);
        if ($ret) {
            foreach ($ret as $pile => $value) {
                $value = json_decode($value, true);
                if ($clientId == $value['clientId']) {
                    $this->removeChannel(strval($pile));
                    break;
                }
            }
        }
    }

    public function hasChannel(string $pile): bool
    {
        return $this->redis->hExists($this->key, $pile);
    }

    public function send(string $pile, Frame $frame): void
    {
        if ($this->hasChannel($pile)) {
            echo "有桩号 ", $pile, "\n";
            $clientId = $this->getChannel($pile);
            // 本地查一下 有没有这个clientId;
            $client = $this->localChannel->getClient($clientId);

            if ($client) {
                $this->eventDispatcher->dispatch(new Encoder($frame, $clientId));
            } else {
                echo "本地没找到\n";
            }
        }
    }

    /**
     * 当前在线终端.
     */
    public function count(): int
    {
        $count = 0;
        $channels = $this->getChannels();
        foreach ($channels as $pile => $value) {
            if (time() - intval($value['timestamp']) > 60) {
                $this->removeChannel(strval($pile));
                $fd = $this->localChannel->getFdByClientId($value['clientId']);
                if ($fd) {
                    $this->eventDispatcher->dispatch(new Close($fd));
                }
            } else {
                ++$count;
            }
        }
        return $count;
    }
}
