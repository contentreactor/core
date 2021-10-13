<?php

namespace Developion\Core\events;

use yii\base\Event;

class DeleteRenderedContentEvent extends Event
{
    const EVENT_AFTER_DELETE_RENDERED_CONTENT = 'afterDeleteRenderedContent';

    /**
     * @var array The registered URL rules.
     */
    public $pages = [];
}
