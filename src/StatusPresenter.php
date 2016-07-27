<?php
namespace Minhbang\Status;

use Form;
use Html;

/**
 * Class StatusPresenter
 *
 * @property-read StatusContract|\Minhbang\Kit\Extensions\Model $entity
 * @package Minhbang\Status
 * @mixin \Minhbang\Kit\Extensions\Model
 */
trait StatusPresenter
{
    /**
     * @param strung $url
     * @param bool $reload
     *
     * @return string
     */
    public function statusActions($url, $reload = true)
    {
        $actions = $this->entity->statusActions();
        $css = $this->entity->statusCss();
        $statuses = $this->entity->statusCanUpdate();
        $html = '';
        foreach ($statuses as $status) {
            $html .= Html::linkButton(
                str_replace('STATUS', $status, $url),
                $actions[$status],
                ['type' => $css[$status], 'size' => 'xs', 'class' => $reload ? 'post-link-normal' : 'post-link']
            );
        }

        return '<div class="m-b-xs">' . $html . '</div>';
    }

    /**
     * @return string
     */
    public function statusFormatted()
    {
        $statuses = $this->entity->statuses();
        $csses = $this->entity->statusCss();

        return "<span class=\"label label-{$csses[$this->entity->status]}\">{$statuses[$this->entity->status]}</span>";
    }

    /**
     * @param string $url
     *
     * @return string
     */
    public function status($url)
    {
        $statuses = $this->entity->statuses();
        $csses = $this->entity->statusCss();
        $lists = [];
        foreach ($statuses as $status => $title) {
            $lists[] = [
                'value'      => $status,
                'text'       => $title,
                'attributes' => [
                    'data-url'  => str_replace('STATUS', $status, $url),
                    'data-type' => $csses[$status],
                ],
            ];
        }

        return Form::select('status', $lists, $this->entity->status, ['class' => 'select-btngroup', 'data-size' => 'xs']);
    }

    /**
     * @param int $current
     * @param string $url
     * @param string $size
     * @param string $active
     * @param string $default
     *
     * @return array
     */
    public function buttons($current, $url, $size = 'sm', $active = 'primary', $default = 'white')
    {
        $statuses = $this->entity->statuses();
        $buttons = [];
        foreach ($statuses as $status => $title) {
            $count = $this->entity->statusCount($status);
            $buttons[] = [
                str_replace('STATUS', $status, $url),
                $title . ($count ? ' <strong class="text-danger">(' . $count . ')</strong>' : ''),
                ['size' => $size, 'type' => $status == $current ? $active : $default],
            ];
        }

        return $buttons;
    }
}