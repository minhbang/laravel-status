<?php namespace Minhbang\Status\Managers;

use Illuminate\Support\Collection;
use Closure;

/**
 * Class NewStatusManager
 * Quản lý status
 * - Status value có dạng: <value> hoặc <level>.<value>
 *
 * @package Minhbang\Status\Managers
 */
abstract class NewStatusManager {
    /**
     * @var \Illuminate\Support\Collection
     */
    protected $statuses;
    /**
     * @var string
     */
    protected $model;
    /**
     * Có phân cấp theo level
     *
     * @var bool
     */
    protected $useLevel;

    // Sử dụng khi trans() status
    protected $trans_prefix = null;

    /**
     * @return string
     */
    abstract public function defaultStatus();

    /**
     * Định nghĩa all statuses: [<status1>,<status2>,...]
     * Format một status:
     * [
     *      'value' => string
     *      'actions' => [
     *          'name(|other)*' => bool | Closure: function ( $model, User $user )
     *      ]
     *      ('css' => 'css class',)?
     *      ('up' => 'status value')?
     *      ('down' => 'status value')?
     * ]
     *
     * Chú ý:
     * - action name có thể nhiều actions: action1 | action2 | action3...
     * - Nếu sử dụng level thì có thêm tham số level trong từng status
     *
     * @return array
     */
    abstract protected function allStatuses();

    /**
     * AbsManager constructor.
     *
     * @param string $model
     */
    public function __construct( $model ) {
        $this->model = $model;
        $statuses = $this->allStatuses();
        $this->useLevel = isset( $statuses[0]['level'] );
        $this->statuses = new Collection( $this->serializeStatuses( $statuses ) );
    }

    /**
     * Chuyển đổi all statuses viết ngắn gọn thành đầy đủ
     * - action name dạng 'action1|action2...' => value, chuyển đổi thành các item đơn: 'action1' => value
     * - id của status: value nếu không level, ngươc lại level.value
     * - Dịch title, up_title, down_title
     *
     * @param array $statuses
     *
     * @return array
     */
    protected function serializeStatuses( $statuses ) {
        $result = [];
        foreach ( $statuses as $status ) {
            $id = ( $this->useLevel ? $status['level'] . '.' : '' ) . $status['value'];
            $actions = [];
            foreach ( (array) mb_array_extract( 'actions', $status, [] ) as $name => $checker ) {
                foreach ( explode( '|', $name ) as $action ) {
                    $actions[$action] = $checker;
                }
            }
            $status['title'] = $this->trans( $status['value'] );
            $status['to_title'] = $this->trans( 'to_' . $status['value'] );
            $status += [ 'to_link' => false, 'css' => 'default' ];
            $result[$id] = $status + [ 'actions' => $actions, 'id' => $id ];
        }

        return $result;
    }

    /**
     * Có cho phép user $by thực hiện $action đối với $model
     *
     * @param string $action
     * @param \Minhbang\Status\Traits\Statusable $model
     * @param mixed $by
     *
     * @return bool
     */
    public function allowed( $action, $model, $by = null ) {
        $status = $model->status ?: $this->defaultStatus();
        $checker = $this->getChecker( $action, $status );

        return $checker instanceof Closure ? $checker( $model, user_model( $by ) ) : $checker;
    }

    /**
     * Danh sách statuses để $by có thể thực hiện $action
     *
     * @param string $action
     * @param mixed $by
     *
     * @return string[]|Collection
     */
    public function avaiableFor( $action, $by = null ) {
        $user = user_model( $by, false );

        return $this->statuses->filter( function ( $status ) use ( $action, $user ) {
            $checker = isset( $status['actions'][$action] ) ? $status['actions'][$action] : false;

            return $checker instanceof Closure ? $checker( null, $user ) : $checker;
        } )->pluck( 'id' );
    }

    /**
     * @return array
     */
    public function all() {
        return $this->statuses->all();
    }

    /**
     * @param string $status
     *
     * @return bool
     */
    public function has( $status ) {
        return $this->statuses->has( $status );
    }

    /**
     * Lấy thông tin của $status (hoặc chỉ $attribute của nó)
     *
     * @param string $status
     * @param string $attribute
     *
     * @return null|string|mixed
     */
    public function get( $status, $attribute = null ) {
        return array_get( $this->statuses->get( $status ), $attribute );
    }

    /**
     * Lấy thông tin của up/down $status (hoặc chỉ $attribute của nó)
     *
     * @param string $status
     * @param bool $up
     * @param string $attribute
     *
     * @return null|string|mixed
     */
    public function next( $status, $up = true, $attribute = null ) {
        $name = $up ? 'up' : 'down';
        $info = $this->get( $status );

        return is_array( $info ) && isset( $info[$name] ) ? $this->get( $info[$name], $attribute ) : null;
    }

    /**
     * Lấy thông tin của up $status (hoặc chỉ $attribute của nó)
     *
     * @param string $status
     * @param string $attribute
     *
     * @return null|string|mixed
     */
    public function up( $status, $attribute = null ) {
        return $this->next( $status, true, $attribute );
    }

    /**
     * Lấy thông tin của up $status (hoặc chỉ $attribute của nó)
     *
     * @param string $status
     * @param string $attribute
     *
     * @return null|string|mixed
     */
    public function down( $status, $attribute = null ) {
        return $this->next( $status, false, $attribute );
    }

    /**
     * Tất cả statuses, nhóm theo level (nếu use level) => sử dụng cho admin statuses selectize
     *
     * @param bool $level
     *
     * @return array
     */
    public function groupByLevel( $level = true ) {
        $titles = $this->levelTitles();

        return $level && $this->useLevel ?
            $this->statuses->groupBy( function ( $item ) use ( $titles ) {
                return array_get( $titles, $item['level'], $item['level'] );
            } )->map( function ( Collection $statuses ) {
                return $this->forSelectize( $statuses );
            } )->all() :
            $this->forSelectize( $this->statuses );
    }

    /**
     * @param \Illuminate\Support\Collection $statuses
     *
     * @return array
     */
    public function forSelectize( Collection $statuses ) {
        return $statuses->mapWithKeys( function ( $item ) {
            return [ ( $this->useLevel ? "{$item['level']}." : '' ) . $item['value'] => $item['title'] ];
        } )->all();
    }

    /**
     * @param string $action
     * @param string $status
     *
     * @return boolean
     */
    protected function getChecker( $action, $status ) {
        $checker = false;
        $s = $this->statuses->get( $status );
        if ( $s && isset( $s['actions'][$action] ) ) {
            $checker = $s['actions'][$action];
        }

        return $checker;
    }

    /**
     * Định nghĩa title cho các level
     *
     * @return array
     */
    protected function levelTitles() {
        return [ '_' => 'All' ];
    }

    /**
     * @param string $str
     *
     * @return string
     */
    protected function trans( $str ) {
        return $this->trans_prefix ? trans( "{$this->trans_prefix}{$str}" ) : ucwords( str_replace( '_', ' ', $str ) );
    }
}