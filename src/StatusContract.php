<?php
namespace Minhbang\Status;
/**
 * Interface StatusContract
 * Trạng thái của Resource
 *
 * @property int $status
 * @package Minhbang\Status
 */
interface StatusContract
{
    /**
     * Định nghĩa tất cả statuses
     *
     * @return array
     */
    public function statuses();

    /**
     * Các css class cho các statuses
     *
     * @return mixed
     */
    public function statusCss();

    /**
     * Các action thay đổi status
     *
     * @return array
     */
    public function statusActions();

    /**
     * @param \Illuminate\Database\Query\Builder $query
     * @param int $status
     *
     * @return \Illuminate\Database\Query\Builder
     */
    public function scopeStatus($query, $status);

    /**
     * @param int $status
     *
     * @return int
     */
    public function statusCount($status);

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

    /**
     * Map từ status này có thể action chuyển sang các statuses nào?
     * Có $status thì kiểm tra luôn, không lấy danh sách
     *
     * @param null|int $status
     *
     * @return array|bool
     */
    public function statusCanUpdate($status = null);
}
