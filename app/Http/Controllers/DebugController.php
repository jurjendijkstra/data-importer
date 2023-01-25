<?php

/*
 * DebugController.php
 * Copyright (c) 2021 james@firefly-iii.org
 *
 * This file is part of the Firefly III Data Importer
 * (https://github.com/firefly-iii/data-importer).
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace App\Http\Controllers;

use Carbon\Carbon;
use Exception;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Monolog\Handler\RotatingFileHandler;

class DebugController extends Controller
{
    /**
     * Show debug info.
     *
     * @param Request $request
     *
     * @return Factory|View
     */
    public function index(Request $request)
    {
        app('log')->emergency('I am a EMERGENCY message.');
        app('log')->alert('I am a ALERT message.');
        app('log')->critical('I am a CRITICAL message.');
        app('log')->error('I am a ERROR message.');
        app('log')->warning('I am a WARNING message.');
        app('log')->notice('I am a NOTICE message.');
        app('log')->info('I am a INFO message.');
        app('log')->debug('I am a DEBUG message.');


        $search  = ['~', '#'];
        $replace = ['\~', '# '];

        $buildNr        = '(unknown)';
        $buildDate      = '(unknown)';
        $now            = Carbon::now()->format('Y-m-d H:i:s e');
        $phpVersion     = str_replace($search, $replace, PHP_VERSION);
        $phpOs          = str_replace($search, $replace, PHP_OS);
        $interface      = PHP_SAPI;
        $userAgent      = $request->header('user-agent');
        $trustedProxies = config('importer.trusted_proxies');
        $displayErrors  = ini_get('display_errors');
        $errorReporting = $this->errorReporting((int)ini_get('error_reporting'));
        $appEnv         = config('app.env');
        $appDebug       = var_export(config('app.debug'), true);
        $logChannel     = config('logging.default');
        $appLogLevel    = config('logging.level');
        $cacheDriver    = config('cache.default');
        $bcscale        = bcscale();
        $tz             = env('TZ');
        $isDocker       = env('IS_DOCKER', false);

        if (file_exists('/var/www/counter-main.txt')) {
            $buildNr = trim(file_get_contents('/var/www/counter-main.txt'));
        }
        if (file_exists('/var/www/build-date-main.txt')) {
            $buildDate = trim(file_get_contents('/var/www/build-date-main.txt'));
        }

        // get latest log file:
        $logger     = app('log')->driver();
        $handlers   = $logger->getHandlers();
        $logContent = '';
        foreach ($handlers as $handler) {
            if ($handler instanceof RotatingFileHandler) {
                $logFile = $handler->getUrl();
                if (null !== $logFile) {
                    try {
                        $logContent = file_get_contents($logFile);
                    } catch (Exception $e) { // @phpstan-ignore-line
                        // @ignoreException
                    }
                }
            }
        }
        if ('' !== $logContent) {
            // last few lines
            $logContent = 'Truncated from this point <----|' . substr($logContent, -8192);
        }
        if (true === config('importer.is_external')) {
            $logContent = 'No logs, external installation.';
        }

        return view(
            'debug',
            compact(
                'phpVersion',
                'appEnv',
                'appDebug',
                'logChannel',
                'tz',
                'appLogLevel',
                'now',
                'bcscale',
                'buildDate',
                'userAgent',
                'displayErrors',
                'errorReporting',
                'phpOs',
                'interface',
                'logContent',
                'buildNr',
                'cacheDriver',
                'trustedProxies',
                'isDocker'
            )
        );
    }

    /**
     * Some common combinations.
     *
     * @param int $value
     *
     * @return string
     */
    protected function errorReporting(int $value): string // get configuration
    {
        $array = [
            -1                                                             => 'ALL errors',
            E_ALL & ~E_NOTICE & ~E_STRICT & ~E_DEPRECATED                  => 'E_ALL & ~E_NOTICE & ~E_STRICT & ~E_DEPRECATED',
            E_ALL                                                          => 'E_ALL',
            E_ALL & ~E_DEPRECATED & ~E_STRICT                              => 'E_ALL & ~E_DEPRECATED & ~E_STRICT',
            E_ALL & ~E_NOTICE                                              => 'E_ALL & ~E_NOTICE',
            E_ALL & ~E_NOTICE & ~E_STRICT                                  => 'E_ALL & ~E_NOTICE & ~E_STRICT',
            E_COMPILE_ERROR | E_RECOVERABLE_ERROR | E_ERROR | E_CORE_ERROR => 'E_COMPILE_ERROR|E_RECOVERABLE_ERROR|E_ERROR|E_CORE_ERROR',
        ];

        return $array[$value] ?? (string)$value;
    }
}
