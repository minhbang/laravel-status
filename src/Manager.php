<?php namespace Minhbang\Status;

use Illuminate\Support\Collection;

/**
 * Class Manager
 * Quản lý trạng thái của loại Model (tên class)
 *
 * @package Minhbang\Status
 */
class Manager extends Collection {
    /**
     * @param string|mixed $model Resource class name
     *
     * @return \Minhbang\Status\Managers\NewStatusManager
     */
    public function of( $model ) {
        $alias = kit()->alias( $model );
        abort_unless( $this->has( $alias ), 500, sprintf( "Unregistered '%s' status manager!", $alias ) );

        return $this->get( $alias );
    }

    /**
     * Đăng ký một status manager cho resource class $name
     * Thực hiện trong service provider của Resource có sử dụng Status
     *
     * @param string|mixed $model Resource class name
     * @param string $manager     Status manager class name
     */
    public function register( $model, $manager ) {
        $this->put( kit()->alias( $model ), new $manager( $model ) );
    }
}