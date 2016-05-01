<?php
namespace Minhbang\Status\Managers;
/**
 * Class SimpleStatusManager
 *
 * @package Minhbang\Status\Managers
 */
class Simple extends StatusManager
{
    // Đang xử lý
    const STATUS_PROCESSING = 1;
    // Đã xuất bản
    const STATUS_PUBLISHED = 2;

    protected function allStatuses()
    {
        return [
            [
                'name'   => 'processing',
                'value'  => static::STATUS_PROCESSING,
                'title'  => 'Processing',
                'action' => 'Process',
                'css'    => 'default',
                'rule'   => [static::STATUS_PUBLISHED],
            ],
            [
                'name'   => 'published',
                'value'  => static::STATUS_PUBLISHED,
                'title'  => 'Published',
                'action' => 'Publish',
                'css'    => 'success',
                'rule'   => [static::STATUS_PROCESSING],
            ],
        ];
    }

    /**
     * @return int
     */
    public function valueDefault()
    {
        return static::STATUS_PROCESSING;
    }

    /**
     * @return array
     */
    public function valuesPublished()
    {
        return [static::STATUS_PUBLISHED];
    }

    /**
     * @param int|string $status
     *
     * @return bool
     */
    public function canDelete($status)
    {
        return !in_array($this->statusValue($status), $this->valuesPublished());
    }

    /**
     * @param int|string $status
     *
     * @return bool
     */
    public function canRead($status)
    {
        return in_array($this->statusValue($status), $this->valuesPublished());
    }

    /**
     * @param int|string $status
     *
     * @return bool
     */
    public function canUpdate($status)
    {
        return !in_array($this->statusValue($status), $this->valuesPublished());
    }
}