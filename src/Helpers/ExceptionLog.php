<?php

namespace ModelsAlpha\Helpers;

class ExceptionLog
{
    public static function toArray(\Throwable $ex): array
    {
        try {
            return [
                'code' => method_exists($ex, 'getCode') ? $ex->getCode() : null,
                'msg' => method_exists($ex, 'getMessage') ? preg_replace( '/[\r\n\t]/', '', (string)$ex->getMessage() ) : null,
                'file' => method_exists($ex, 'getFile') ? $ex->getFile() . (method_exists($ex, 'getLine') ? ':' . $ex->getLine() : '') : null,
                'ex' => method_exists($ex, 'getTraceAsString') ? preg_replace( '/[\r\n\t]/', '', (string)$ex->getTraceAsString() ) : null,
            ];
        } catch (\Exception $exEx) {
            return [
                'ex' => get_class($ex),
                'exEx' => get_class($exEx)
            ];
        }
    }
}