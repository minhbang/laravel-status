<?php
namespace Minhbang\Status\Traits;

use Carbon\Carbon;
use Status;

/**
 * Class Statusable
 * Trait cho Resource Model: Article, Document,...
 * - Model có property 'status' => trạng thái hiện tại
 *
 * @property string $table
 * @property int $status
 * @property string $statusManagerName
 * @property-read string $status_title
 * @package Minhbang\Status
 * @mixin \Eloquent
 */
trait Statusable
{
    /**
     * @return \Minhbang\Status\Managers\StatusManager
     */
    public function statusManager()
    {
        return Status::of(static::class);
    }

    /**
     * Lấy resources theo status, có thể dùng status value|name
     *
     * @param \Illuminate\Database\Query\Builder $query
     * @param int|string $status
     *
     * @return \Illuminate\Database\Query\Builder
     */
    public function scopeStatus($query, $status)
    {
        return $query->where("{$this->table}.status", '=', $this->statusManager()->valueStatus($status));
    }

    /**
     * @param \Illuminate\Database\Query\Builder $query
     *
     * @return \Illuminate\Database\Query\Builder
     */
    public function scopePublished($query)
    {
        return $query->whereIn("{$this->table}.status", $this->statusManager()->valuesPublished());
    }

    /**
     * @param int|string $status
     *
     * @return int
     */
    public function statusCount($status)
    {
        return static::where("{$this->table}.status", '=', $this->statusManager()->valueStatus($status))->count();
    }

    /**
     * @param int|string $status
     *
     * @return bool
     */
    public function statusCanSetTo($status)
    {
        return $this->statusManager()->canChange($this->status, $status);
    }

    /**
     * Model có thể chuyển sang những statuses nào?
     *
     * @return array
     */
    public function statusCan()
    {
        return $this->statusManager()->statusRule($this->status, []);
    }

    /**
     * @param int $status
     * @param bool $default
     *
     * @return bool
     */
    public function fillStatus($status, $default = true)
    {
        $new_status = ($status && $this->statusCanSetTo($status)) ?
            $this->statusManager()->valueStatus($status) :
            ($default ? $this->statusManager()->valueDefault() : false);
        if ($new_status) {
            $this->status = $new_status;
        }

        return $new_status;
    }

    /**
     * @param int $status
     * @param bool $timestamps
     * @param string $published_at
     *
     * @return bool
     */
    public function updateStatus($status, $timestamps = false, $published_at = null)
    {
        if ($this->fillStatus($status, false)) {
            if ($published_at && $this->statusManager()->checkPublished($status)) {
                $this->{$published_at} = Carbon::now();
            }
            $this->timestamps = $timestamps;
            $this->enableTags = false;

            return $this->save();
        } else {
            return false;
        }
    }

    /**
     * getter $status_title
     *
     * @return string|null
     */
    public function getStatusTitleAttribute()
    {
        return is_null($this->status) ? null : $this->statusManager()->statusTitle($this->status);
    }

    /**
     * @return bool
     */
    public function isPublished()
    {
        return $this->statusManager()->checkPublished($this->status);
    }
}
