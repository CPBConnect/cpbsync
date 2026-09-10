<?php

namespace CPBConnect\Application\Transform;

class ReplaceTextTransformer
{
    public function transform(
        $value,
        string $search,
        string $replace
    ): string {
        $value = (string) $value;

        if ($search === '') {
            return $value;
        }

        return str_replace(
            $search,
            $replace,
            $value
        );
    }
}