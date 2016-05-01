<?php
namespace Minhbang\Status\Managers;

/**
 * Class LevelStatusManager
 * Mở rộng tính năng từ StatusManager, quản lý các statuses có chia level
 * - Giá trị level chẵn trăm: 100, 200, 300,...
 * - Ký hiệu status* là giá trị lưu DB, đã bao gồm level và status
 *
 * @package Minhbang\Status
 *
 * Danh sách các level attributes, key là value
 * @method array levelNames()
 * @method array levelValues()
 * @method array levelTitles()
 * @method array levelStatuses()
 *
 * Giá trị attribute theo $level (là value|name)
 * @method string levelName($level, $default = null)
 * @method int levelValue($level, $default = null)
 * @method string levelTitle($level, $default = null)
 * @method array levelStatus($level, $default = null)
 */
abstract class LevelStatusManager extends StatusManager
{
    const FACTOR_LEVEL = 100;
    /**
     * Các method có thể gọi public
     *
     * @var array
     */
    protected $public_methods = ['can*', 'check*', 'value*', 'level*'];

    /**
     * Các thuộc tính của level
     *
     * @var array
     */
    protected $level_attributes = ['name', 'value', 'title', 'status'];

    /**
     * Cached cho levels()
     *
     * @var \Illuminate\Support\Collection
     */
    protected $levels;

    /**
     * All levels
     * [
     *     'name'  => string,
     *     'value' => int,
     *     'title' => string,
     *     'status' => array (danh sách status* thuộc level này)
     * ]
     *
     * @return array
     */
    abstract protected function allLevels();

    /**
     * Giá trị status khi lên 1 level
     *
     * @return int
     */
    abstract public function valueLevelUp();

    /**
     * Giá trị status khi xuống 1 level
     *
     * @return int
     */
    abstract public function valueLevelDown();

    /**
     * Lấy level của status
     *
     * @param int $value
     *
     * @return int
     */
    protected function getLevel($value)
    {
        return $value - ($value % static::FACTOR_LEVEL);
    }

    /**
     * Lấy 'giá trị thật sự' của status
     *
     * @param int $value
     *
     * @return int
     */
    protected function getStatus($value)
    {
        return $value % static::FACTOR_LEVEL;
    }

    /**
     * @param int $level
     * @param int $status
     *
     * @return int
     */
    protected function encodeStatus($level, $status)
    {
        return $level + $status;
    }

    /**
     * Chuyển status XUỐNG 1 level
     *
     * @param int $level
     *
     * @return int
     */
    protected function getLevelDown($level)
    {
        return $level - static::FACTOR_LEVEL + $this->valueLevelDown();
    }

    /**
     * Chuyển status LÊN 1 level
     *
     * @param int $level
     *
     * @return int
     */
    protected function getLevelUp($level)
    {
        return $level + static::FACTOR_LEVEL + $this->valueLevelUp();
    }

    /**
     * $level có thể value | name
     *
     * @param int|string $level
     * @param string $attribute
     * @param mixed $default
     *
     * @return \Illuminate\Support\Collection|array|mixed
     */
    protected function levels($level = null, $attribute = null, $default = null)
    {
        if (empty($level)) {
            return empty($attribute) ? $this->levels : $this->levels->pluck($attribute, 'value')->all();
        } else {
            return array_get(
                $this->levels->where(is_numeric($level) ? 'value' : 'name', $level)->first(),
                $attribute,
                $default
            );
        }
    }

    /**
     * LevelStatusManager constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->levels = collect($this->allLevels());
    }

    /**
     * @param string $name
     * @param array $arguments
     *
     * @return array|\Illuminate\Support\Collection|mixed
     */
    public function __call($name, $arguments)
    {
        if ($attribute = $this->getMagicAttribute($name, 'level')) {
            return $this->callMagicMethod('levels', $attribute, $arguments);
        } else {
            return parent::__call($name, $arguments);
        }
    }
}