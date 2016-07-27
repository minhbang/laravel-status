<?php
namespace Minhbang\Status\Managers;

use Minhbang\User\User;

/**
 * Class Simple
 *
 * @package Minhbang\Status\Managers
 */
class Simple extends StatusManager
{
    // Đang xử lý
    const STATUS_EDITING = 1;
    // Đã xuất bản
    const STATUS_PUBLISHED = 2;

    /*
     * Định nghĩa tất cả content statuses
     */
    protected function defineStatuses()
    {
        return [
            static::STATUS_EDITING   => [
                'title'   => 'Editing',
                'can'     => [
                    'read'   => false,
                    'update' => true,
                    'delete' => true,
                    'set'    => [
                        static::STATUS_PUBLISHED => true,
                    ],
                ],
                'filter'  => function (User $user) {
                    return ['where', 'user_id', $user->id];
                },
                'editing' => true,
            ],
            static::STATUS_PUBLISHED => [
                'title'     => 'Published',
                'can'       => [
                    'read'   => true,
                    'update' => true,
                    'delete' => true,
                    'set'    => [
                        static::STATUS_EDITING => true,
                    ],
                ],
                'filter'    => function (User $user) {
                    return ['where', 'user_id', $user->id];
                },
                'published' => true,
            ],
        ];
    }

    /**
     * @return array
     */
    protected function defineLevels()
    {
        return [];
    }

}