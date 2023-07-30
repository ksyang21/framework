<?php

namespace tables;

class Projects
{
    public array $columns = [
        'id'          => [
            'type'     => 'int',
            'pk'       => 1, // primary key
            'ai'       => 1, // auto increment
            'not_null' => 1, // cannot be null
        ],
        'name'        => [
            'type'     => 'varchar(255)',
            'not_null' => 1,
        ],
        'description' => [
            'type'    => 'varchar(255)',
            'comment' => 'Full description'
        ],
        'languages' => [
            'type'    => 'text',
            'comment' => 'languages used to build this project'
        ],
        'status'      => [
            'type'     => 'tinyint(1)',
            'default'  => 0,
            'not_null' => 1,
        ],
        'created_at'  => [
            'type'      => 'int',
            'default'   => 0,
            'not_null'  => 1,
            'timestamp' => 1,
        ]
    ];
}