<?php
namespace Minhbang\Status\Traits;
/**
 * Class LevelStatusable
 * Status nâng cao: chia các statuses thành các level khác nhau
 * @method \Minhbang\Status\Managers\LevelStatusManager statusManager()
 *
 * @package Minhbang\Status
 */
trait LevelStatusable
{
    use Statusable;

    /**
     * Lấy resources theo status level, có thể dùng level value|name
     *
     * @param \Illuminate\Database\Query\Builder $query
     * @param int|string $level
     *
     * @return \Illuminate\Database\Query\Builder
     */
    public function scopeStatusLevel($query, $level)
    {
        return $query->whereIn("{$this->table}.status", $this->statusManager()->levelStatus($level));
    }
    
}