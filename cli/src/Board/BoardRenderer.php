<?php

declare(strict_types=1);

namespace Workflow\Board;

use Workflow\Domain\Item;
use Workflow\Domain\Status;

final class BoardRenderer
{
    private const COLUMNS = [
        Status::Draft,
        Status::Todo,
        Status::InProgress,
        Status::Done,
        Status::Cancelled,
    ];

    /**
     * @param array<string, Item> $items
     */
    public function render(string $projectName, array $items): string
    {
        $groups = [];
        foreach (self::COLUMNS as $column) {
            $groups[$column->value] = [];
        }
        foreach ($items as $item) {
            $groups[$item->status->value][] = $item;
        }

        $columns = '';
        foreach (self::COLUMNS as $column) {
            $cards = '';
            foreach ($groups[$column->value] as $item) {
                $cards .= $this->card($item);
            }

            $columns .= sprintf(
                '<section class="column"><h2>%s <span class="count">%d</span></h2>%s</section>',
                $this->escape($this->label($column)),
                count($groups[$column->value]),
                $cards,
            );
        }

        return <<<HTML
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta http-equiv="refresh" content="5">
<title>{$this->escape($projectName)} — workflow board</title>
<style>
:root { color-scheme: light dark; --bg:#f6f7f9; --panel:#fff; --ink:#1c2330; --muted:#667085; --line:#d9dee7; --accent:#2f6feb; }
@media (prefers-color-scheme: dark) { :root { --bg:#14181f; --panel:#1b212b; --ink:#e7ecf3; --muted:#98a2b3; --line:#2c3442; --accent:#7aa2ff; } }
* { box-sizing: border-box; }
body { margin:0; font:14px/1.45 system-ui, -apple-system, Segoe UI, sans-serif; background:var(--bg); color:var(--ink); }
header { padding:20px 24px 8px; }
h1 { margin:0 0 4px; font-size:20px; }
header p { margin:0; color:var(--muted); }
.board { display:flex; gap:16px; padding:16px 24px 32px; align-items:flex-start; overflow-x:auto; }
.column { flex:0 0 260px; background:var(--panel); border:1px solid var(--line); border-radius:10px; padding:12px; }
.column h2 { margin:0 0 10px; font-size:13px; text-transform:uppercase; letter-spacing:.04em; color:var(--muted); }
.count { float:right; font-weight:600; }
.card { border:1px solid var(--line); border-radius:8px; padding:10px; margin-bottom:8px; background:var(--bg); }
.card .id { color:var(--accent); font-weight:700; margin-right:6px; }
.card .meta { margin-top:4px; color:var(--muted); font-size:12px; }
.empty { color:var(--muted); font-size:12px; }
</style>
</head>
<body>
<header>
<h1>{$this->escape($projectName)}</h1>
<p>Generated {$this->escape(gmdate('Y-m-d H:i \U\T\C'))} · read-only · refreshes every 5 s</p>
</header>
<main class="board">
{$columns}
</main>
</body>
</html>
HTML;
    }

    private function card(Item $item): string
    {
        $meta = sprintf(
            '%s · %s · p%d',
            $item->kind->value,
            $item->phase->value,
            $item->priority,
        );

        return sprintf(
            '<article class="card"><span class="id">%s</span><span class="title">%s</span><div class="meta">%s</div></article>',
            $this->escape($item->id),
            $this->escape($item->title),
            $this->escape($meta),
        );
    }

    private function label(Status $status): string
    {
        return match ($status) {
            Status::Draft => 'Draft',
            Status::Todo => 'Todo',
            Status::InProgress => 'In progress',
            Status::Done => 'Done',
            Status::Cancelled => 'Cancelled',
        };
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
