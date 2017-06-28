<?php namespace Minhbang\Status\Traits;

//use Carbon\Carbon;
use Status;

/**
 * Class Statusable
 * Trait cho Resource Model: Article, Document,...
 * - Model có property 'status':string,max:40 => trạng thái hiện tại
 * - status 2 dạng: 'editing' hay 'level1.editing' (có/không dùng level)
 *
 * @property-read string $table
 * @property string $status
 * @package Minhbang\Status\Traits
 * @mixin \Eloquent
 */
trait Statusable {
    /**
     * Model có sẳn sàng cho $action được thực hiện bởi User $by
     *
     * @param string $action
     * @param mixed $by
     *
     * @return true
     */
    public function isReady( $action, $by = null ) {
        return $this->statusManager()->allowed( $action, $this, $by );
    }

    /**
     * Thử $newStatus xem có còn thực hiện được $action
     *
     * @param string $newStatus
     * @param string $action
     * @param mixed $by
     *
     * @return bool
     */
    public function attemptStatus( $newStatus, $action, $by = null ) {
        $result = false;
        if ( $newStatus ) {
            $curent = $this->status;
            $this->status = $newStatus;
            $result = $this->isReady( $action, $by );
            if ( ! $result ) {
                $this->status = $curent;
            }
        }

        return $result;
    }

    /**
     * @param string $action
     * @param mixed $by
     *
     * @return bool
     */
    public function attemptUpStatus( $action, $by = null ) {
        return $this->attemptStatus( $this->statusNextInfo( true, 'id' ), $action, $by );
    }

    /**
     * Lấy resources theo status
     *
     * @param \Illuminate\Database\Query\Builder $query
     * @param $action
     * @param mixed $by
     *
     * @return \Illuminate\Database\Query\Builder
     */
    public function scopeReady( $query, $action, $by = null ) {
        $statuses = $this->statusManager()->avaiableFor( $action, $by );

        return $statuses ? $query->whereIn( "{$this->table}.status", $statuses ) : $query->where( "{$this->table}.status", - 1 );
    }

    /**
     * @return \Minhbang\Status\Managers\NewStatusManager
     */
    public function statusManager() {
        return Status::of( static::class );
    }

    /**
     * Lấy thông tin về status của model
     *
     * @param string $attribute
     *
     * @return array|mixed
     */
    public function statusCurrentInfo( $attribute = null ) {
        return $this->statusManager()->get( $this->status, $attribute );
    }

    /**
     * Lấy thông tin status up/down của model
     *
     * @param bool $up
     * @param string $attribute
     *
     * @return array|mixed
     */
    public function statusNextInfo( $up = true, $attribute = null ) {
        return $this->statusManager()->next( $this->status, $up, $attribute );
    }

    /**
     * @return bool
     */
    public function canUpStatus() {
        return is_array( $this->statusNextInfo( true ) );
    }

    /**
     * @param bool $up
     *
     * @return boolean
     */
    public function updateNextStatus( $up = true ) {
        return ( $newStatus = $this->statusNextInfo( $up, 'id' ) ) && $newStatus && $this->update( [ 'status' => $newStatus ] );
    }

    /**
     * @return bool
     */
    public function updateUpStatus() {
        return $this->updateNextStatus( true );
    }

    /**
     * @return bool
     */
    public function updateDownStatus() {
        return $this->updateNextStatus( false );
    }

    /**
     * Lấy resources theo status
     *
     * @param \Illuminate\Database\Query\Builder $query
     * @param string $status
     *
     * @return \Illuminate\Database\Query\Builder
     */
    public function scopeStatus( $query, $status ) {
        return $status ? $query->where( "{$this->table}.status", $status ) : $query;
    }

    /**
     * @param int $status
     *
     * @return int
     */
    /*public function countStatus( $status ) {
        return $this->statusManager()->count( $status );
    }*/

    /**
     * @param string $action
     * @param int $status
     *
     * @return bool
     */
    /*public function can($action, $status = null)
    {
        return $this->statusManager()->can($action, $this, $status);
    }*/

    /**
     * Có thể chuyển sang những statuses nào?
     *
     * @return array
     */
    /*public function availableStatuses() {
        return $this->statusManager()->available( $this );
    }*/

    /**
     * @param int $status
     * @param bool $default
     *
     * @return bool
     */
    /*public function fillStatus( $status, $default = true ) {
        $status = $this->can( 'set',
            $status ) ? $status : ( $default ? $this->statusManager()->editingValue() : false );
        if ( $status ) {
            $this->{$this->statusManager()->getColumnName( 'status' )} = $status;
        }

        return $status;
    }*/

    /**
     * @param int $status
     * @param bool $timestamps
     * @param string $published_at
     *
     * @return bool
     */
    /*public function updateStatus( $status, $timestamps = false, $published_at = null ) {
        if ( $this->fillStatus( $status, false ) ) {
            if ( $published_at && $this->statusManager()->isPublished( $status ) ) {
                $this->{$published_at} = Carbon::now();
            }
            $this->timestamps = $timestamps;
            $this->enableTags = false;

            return $this->save();
        } else {
            return false;
        }
    }*/

    /**
     * Getter $status_title
     *
     * @return string
     */
    /*public function getStatusTitleAttribute() {
        return $this->statusManager()->get( 'title', $this->statusValue() );
    }*/

    /**
     * @return bool
     */
    /*public function isPublished() {
        return $this->statusManager()->isPublished( $this->statusValue() );
    }*/

    /**
     * @return int
     */
    /*public function statusValue() {
        return $this->{$this->statusManager()->getColumnName( 'status' )};
    }*/
}
