<?php

namespace App\Signal;

use Hyperf\Signal\Annotation\Signal;
use Hyperf\Signal\SignalHandlerInterface;

//#[Signal]
class TermSignalHandler implements SignalHandlerInterface
{

    public function listen(): array
    {
        return [
            [SignalHandlerInterface::PROCESS, SIGTERM],
        ];
    }

    public function handle(int $signal): void
    {
        var_dump('dddddd');
        var_dump($signal);
    }
}