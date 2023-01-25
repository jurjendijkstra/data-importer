<?php
/*
 * AuthenticationValidator.php
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

namespace App\Services\Spectre;

use App\Exceptions\ImporterHttpException;
use App\Services\Enums\AuthenticationStatus;
use App\Services\Shared\Authentication\AuthenticationValidatorInterface;
use App\Services\Shared\Authentication\IsRunningCli;
use App\Services\Spectre\Authentication\SecretManager;
use App\Services\Spectre\Request\ListCustomersRequest;
use App\Services\Spectre\Response\ErrorResponse;

/**
 * Class AuthenticationValidator
 */
class AuthenticationValidator implements AuthenticationValidatorInterface
{
    use IsRunningCli;

    /**
     * @inheritDoc
     */
    public function validate(): AuthenticationStatus
    {
        app('log')->debug(sprintf('Now at %s', __METHOD__));

        $url    = config('spectre.url');
        $appId  = SecretManager::getAppId();
        $secret = SecretManager::getSecret();

        if ('' === $appId || '' === $secret) {
            return AuthenticationStatus::nodata();
        }

        $request = new ListCustomersRequest($url, $appId, $secret);
        $request->setTimeOut(config('importer.connection.timeout'));

        try {
            $response = $request->get();
        } catch (ImporterHttpException $e) {
            app('log')->error($e->getMessage());

            return AuthenticationStatus::error();
        }
        if ($response instanceof ErrorResponse) {
            app('log')->error(sprintf('%s: %s', $response->class, $response->message));

            return AuthenticationStatus::error();
        }

        return AuthenticationStatus::authenticated();
    }
}
