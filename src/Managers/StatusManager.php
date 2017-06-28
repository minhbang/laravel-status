<?php
namespace Minhbang\Status\Managers;

use Illuminate\Support\Collection;
use DB;
// TODO: Không dùng class này nữa, chuyển sang NewStatusManager
/**
 * Class StatusManager
 *
 * @package Minhbang\Status\Managers
 */
abstract class StatusManager
{
    /**
     * Giá trị status không hợp lệ
     */
    const STATUS_INVALID = -1;
    /**
     * Giá trị level không hợp lệ
     */
    const LEVEL_INVALID = -1;
    /**
     * @var \Eloquent
     */
    protected $model;
    /**
     * DB table name
     *
     * @var string
     */
    protected $table;
    /**
     * Table column names
     *
     * @var array
     */
    protected $tableColumns = [];

    /**
     * @var \Illuminate\Support\Collection;
     */
    protected $statuses;
    /**
     * Giá trị status editing (khi create content,...)
     *
     * @var int
     */
    protected $editingValue;
    /**
     * @var int
     */
    protected $publishedValue;
    /**
     * @var \Illuminate\Support\Collection;
     */
    protected $levels;
    /**
     * @var bool
     */
    protected $useLevel;
    /**
     * Giá trị status khi level up
     *
     * @var int
     */
    protected $levelUpValue;

    /**
     * Giá trị status khi level down
     *
     * @var int
     */
    protected $levelDownValue;

    /**
     * Giá trị level min
     *
     * @var int
     */
    protected $minLevel;

    /**
     * Giá trị level max
     *
     * @var int
     */
    protected $maxLevel;

    /**
     * Đĩnh nghĩa tất cả content statuses
     * [
     *      value:int => [
     *          'title' => string,
     *          ?'filter' => callback
     *          ?'can' => ['action' => callback|bool,...],
     *          ? 'default' => true,    // status mặc định
     *          ? 'level_up' => true,   // status khi level up
     *          ? 'level_down' => true, // status khi level down
     *          ? 'published' => true,  // status 'đã xuất bản'
     *      ],
     * ]
     *
     * @return array
     */
    abstract protected function defineStatuses();

    /**
     * Đĩnh nghĩa tất cả content levels:
     * [
     *      value:int => [
     *          'title' => string,
     *          'can' => ['action' => callback|bool,...],
     *          ?'filter' => callback
     *      ],
     * ]
     *
     * @return array
     */
    abstract protected function defineLevels();

    /**
     * StatusManager constructor.
     *
     * @param string $class
     */
    public function __construct($class)
    {
        $this->model = app($class);
        $this->table = $this->model->getTable();
        $this->tableColumns['status'] = $this->model->statusColumnName;
        $this->tableColumns['user_id'] = $this->model->userIdColumnName;
        $this->tableColumns['level'] = $this->model->levelColumnName;

        $this->statuses = new Collection($this->defineStatuses());
        $this->editingValue = $this->getKeyIf($this->statuses, 'editing');
        $this->publishedValue = $this->getKeyIf($this->statuses, 'published');
        abort_unless($this->editingValue && $this->publishedValue, 500, 'Invalid Statuses defined!');

        $this->levels = new Collection($this->defineLevels());
        $this->useLevel = !$this->levels->isEmpty();
        if ($this->useLevel) {
            $this->levelUpValue = $this->getKeyIf($this->statuses, 'level_up');
            $this->levelDownValue = $this->getKeyIf($this->statuses, 'level_down');
            abort_unless($this->levelUpValue && $this->levelDownValue, 500, 'Invalid Statuses defined!');
            $keys = $this->levels->keys();
            $this->minLevel = key($keys->first());
            $this->maxLevel = key($keys->last());
        }
    }

    /**
     * @param int $status
     *
     * @return bool
     */
    public function has($status)
    {
        return $status && $this->statuses->has($status);
    }

    /**
     * @return int
     */
    public function editingValue()
    {
        return $this->editingValue;
    }

    /**
     * Các giá trị statuses trạng thái content 'đã xuất bản'
     *
     * @return array
     */
    public function publishedValues()
    {
        return $this->publishedValue;
    }

    /**
     * @param string $attribute
     * @param int $status
     * @param mixed $default
     *
     * @return mixed
     */
    public function get($attribute, $status, $default = null)
    {
        return $this->getAttribute($this->statuses, $attribute, $status, $default);
    }

    /**
     * Danh sách các $attribute của status
     *
     * @param string $attribute
     * @param bool $key
     *
     * @return array
     */
    public function pluck($attribute = 'title', $key = true)
    {
        return $this->pluckAttribute($this->statuses, $attribute, $key);
    }

