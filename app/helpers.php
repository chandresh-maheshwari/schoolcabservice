<?php

use Illuminate\Support\Facades\Log;

if (! function_exists('errorLog')) {
    /**
     * Custom error logger
     *
     * @param \Throwable|string $error
     * @return void
     */
    function errorLog($error)
    {
        if ($error instanceof \Throwable) {
            Log::error($error->getMessage(), [
                'file' => $error->getFile(),
                'line' => $error->getLine(),
                'trace' => $error->getTraceAsString(),
            ]);
        } else {
            Log::error($error);
        }
    }
}
