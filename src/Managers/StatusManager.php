<?php
namespace Minhbang\Status\Managers;

/**
 * Class StatusManager
 * Quản lý statuses của content
 * - Giá trị status nguyên dương: 1, 2, 3,...
 *
 * @package Minhbang\Status
 *
 * Danh sách các attributes, key là value
 * @method array statusNames()
 * @method array statusValues()
 * @method array statusTitles()
 * @method array statusActions()
 * @method array statusCsses()
 * @method array statusRules()
 *
 * Giá trị attribute theo $status (là value|name)
 * @method string statusName($status, $default = null)
 * @method int statusValue($status, $default = null)
 * @method string statusTitle($status, $default = null)
 * @method string statusAction($status, $default = null)
 * @method string statusCss($status, $default = null)
 * @method array statusRule($status, $default = null)
 */
abstract class StatusManager
{
    /**
     * Các method có thể gọi public
     *
     * @var array
     */
    protected $public_methods = ['can*', 'check*', 'value*'];

    /**
     * Các thuộc tính của status
     *
     * @var array
     */
    protected $status_attributes = ['name', 'value', 'title', 'action', 'css', 'rule'];

    /**
     * @var \Illuminate\Support\Collection
     */
    protected $statuses;

    /**
     * Định nghĩa tất cả statuses, định dạng:
     * [
     *     [
     *         'name'   => string, // Status system name
     *         'value'  => int,    // Giá trị status lưu trong DB
     *         'title'  => string, // Tên hiển thị
     *         'action' => string, // Tên action để chuyển sang status này
     *         'css'    => string, // css class hiển thị status này
     *         'rule'    => array,  // danh sách statuses mà status này có thể chuyển sang
     *     ],
     * ]
     *
     * @return array
     */
    abstract protected function allStatuses();

    /**
     * Giá trị status mặc định (khi tạo mới...)
     *
     * @return int
     */
    abstract public function valueDefault();

    /**
     * Danh sách status cho trạng thái Đã xuất bản
     *
     * @return array
     */
    abstract public function valuesPublished();

    /**
     * User hiện tại có thể DELETE ebook này không?
     *
     * @param int|string $status
     *
     * @return bool
     */
    abstract public function canDelete($status);

    /**
     * User hiện tại có thể UPDATE ebook này không?
     *
     * @param int|string $status
     *
     * @return bool
     */
    abstract public function canUpdate($status);

    /**
     * User hiện tại có thể READ ebook này không?
     *
     * @param int|string $status
     *
     * @return bool
     */
    abstract public function canRead($status);

    /**
     * user() có thể thay đổi status của Resource từ $old_status thành $new_status được không?
     *
     * @param int|string $old_status
     * @param int|string $new_status
     *
     * @return array|bool
     */
    public function canChange($old_status, $new_status)
    {
        if (is_null($old_status) || is_null($new_status)) {
            return false;
        }
        $allowed = $this->statuses($old_status, 'rule');

        return $allowed && in_array($this->valueStatus($new_status), $allowed);
    }

    /**
     * @param int|string $status
     *
     * @return bool
     */
    public function checkPublished($status)
    {
        return in_array($this->valueStatus($status), $this->valuesPublished());
    }

    /**
     * Kiểm tra có định nghĩa $status không?
     *
     * @param int|string $status
     *
     * @return bool
     */
    public function checkStatus($status)
    {
        return $this->statuses->whereLoose(is_numeric($status) ? 'value' : 'name', $status)->count();
    }

    /**
     * Chuyển $status name thành value
     *
     * @param int|string $status
     *
     * @return int
     */
    public function valueStatus($status)
    {
        return is_numeric($status) ? (int)$status : $this->statuses((string)$status, 'value');
    }

    /**
     * $status có thể value | name
     *
     * @param int|string $status
     * @param string $attribute
     * @param mixed $default
     *
     * @return \Illuminate\Support\Collection|array|mixed
     */
    protected function statuses($status = null, $attribute = null, $default = null)
    {
        if (empty($status)) {
            return empty($attribute) ? $this->statuses : $this->statuses->pluck($attribute, 'value')->all();
        } else {
            return array_get(
                $this->statuses->whereLoose(is_numeric($status) ? 'value' : 'name', $status)->first(),
                $attribute,
                $default
            );
        }
    }


    /**
     * @param string $name
     *
     * @return bool
     */
    protected function isPublicMethod($name)
    {
        foreach ($this->public_methods as $pattern) {
            if (str_is($pattern, $name)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param string $method
     * @param string $of
     *
     * @return string|bool
     */
    protected function getMagicAttribute($method, $of)
    {
        return (
            str_is("{$of}*", $method) &&
            ($attribute = strtolower(str_singular(substr($method, strlen($of))))) &&
            in_array($attribute, $this->{"{$of}_attributes"})
        ) ? $attribute : false;
    }

    /**
     * @param string $name
     * @param string $attribute
     * @param array $arguments
     *
     * @return array|\Illuminate\Support\Collection|mixed
     */
    protected function callMagicMethod($name, $attribute, $arguments)
    {
        return $this->{$name}(
            isset($arguments[0]) ? $arguments[0] : null,
            $attribute,
            isset($arguments[1]) ? $arguments[1] : null
        );
    }

    /**
     * @param string $name
     * @param array $arguments
     *
     * @return mixed
     */
    public function __call($name, $arguments)
    {
        if ($attribute = $this->getMagicAttribute($name, 'status')) {
            return $this->callMagicMethod('statuses', $attribute, $arguments);
        } else {
            return $this->isPublicMethod($name) ? call_user_func_array([$this, $name], $arguments) : null;
        }
    }

    /**
     * StatusManager constructor.
     */
    public function __construct()
    {
        $this->statuses = collect($this->allStatuses());
    }
}