<?php

/*
 * TransactionProcessor.php
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

namespace App\Services\Spectre\Conversion\Routine;

use App\Exceptions\ImporterHttpException;
use App\Services\Shared\Configuration\Configuration;
use App\Services\Spectre\Authentication\SecretManager as SpectreSecretManager;
use App\Services\Spectre\Model\Transaction;
use App\Services\Spectre\Request\GetTransactionsRequest;
use App\Services\Spectre\Request\PutRefreshConnectionRequest;
use App\Services\Spectre\Response\ErrorResponse;
use App\Services\Spectre\Response\GetTransactionsResponse;
use Carbon\Carbon;

/**
 * Class TransactionProcessor
 */
class TransactionProcessor
{
    private const DATE_TIME_FORMAT = 'Y-m-d H:i:s';
    private Configuration $configuration;
    private string        $downloadIdentifier;
    private ?Carbon       $notAfter;
    private ?Carbon       $notBefore;

    /**
     * @return array
     * @throws ImporterHttpException
     */
    public function download(): array
    {
        $this->refreshConnection();
        $this->notBefore = null;
        $this->notAfter  = null;
        if ('' !== (string)$this->configuration->getDateNotBefore()) {
            $this->notBefore = new Carbon($this->configuration->getDateNotBefore());
        }

        if ('' !== (string)$this->configuration->getDateNotAfter()) {
            $this->notAfter = new Carbon($this->configuration->getDateNotAfter());
        }

        app('log')->debug('Now in download()');
        $accounts = array_keys($this->configuration->getAccounts());
        app('log')->debug(sprintf('Found %d accounts to download from.', count($this->configuration->getAccounts())));
        $return = [];
        foreach ($accounts as $account) {
            $account = (string)$account;
            app('log')->debug(sprintf('Going to download transactions for account #%s', $account));
            $url     = config('spectre.url');
            $appId   = SpectreSecretManager::getAppId();
            $secret  = SpectreSecretManager::getSecret();
            $request = new GetTransactionsRequest($url, $appId, $secret);
            $request->setTimeOut(config('importer.connection.timeout'));
            $request->accountId    = $account;
            $request->connectionId = $this->configuration->getConnection();
            /** @var GetTransactionsResponse $transactions */
            $transactions = $request->get();
            /*
             * Getting a Response object means that the Transaction objects are basically cast back into an array making this
             * exercise pretty pointless (from array to object back to array).
             *
             * Does mean however that we can normalise the data before we start using it.
             */
            $return[$account] = $this->filterTransactions($transactions);
        }
        $final = [];
        foreach ($return as $set) {
            $final = array_merge($final, $set);
        }

        return $final;
    }

    /**
     * @throws ImporterHttpException
     */
    private function refreshConnection(): void
    {
        // refresh connection
        $url    = config('spectre.url');
        $appId  = SpectreSecretManager::getAppId();
        $secret = SpectreSecretManager::getSecret();
        $put    = new PutRefreshConnectionRequest($url, $appId, $secret);
        $put->setConnection($this->configuration->getConnection());
        $response = $put->put();
        if ($response instanceof ErrorResponse) {
            app('log')->alert('Could not refresh connection.');
            app('log')->alert(sprintf('%s: %s', $response->class, $response->message));
        }
    }

    /**
     * @param GetTransactionsResponse $transactions
     *
     * @return array
     */
    private function filterTransactions(GetTransactionsResponse $transactions): array
    {
        app('log')->info(sprintf('Going to filter downloaded transactions. Original set length is %d', count($transactions)));
        if (null !== $this->notBefore) {
            app('log')->info(sprintf('Will not grab transactions before "%s"', $this->notBefore->format('Y-m-d H:i:s')));
        }
        if (null !== $this->notAfter) {
            app('log')->info(sprintf('Will not grab transactions after "%s"', $this->notAfter->format('Y-m-d H:i:s')));
        }
        $return = [];
        /** @var Transaction $transaction */
        foreach ($transactions as $transaction) {
            $madeOn = $transaction->madeOn;

            if (null !== $this->notBefore && $madeOn->lt($this->notBefore)) {
                app('log')->debug(
                    sprintf(
                        'Skip transaction because "%s" is before "%s".',
                        $madeOn->format(self::DATE_TIME_FORMAT),
                        $this->notBefore->format(self::DATE_TIME_FORMAT)
                    )
                );
                continue;
            }
            if (null !== $this->notAfter && $madeOn->gt($this->notAfter)) {
                app('log')->debug(
                    sprintf(
                        'Skip transaction because "%s" is after "%s".',
                        $madeOn->format(self::DATE_TIME_FORMAT),
                        $this->notAfter->format(self::DATE_TIME_FORMAT)
                    )
                );

                continue;
            }
            app('log')->debug(sprintf('Include transaction because date is "%s".', $madeOn->format(self::DATE_TIME_FORMAT), ));
            $return[] = $transaction;
        }
        app('log')->info(sprintf('After filtering, set is %d transaction(s)', count($return)));

        return $return;
    }

    /**
     * @param Configuration $configuration
     */
    public function setConfiguration(Configuration $configuration): void
    {
        $this->configuration = $configuration;
    }

    /**
     * @param string $downloadIdentifier
     */
    public function setDownloadIdentifier(string $downloadIdentifier): void
    {
        $this->downloadIdentifier = $downloadIdentifier;
    }
}
