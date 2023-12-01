<?php

namespace App\Server;

class TcpClientInfo
{
    public int $fd;
    public int $server_port;
    public int $server_fd;
    public int $socket_fd;
    public int $socket_type;
    public int $remote_port;
    public string $remote_ip;
    public int $reactor_id;
    public int $connect_time;

    public array $server_ip;
}
