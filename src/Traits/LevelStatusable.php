<?php
namespace Minhbang\Status\Traits;
/**
 * Class LevelStatusable
 * Model có $level và $status
 *
 * @property bool $enableTags
 *
 * @package Minhbang\Status
 * @mixin \Eloquent
 */
trait LevelStatusable
{
    use Statusable;

    /**
     * Lấy content theo $level
     *
     * @param \Illuminate\Database\Query\Builder $query
     * @param int $level
     *
     * @return \Illuminate\Database\Query\Builder
     */
    public function scopeLevel($query, $level)
    {
        return $this->statusManager()->filterLevel($query, $level);
    }

    /**
     * @param int $level
     *
     * @return int
     */
    public function countLevel($level)
    {
        return $this->statusManager()->countLevel($level);
    }

    /**
     * Getter $level_title
     *
     * @return string
     */
    public function getLevelTitleAttribute()
    {
        return $this->statusManager()->getLevel('title', $this->levelValue());
    }

    /**
     * @param int $level
     *
     * @return bool
     */
    public function fillLevel($level = null)
    {
        return $this->statusManager()->fillLevel($this, $level);
    }


    /**
     * @param bool $up
     * @param bool $timestamps
     *
     * @return bool
     */
    public function updateLevel($up = true, $timestamps = false)
    {
        if ($this->statusManager()->updateLevel($this, $up)) {
            $this->timestamps = $timestamps;
            $this->enableTags = false;

            return $this->save();
        } else {
            return false;
        }
    }

    /**
     * @return int
     */
    public function levelValue()
    {
        return $this->{$this->statusManager()->getColumnName('level')};
    }
}