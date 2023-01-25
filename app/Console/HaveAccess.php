<?php
/*
 * HaveAccess.php
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

namespace App\Console;

use GrumpyDictator\FFIIIApiSupport\Exceptions\ApiHttpException;
use GrumpyDictator\FFIIIApiSupport\Request\SystemInformationRequest;

/**
 * Trait HaveAccess
 */
trait HaveAccess
{
    /**
     * @return bool
     */
    private function haveAccess(): bool
    {
        $url     = (string)config('importer.url');
        $token   = (string)config('importer.access_token');
        $request = new SystemInformationRequest($url, $token);

        $request->setVerify(config('importer.connection.verify'));
        $request->setTimeOut(config('importer.connection.timeout'));

        try {
            $request->get();
        } catch (ApiHttpException $e) {
            $this->error(sprintf('Could not connect to Firefly III at %s: %s', $url, $e->getMessage()));

            return false;
        }

        return true;
    }

    /**
     * @param      $string
     * @param null $verbosity
     *
     * @return void
     */
    abstract public function error($string, $verbosity = null);

    /**
     * @param string $path
     *
     * @return bool
     */
    private function isAllowedPath(string $path): bool
    {
        $error = 'No valid paths in IMPORT_DIR_WHITELIST, cannot continue.';
        $paths = config('importer.import_dir_whitelist');
        if (null === $paths) {
            $this->warn($error);

            return false;
        }
        if (is_array($paths) && 0 === count($paths)) {
            $this->warn($error);

            return false;
        }
        if (is_array($paths) && 1 === count($paths) && '' === $paths[0]) {
            $this->warn($error);

            return false;
        }

        return in_array($path, $paths, true);
    }
}
