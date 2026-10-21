<?php

declare(strict_types=1);

namespace Workflow\Domain;

use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

final class FrontMatter
{
    /**
     * @return array{data: array<string, mixed>, body: string}
     */
    public static function parse(string $raw): array
    {
        $raw = str_replace("\r\n", "\n", $raw);

        if (!str_starts_with($raw, "---\n")) {
            throw new InvalidItemException('Missing opening "---" front matter delimiter.');
        }

        $end = strpos($raw, "\n---\n", 4);
        if ($end === false) {
            if (str_ends_with($raw, "\n---")) {
                $end = strlen($raw) - 4;
            } else {
                throw new InvalidItemException('Missing closing "---" front matter delimiter.');
            }
        }

        // The newline before the delimiter belongs to YAML block scalars.
        $yaml = substr($raw, 4, $end - 3);
        $body = substr($raw, $end + 5);

        try {
            $data = Yaml::parse($yaml);
        } catch (ParseException $exception) {
            throw new InvalidItemException('Invalid YAML front matter: '.$exception->getMessage(), 0, $exception);
        }

        if ($data === null) {
            $data = [];
        }

        if (!is_array($data)) {
            throw new InvalidItemException('Front matter must be a YAML mapping.');
        }

        return ['data' => $data, 'body' => ltrim($body, "\n")];
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function render(array $data, string $body): string
    {
        $yaml = Yaml::dump($data, 8, 2, Yaml::DUMP_MULTI_LINE_LITERAL_BLOCK | Yaml::DUMP_EMPTY_ARRAY_AS_SEQUENCE);
        // Keep scalar newlines intact, adding only a missing delimiter boundary.
        if (!str_ends_with($yaml, "\n")) {
            $yaml .= "\n";
        }
        $body = ltrim(str_replace("\r\n", "\n", $body), "\n");

        return "---\n".$yaml."---\n\n".rtrim($body)."\n";
    }
}
