<?php

declare(strict_types=1);

namespace Workflow\Board;

use Workflow\Domain\WorkflowException;
use Workflow\Read\ProjectView;
use Workflow\Store\ItemStore;

final class BoardWriter
{
    public function write(ItemStore $store): string
    {
        if (!is_dir($store->workflowDir())) {
            throw new WorkflowException(sprintf('Workflow directory does not exist: %s', $store->workflowDir()));
        }

        $html = (new BoardRenderer())->render(
            basename($store->root()),
            (new ProjectView($store))->items(),
        );

        $path = $store->workflowDir().'/board.html';
        $temporary = $path.'.'.bin2hex(random_bytes(6)).'.tmp';

        if (file_put_contents($temporary, $html) === false) {
            throw new WorkflowException(sprintf('Cannot write board: %s', $temporary));
        }

        if (!rename($temporary, $path)) {
            @unlink($temporary);
            throw new WorkflowException(sprintf('Cannot replace board: %s', $path));
        }

        return $path;
    }
}
