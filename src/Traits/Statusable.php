<?php
namespace Minhbang\Status\Traits;

use Carbon\Carbon;
//use Status;

/**
 * Class Statusable
 * Trait cho Resource Model: Article, Document,...
 * - Model có property 'status' => trạng thái hiện tại
 *
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
        return $this->statusManager()->filter($query, $status);
    }

    /**
     * @param \Illuminate\Database\Query\Builder $query
     *
     * @return \Illuminate\Database\Query\Builder
     */
    public function scopePublished($query)
    {
        return $this->statusManager()->filterPublished($query);
    }

    /**
     * @param int $status
     *
     * @return int
     */
    public function countStatus($status)
    {
        return $this->statusManager()->count($status);
    }

    /**
     * @param string $action
     * @param int $status
     *
     * @return bool
     */
    public function can($action, $status = null)
    {
        return $this->statusManager()->can($action, $this, $status);
    }

    /**
     * Có thể chuyển sang những statuses nào?
     *
     * @return array
     */
    public function availableStatuses()
    {
        return $this->statusManager()->available($this);
    }

    /**
     * @param int $status
     * @param bool $default
     *
     * @return bool
     */
    public function fillStatus($status, $default = true)
    {
        $status = $this->can('set', $status) ? $status : ($default ? $this->statusManager()->editingValue() : false);
        if ($status) {
            $this->{$this->statusManager()->getColumnName('status')} = $status;
        }

        return $status;
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
            if ($published_at && $this->statusManager()->isPublished($status)) {
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
     * Getter $status_title
     *
     * @return string
     */
    public function getStatusTitleAttribute()
    {
        return $this->statusManager()->get('title', $this->statusValue());
    }

    /**
     * @return bool
     */
    public function isPublished()
    {
        return $this->statusManager()->isPublished($this->statusValue());
    }

    /**
     * @return int
     */
    public function statusValue()
    {
        return $this->{$this->statusManager()->getColumnName('status')};
    }
}
