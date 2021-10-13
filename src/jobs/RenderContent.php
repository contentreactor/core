<?php

namespace Developion\Core\jobs;

use Craft;
use craft\queue\BaseJob;

class RenderContent extends BaseJob
{
    public function __construct(
        protected array $pages
    ){
        parent::__construct([]);
    }
    /**
     * @inheritdoc
     */
    public function execute($queue): void
    {
        $user_pass = '';
        if (!empty(getenv('DEV_USER'))) {
            $user_pass = ' --user ' . getenv('DEV_USER') . ' --password ' . getenv('DEV_PASS');
        }
        $total = count($this->pages);
        try {
            foreach( $this->pages as $i => $page ) {
                $this->setProgress(
                    $queue,
                    $i / $total,
                    Craft::t(
                        'core',
                        '{step, number} of {total, number}',
                        [
                            'step' => $i + 1,
                            'total' => $total,
                        ]
                    )
                );
                exec("wget -q$user_pass -P " . CRAFT_BASE_PATH . '/web/api-render/ ' . $page);
            }
        } catch (\Throwable $e) {
            // Don’t let an exception block the queue
            \Craft::warning("Something went wrong: {$e->getMessage()}", __METHOD__);
        }
    }

    /**
     * @inheritdoc
     */
    protected function defaultDescription(): string
    {
        return \Craft::t('app', 'Regenerate cached pages and images.');
    }
}
