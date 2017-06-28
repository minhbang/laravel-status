<?php namespace Minhbang\Status\Managers;

use Minhbang\User\User;
use Minhbang\User\Support\HasOwner;
use Authority;

/**
 * Class Simple
 * Status Manager đơn giãn: 2 trạng thái (đang biên tập, đã xuất bản)
 *
 * @package Minhbang\Status\Managers
 */
class Simple extends NewStatusManager {
    /**
     * @return string
     */
    public function defaultStatus() {
        return 'editing';
    }

    /**
     *
     * @return array
     */
    protected function allStatuses() {
        return [
            [
                'value'   => 'editing',
                'actions' => [
                    'read|update|delete' => function ( $model, User $user ) {
                        /** @var HasOwner $model */
                        return $user && ( Authority::user( $user )->isAdmin() || ( $model && $model->isOwnedBy( $user ) ) );
                    },
                ],
                'up'      => 'published',
                'css'     => 'default',
            ],
            [
                'value'   => 'published',
                'actions' => [
                    'read'          => true,
                    'update|delete' => function ( $model, User $user ) {
                        /** @var HasOwner $model */
                        return $user && ( Authority::user( $user )->isAdmin() || ( $model && $model->isOwnedBy( $user ) ) );
                    },
                ],
                'down'    => 'editing',
                'css'     => 'success',
            ],
        ];
    }
}