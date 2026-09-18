<?php

namespace App\Libraries;

class SecturiFoliosOficiales
{
    private const FOLIOS = [
        'TA' => [
            'folio' => '1020',
            'sub_folios' => [
                'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M',
                'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z',
                'AA', 'AB', 'AC', 'AD', 'AF', 'AG', 'AH',
            ],
        ],
        'TH' => [
            'folio' => '1111',
            'sub_folios' => ['A', 'B', 'G'],
        ],
    ];

    public static function normalizeClave(string $clave): string
    {
        return strtoupper(trim($clave));
    }

    public static function normalizeSubFolio(?string $subFolio): string
    {
        $subFolio = strtoupper(trim((string) $subFolio));
        return preg_replace('/[^A-Z]/', '', $subFolio);
    }

    public static function folioPadre(string $clave): string
    {
        $clave = self::normalizeClave($clave);
        return (string) (self::FOLIOS[$clave]['folio'] ?? '');
    }

    public static function subFolios(string $clave): array
    {
        $clave = self::normalizeClave($clave);
        return self::FOLIOS[$clave]['sub_folios'] ?? [];
    }

    public static function contiene(string $clave, string $folio, string $subFolio): bool
    {
        $clave = self::normalizeClave($clave);
        $folio = preg_replace('/\D+/', '', $folio);
        $subFolio = self::normalizeSubFolio($subFolio);

        return $folio !== ''
            && $folio === self::folioPadre($clave)
            && in_array($subFolio, self::subFolios($clave), true);
    }

    public static function siguienteSubFolio(string $clave, string $subFolio): ?string
    {
        $subFolios = self::subFolios($clave);
        $subFolio = self::normalizeSubFolio($subFolio);
        $index = array_search($subFolio, $subFolios, true);
        if ($index === false) {
            return null;
        }

        return $subFolios[$index + 1] ?? null;
    }

    public static function desdeSubFolio(string $clave, ?string $subFolio): array
    {
        $subFolios = self::subFolios($clave);
        $subFolio = self::normalizeSubFolio($subFolio);
        if ($subFolio === '') {
            return [];
        }

        $index = array_search($subFolio, $subFolios, true);
        if ($index === false) {
            return [];
        }

        return array_slice($subFolios, $index);
    }
}
