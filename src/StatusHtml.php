<?php
namespace Minhbang\Status;

use Form;
use Html;

/**
 * Class StatusHtml
 *
 * @property-read \Minhbang\Status\Traits\Statusable $entity
 * @package Minhbang\Status
 * @mixin \Minhbang\Kit\Extensions\Model
 */
trait StatusHtml
{
    /**
     * @param \Minhbang\Status\Traits\Statusable $model
     * @param string $url
     * @param bool $reload
     *
     * @return string
     */
    public function statusActions($model, $url, $reload = true)
    {
        $actions = $model->statusManager()->statusActions();
        $csses = $model->statusManager()->statusCsses();
        $statuses = $model->availableStatuses();
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
     * @param \Minhbang\Status\Traits\Statusable $model
     *
     * @return string
     */
    public function statusFormatted($model)
    {
        $statuses = $model->statusManager()->statusTitles();
        $csses = $model->statusManager()->statusCsses();

        return "<span class=\"label label-{$csses[$model->status]}\">{$statuses[$model->status]}</span>";
    }

    /**
     * @param \Minhbang\Status\Traits\Statusable $model
     * @param string $url
     * @param string $group_by
     * @param string $name
     *
     * @return string
     */
    public function status($model, $url, $group_by = null, $name = 'status')
    {
        $statuses = $model->statusManager()->statusTitles($group_by);
        $csses = $model->statusManager()->statusCsses();
        if (!$group_by) {
            $statuses = ['' => $statuses];
        }
        $lists = [];
        foreach ($statuses as $group => $items) {
            if ($group) {
                $lists[] = $this->statusOptionItem($group, $group);
            }
            foreach ($items as $status => $title) {
                $lists[] = $this->statusOptionItem($status, $title, $url, $csses[$status]);
            }
        }
        
        return Form::select($name, $lists, $model->status, ['class' => 'select-btngroup', 'data-size' => 'xs']);
    }

    /**
     * @param int $status
     * @param stirng $title
     * @param string $url
     * @param string $css
     *
     * @return array
     */
    protected function statusOptionItem($status, $title, $url = '#', $css = 'group')
    {
        return [
            'value'      => $status,
            'text'       => $title,
            'attributes' => [
                'data-url'  => str_replace('STATUS', $status, $url),
                'data-type' => $css,
            ],
        ];
    }

    /**
     * @param \Minhbang\Status\Traits\Statusable $model
     * @param int $current
     * @param string $url
     * @param string $size
     * @param string $active
     * @param string $default
     *
     * @return array
     */
    public function buttons($model, $current, $url, $size = 'sm', $active = 'primary', $default = 'white')
    {
        $statuses = $model->statusManager()->statusTitles();
        $buttons = [];
        foreach ($statuses as $status => $title) {
            $count = $model->countStatus($status);
            $buttons[] = [
                str_replace('STATUS', $status, $url),
                $title . ($count ? ' <strong class="text-danger">(' . $count . ')</strong>' : ''),
                ['size' => $size, 'type' => $status == $current ? $active : $default],
            ];
        }

        return $buttons;
    }
}