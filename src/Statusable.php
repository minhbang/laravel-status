<?php
namespace Minhbang\Status;

/**
 * Class Statusable
 * Trait cho Resource Model: Article, Document,...
 * - Model có property 'status' => trạng thái hiện tại
 *
 * @property string $table
 * @property int $status
 * @package Minhbang\Status
 * @mixin \Eloquent
 */
trait Statusable
{
    /**
     * @var array
     */
    protected static $status_titles;

    /**
     * Định nghĩa tất cả statuses
     *
     * @return array
     */
    abstract public function statuses();

    /**
     * Các css class cho các statuses
     *
     * @return mixed
     */
    abstract public function statusCss();

    /**
     * Các action thay đổi status
     *
     * @return array
     */
    abstract public function statusActions();

    /**
     * Map từ status này có thể action chuyển sang các statuses nào?
     *
     * @param null|int $status
     *
     * @return array
     */
    abstract public function statusCanUpdate($status = null);

    /**
     * Lấy resources theo status
     *
     * @param \Illuminate\Database\Query\Builder $query
     * @param int $status
     *
     * @return \Illuminate\Database\Query\Builder
     */
    public function scopeStatus($query, $status)
    {
        return $query->where("{$this->table}.status", '=', $status);
    }

    /**
     * @param int $status
     *
     * @return int
     */
    public function statusCount($status)
    {
        return static::where("{$this->table}.status", '=', $status)->count();
    }

    /**
     * Lấy status titles
     * - $status = null: tất cả status, dạng: ['status code' => 'status title,...']
     * - $status !=null: nếu tồn tại ==> 'status title', ngược lại ==> FALSE
     *
     * @param null|int $status
     *
     * @return array|string|false
     */
    public function statusTitles($status = null)
    {
        if (!self::$status_titles) {
            $instance = new static();
            self::$status_titles = $instance->statuses();
        }

        return is_null($status) ?
            self::$status_titles :
            (isset(self::$status_titles[$status]) ? self::$status_titles[$status] : false);
    }

    /**
     * Lấy tất cả status values, không 'title'
     *
     * @return array
     */
    public function statusValues()
    {
        return array_keys($this->statusTitles());
    }

    /**
     * @return string
     */
    public function statusTitle()
    {
        $title = $this->statusTitles($this->status);
        if (is_array($title)) {
            $title = current($title);
        }

        return $title;
    }

    /**
     * @param int $status
     *
     * @return bool
     */
    public function statusUpdate($status)
    {
        if (!is_null($status) && $this->statusCanUpdate($status)) {
            $this->timestamps = false;
            $this->status = $status;

            return $this->save();
        } else {
            return false;
        }
    }
}
