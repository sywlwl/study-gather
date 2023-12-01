<?php

namespace App\Server;

use Hyperf\Memory\TableManager;

// 保存所有channel 到 内存表
class LocalChannel
{
    private $clientKey = "clientId";
    private $fdKey = "fd";

    // 通过clientId 找到 clientInfo
    // 添加client
    public function addClient(string $clientId, TcpClientInfo $client): void
    {
        $this->removeClient($clientId);

        $clientTable = TableManager::get($this->clientKey);
        $clientTable->set($clientId, [
            'client' => serialize($client),
        ]);
        $fdTable = TableManager::get($this->fdKey);
        $fdTable->set(strval($client->fd), [
            'clientId' => $clientId,
        ]);
    }

    // 删除
    public function removeClient(string $clientId): void
    {
        $clientTable = TableManager::get($this->clientKey);
        $client = $this->getClient($clientId);

        if ($client) {
            $clientTable->del($clientId);
            // 删掉fd
            $fdTable = TableManager::get($this->fdKey);
            $fdTable->del(strval($client->fd));
        }
    }

    // 获取client
    public function getClient(string $clientId): ?TcpClientInfo
    {
        $clientTable = TableManager::get($this->clientKey);
        $clientString = $clientTable->get($clientId, 'client');
        if (is_string($clientString)) {
            $client = @unserialize($clientString);
            if (is_object($client) and $client instanceof TcpClientInfo) {
                return $client;
            }
        }
        return null;
    }

    // 通过fd 获取client
    public function getClientByFd(int $fd): ?TcpClientInfo
    {
        $clientId = $this->getClientIdByFd($fd);
        if ($clientId) {
            $client = $this->getClient($clientId);
            return $client;
        }
        return null;
    }

    // 通过fd 获取clientId
    public function getClientIdByFd(int $fd): ?string
    {
        // 删掉fd
        $fdTable = TableManager::get($this->fdKey);
        $clientId = $fdTable->get(strval($fd), 'clientId');
        return $clientId ?? null;
    }

    // 获取fd
    public function getFdByClientId(string $clientId): ?int
    {
        $client = $this->getClient($clientId);
        if ($client) {
            return $client->fd;
        }
        return null;
    }


    // 链接数
    public function count(): int
    {
        $clientTable = TableManager::get($this->clientKey);
        return $clientTable->count();
    }

}