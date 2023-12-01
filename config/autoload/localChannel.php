<?php

// 本地channel 内存表配置
return [
    // 以clientId为key 存的内容是client
    'clientId' => [
        'size' => 10000 * 45,
        'columns' => [
            ['client', \Swoole\Table::TYPE_STRING, 1024],
        ],
    ],
    // key是fd 保存内容是clientId
    'fd' => [
        'size' => 10000 * 45,
        'columns' => [
            ['clientId', \Swoole\Table::TYPE_STRING, 64],
        ],
    ]
];