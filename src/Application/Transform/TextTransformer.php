<?php

namespace CPBConnect\Application\Transform;

class TextTransformer
{
    public function transform($value): string
    {
        $value = trim((string) $value);

        // Normalizar espacios múltiples
        $value = preg_replace('/\s+/', ' ', $value);

        return $value;
    }
}