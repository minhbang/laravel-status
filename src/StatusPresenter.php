<?php namespace Minhbang\Status;

use Form;
use Html;

/**
 * Class StatusPresenter
 *
 * @property-read \Minhbang\Status\Traits\Statusable $entity
 * @package Minhbang\Status
 * @mixin \Minhbang\Kit\Extensions\Model
 */
trait StatusPresenter {
    /**
     * Render Trạng thái
     *
     * @return string
     */
    public function status() {
        return ( $status = $this->entity->statusManager()->get( $this->entity->status ) ) ?
            "<span class=\"label label-{$status['css']}\">{$status['title']}</span>" : null;
    }

    /**
     * Render Trạng thái có tính năng Quick Update
     * Cho phép all giá trị status -> chỉ dùng cho quản lý cao nhất (không chạy qui trình kiểm duyệt)
     *
     * @param string $url
     * @param string $name
     * @param array $options
     *
     * @return string
     */
    public function statusQuickUpdate( $url, $name = 'status', $options = [] ) {
        $lists = [];
        foreach ( $this->entity->statusManager()->all() as $status ) {
            $lists[] = [
                'value'      => $status['id'],
                'text'       => $status['title'],
                'attributes' => [
                    'data-url'  => str_replace( 'STATUS', $status['id'], $url ),
                    'data-type' => $status['css'],
                ],
            ];
        }

        return Form::select( $name, $lists, $this->entity->status, $options + [ 'class' => 'select-btngroup', 'data-size' => 'xs' ] );
    }

    /**
     * @param string $url
     * @param bool $disabled
     * @param bool $up
     * @param array $attributes
     * @param array $params
     *
     * @return string
     */
    public function statusButton( $url, $disabled = false, $up = true, $attributes = [], $params = [] ) {
        if ( $next = $this->entity->statusNextInfo( $up ) ) {
            if ( $disabled ) {
                $url = '#';
                mb_attributes_addclass( $attributes, 'disabled' );
            } else {
                if ( ! $next['to_link'] ) {
                    $url = str_replace( 'edit_up', 'status_up', $url );
                    mb_attributes_addclass( $attributes, 'post-link' );
                }
                $attributes += [ 'type' => $next['css'], 'size' => 'xs', 'icon' => false, 'raw' => false ];
            }

            return Html::linkButton( $url, $next['to_title'], $attributes, $params );
        } else {
            return '';
        }
    }

    /**
     * @param string $url
     * @param bool $disabled
     * @param array $attributes
     * @param array $params
     *
     * @return string
     */
    public function statusUpButton( $url, $disabled = false, $attributes = [], $params = [] ) {
        return $this->statusButton( $url, $disabled, true, $attributes, $params );
    }

    /**
     * @param string $url
     * @param bool $disabled
     * @param array $attributes
     * @param array $params
     *
     * @return string
     */
    public function statusDownButton( $url, $disabled = false, $attributes = [], $params = [] ) {
        return $this->statusButton( $url, $disabled, false, $attributes, $params );
    }
}