    /**
     * @param \Illuminate\Database\Query\Builder $query
     * @param int $status
     * @param bool $silent
     *
     * @return \Illuminate\Database\Query\Builder
     */
    public function filter($query, $status, $silent = true)
    {
        if (!$this->has($status)) {
            if ($silent) {
                $status = static::STATUS_INVALID;
            } else {
                abort(500, 'Status manager: invalid status!');
            }
        }
        if ($filter = $this->callAttribute($this->statuses, 'filter', $status, [user()])) {
            $query = $this->addQueryClauses($query, $filter);
        }

        return $query->where($this->getTableColumn('status'), $status);
    }

    /**
     * @param \Illuminate\Database\Query\Builder $query
     *
     * @return \Illuminate\Database\Query\Builder
     */
    public function filterPublished($query)
    {
        return $query->whereIn($this->getTableColumn('status'), $this->publishedValues());
    }

    /**
     * user() có thể thực hiện $action đối với $model
     * - Riêng action 'set' có thêm tham số $new_status
     * - Khi useLevel, sử dụng thuộc tính 'can' của level thay vì của status
     *
     * @param string $action
     * @param mixed $model
     * @param int $new_status
     *
     * @return bool
     */
    public function can($action, $model, $new_status = null)
    {
        $status = $model->{$this->getColumnName('status')};
        $level = $model->{$this->getColumnName('level')};

        if ($action === 'set') {
            if (!$this->has($new_status)) {
                return false;
            }
            if ($status == $new_status) {
                return true;
            }
        }

        return $this->callAttribute(
            $this->useLevel ? $this->levels : $this->statuses,
            $new_status ? "can.set.{$new_status}" : "can.{$action}",
            $this->useLevel ? $level : $status,
            [user(), $model],
            false
        );
    }

    /**
     * $model có thể chuyển sang những statuses nào? ==> [status:int => title:string]
     *
     * @param $model
     *
     * @return array
     */
    public function available($model)
    {
        $set = $this->get('can.set', $model->{$this->getColumnName('status')}, []);
        $available = [];
        if ($set) {
            foreach ($set as $value => $callback) {
                if ($this->call($callback, [user(), $model])) {
                    $available[$value] = $this->statuses->get($value)['title'];
                }
            }
        }

        return $available;
    }

    /**
     * Đếm content có $status
     *
     * @param int $status
     *
     * @return int
     */
    public function count($status)
    {
        return DB::table($this->table)->where($this->getColumnName('status'), $status)->count();
    }

    /**
     * Kiểm tra có phải $status 'đã xuất bản'?
     *
     * @param int $status
     *
     * @return bool
     */
    public function isPublished($status)
    {
        return $status && in_array($status, $this->publishedValues());
    }

    /**
     * @param string $column
     *
     * @return string
     */
    public function getTableColumn($column)
    {
        return "{$this->table}.{$this->getColumnName($column)}";
    }

    /**
     * @param string $column
     *
     * @return string
     */
    public function getColumnName($column)
    {
        return empty($this->tableColumns[$column]) ? $column : $this->tableColumns[$column];
    }

    /**
     * Lấy thuộc tính của $collection
     *
     * @param \Illuminate\Support\Collection $collection
     * @param string $attribute
     * @param int $id
     * @param mixed $default
     *
     * @return int|mixed
     */
    protected function getAttribute($collection, $attribute, $id, $default = null)
    {
        return array_get(
            $collection->get($id),
            $attribute,
            $default
        );
    }

    /**
     * @param \Illuminate\Support\Collection $collection
     * @param string $attribute
     * @param bool $key
     *
     * @return array
     */
    protected function pluckAttribute($collection, $attribute, $key = true)
    {
        $attributes = $collection->pluck($attribute);
        if ($key) {
            $attributes = $collection->keys()->combine($attributes);
        }

        return $attributes->all();
    }

    /**
     * Lấy key của $collection nếu thuộc tính $ifAttribute là TRUE
     *
     * @param \Illuminate\Support\Collection $collection
     * @param string $ifAttribute
     * @param mixed $default
     *
     * @return mixed
     */
    protected function getKeyIf($collection, $ifAttribute, $default = null)
    {
        $filtered = $collection->whereLoose($ifAttribute, true);

        return $filtered->isEmpty() ? $default : $filtered->keys()->first();
    }

    /**
     * @param \Illuminate\Support\Collection $collection
     * @param string $attribute
     * @param int $id
     * @param array $params
     * @param mixed $default
     *
     * @return mixed
     */
    protected function callAttribute($collection, $attribute, $id, $params = [], $default = null)
    {
        return $this->call($this->getAttribute($collection, $attribute, $id, $default), $params);
    }

