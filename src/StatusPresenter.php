<?php
namespace Minhbang\Status;

use Form;
use Html;

/**
 * Class StatusPresenter
 *
 * @property-read \Minhbang\Status\Traits\Statusable $entity
 * @package Minhbang\Status
 * @mixin \Minhbang\Kit\Extensions\Model
 */
trait StatusPresenter
{
    /**
     * @param string $url
     * @param bool $reload
     *
     * @return string
     */
    public function statusActions($url, $reload = true)
    {
        $actions = $this->entity->statusManager()->statusActions();
        $csses = $this->entity->statusManager()->statusCsses();
        $statuses = $this->entity->statusCan();
        $html = '';
        foreach ($statuses as $status) {
            $html .= Html::linkButton(
                str_replace('STATUS', $status, $url),
                $actions[$status],
                ['type' => $csses[$status], 'size' => 'xs', 'class' => $reload ? 'post-link-normal' : 'post-link']
            );
        }

        return '<div class="m-b-xs">' . $html . '</div>';
    }

    /**
     * @return string
     */
    public function statusFormatted()
    {
        $statuses = $this->entity->statusManager()->statusTitles();
        $csses = $this->entity->statusManager()->statusCsses();

        return "<span class=\"label label-{$csses[$this->entity->status]}\">{$statuses[$this->entity->status]}</span>";
    }

    /**
     * @param string $url
     * @param string $name
     *
     * @return string
     */
    public function status($url, $name = 'status')
    {
        $statuses = $this->entity->statusManager()->statusTitles();
        $csses = $this->entity->statusManager()->statusCsses();
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

        return Form::select($name, $lists, $this->entity->status, ['class' => 'select-btngroup', 'data-size' => 'xs']);
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
        $statuses = $this->entity->statusManager()->statusTitles();
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