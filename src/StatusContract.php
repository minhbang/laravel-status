<?php
namespace Minhbang\Status;
/**
 * Interface ResourceStatus
 * Trạng thái của Resource
 *
 * @package Minhbang\AccessControl\Contracts
 */
interface StatusContract
{
    /**
     * @param \Illuminate\Database\Query\Builder $query
     * @param int $status
     *
     * @return \Illuminate\Database\Query\Builder
     */
    public function scopeStatus($query, $status);

    /**
     * Lấy status titles
     * - $status = null: tất cả status, dạng: ['status code' => 'status title,...']
     * - $status !=null: nếu tồn tại ==> 'status title', ngược lại ==> FALSE
     *
     * @param null|int $status
     *
     * @return array|string|false
     */
    public function statusTitles($status = null);

    /**
     * Lấy tất cả status values, không 'title'
     *
     * @return array
     */
    public function statusValues();

    /**
     * @return string
     */
    public function statusTitle();

    /**
     * @param int $status
     *
     * @return bool
     */
    public function statusUpdate($status);
}