    /**
     * @param mixed $callback
     * @param array $params
     *
     * @return mixed
     */
    protected function call($callback, $params = [])
    {
        return is_callable($callback) ? call_user_func_array($callback, $params) : $callback;
    }

    /**
     * Add clauses to $query, clause = ['clause', 'column', ...tham số của clause...]
     *
     * @param \Illuminate\Database\Query\Builder $query
     * @param array $clauses
     *
     * @return \Illuminate\Database\Query\Builder
     */
    protected function addQueryClauses($query, $clauses)
    {
        if (is_string($clauses[0])) {
            $clauses = [$clauses];
        }
        foreach ($clauses as $clause) {
            abort_if(!is_array($clause), 500, 'Status manage/Add query clause: invalid clause!');
            $method = array_shift($clause);
            $column = array_shift($clause);
            array_unshift($clause, $this->getTableColumn($column));
            call_user_func_array([$query, $method], $clause);
        }

        return $query;
    }

    // LEVEL------------------------------
    /**
     * @param int $level
     *
     * @return bool
     */
    public function hasLevel($level)
    {
        return $this->levels->has($level);
    }

    /**
     * @param mixed $model
     * @param int $level
     *
     * @return bool
     */
    public function fillLevel($model, $level = null)
    {
        $column = $this->getColumnName('level');
        $current = $model->{$column};
        $level = is_null($level) ? $this->minLevel : $level;
        if ($this->minLevel <= $level && $level <= $this->maxLevel) {
            $model->{$column} = $level;
        }

        return $current != $this->{$column};
    }


    /**
     * @param mixed $model
     *
     * @return bool
     */
    public function levelUp($model)
    {
        return $this->updateLevel($model, true);
    }

    /**
     * @param mixed $model
     *
     * @return bool
     */
    public function levelDown($model)
    {
        return $this->updateLevel($model, false);
    }

    /**
     * @param mixed $model
     * @param bool $up
     *
     * @return bool
     */
    public function updateLevel($model, $up = true)
    {
        if ($this->useLevel) {
            $level = $model->{$this->getColumnName('level')};
            if ($this->fillLevel($model, $level + ($up ? 1 : -1))) {
                $model->{$this->getColumnName('status')} = $up ? $this->levelUpValue : $this->levelDownValue;
            }

            return $level != $model->{$this->getColumnName('level')};
        } else {
            return false;
        }
    }

    /**
     * @param string $attribute
     * @param bool $key
     *
     * @return array
     */
    public function pluckLevel($attribute = 'title', $key = true)
    {
        return $this->pluckAttribute($this->levels, $attribute, $key);
    }

    /**
     * Lấy thuộc tính $attribute của $level
     *
     * @param string $attribute
     * @param int $level
     * @param mixed $default
     *
     * @return mixed
     */
    public function getLevel($attribute, $level, $default = null)
    {
        return $this->getAttribute($this->levels, $attribute, $level, $default);
    }

    /**
     * user() có thể thực hiện $action của $level
     *
     * @param string $action
     * @param int|string $level
     *
     * @return bool
     */
    public function canLevel($action, $level)
    {
        return $this->callAttribute(
            $this->levels,
            "can.{$action}",
            $level,
            [user(), $level],
            false
        );
    }

    /**
     * Đếm content ở $level
     *
     * @param int $level
     * @param bool $all
     *
     * @return int
     */
    public function countLevel($level, $all = false)
    {
        if ($this->useLevel) {
            $query = DB::table($this->table);
            $query = $all ?
                $query->where($this->getColumnName('level'), $level) :
                $this->filterLevel($query, $level);

            return $query->count();
        } else {
            return 0;
        }
    }

    /**
     * @param \Illuminate\Database\Query\Builder $query
     * @param int $level
     * @param bool $silent
     *
     * @return \Illuminate\Database\Query\Builder
     */
    public function filterLevel($query, $level, $silent = true)
    {
        if (!$this->hasLevel($level)) {
            if ($silent) {
                $level = static::LEVEL_INVALID;
            } else {
                abort(500, 'Level Status manager: invalid level!');
            }
        }
        if ($filter = $this->callAttribute($this->levels, 'filter', $level, [user()])) {
            $query = $this->addQueryClauses($query, $filter);
        }

        return $query->where($this->getTableColumn('level'), $level);
    }

    /**
     * Danh sách levels user() có thể manage, [level:int => title:string]
     *
     * @return array
     */
    public function availableLevel()
    {
        $levels = [];
        if ($this->useLevel) {
            $user = user();
            $this->levels->each(function ($item, $level) use (&$levels, $user) {
                if ($this->call(array_get($item, 'can.manage'), [$user])) {
                    $levels[$level] = array_get($item, 'title');
                }
            });
        }

        return $levels;
    }
}