<?php
/*
 * AccountInformationCollector.php
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

namespace App\Services\Nordigen\Services;

use App\Exceptions\AgreementExpiredException;
use App\Exceptions\ImporterErrorException;
use App\Exceptions\ImporterHttpException;
use App\Services\Nordigen\Model\Account;
use App\Services\Nordigen\Model\Balance;
use App\Services\Nordigen\Request\GetAccountBalanceRequest;
use App\Services\Nordigen\Request\GetAccountBasicRequest;
use App\Services\Nordigen\Request\GetAccountInformationRequest;
use App\Services\Nordigen\Response\ArrayResponse;
use App\Services\Nordigen\TokenManager;

/**
 * Class AccountInformationCollector
 *
 * Collects meta information and more on the given Account
 */
class AccountInformationCollector
{
    /**
     * @param Account $account
     *
     * @return Account
     * @throws AgreementExpiredException
     */
    public static function collectInformation(Account $account): Account
    {
        app('log')->debug(sprintf('Now in %s', __METHOD__));

        // you know nothing, Jon Snow
        $detailedAccount = $account;
        try {
            $detailedAccount = self::getAccountDetails($account);
        } catch (ImporterHttpException|ImporterErrorException $e) {
            app('log')->error($e->getMessage());
            // ignore error otherwise for now.
            $detailedAccount->setStatus('no-info');
            $detailedAccount->setName('Unknown account');
        }
        $balanceAccount = $detailedAccount;

        try {
            $balanceAccount = self::getBalanceDetails($account);
        } catch (ImporterHttpException|ImporterErrorException $e) {
            app('log')->error($e->getMessage());
            // ignore error otherwise for now.
            $status = $balanceAccount->getStatus();
            if ('no-info' === $status) {
                $balanceAccount->setStatus('nothing');
            }
            if ('no-info' !== $status) {
                $balanceAccount->setStatus('no-balance');
            }
        }

        // also collect some extra information, but don't use it right now.
        return self::getBasicDetails($balanceAccount);
    }

    /**
     * @param Account $account
     *
     * @return Account
     * @throws ImporterErrorException
     * @throws ImporterHttpException
     * @throws AgreementExpiredException
     */
    protected static function getAccountDetails(Account $account): Account
    {
        app('log')->debug(sprintf('Now in %s(%s)', __METHOD__, $account->getIdentifier()));

        $url         = config('nordigen.url');
        $accessToken = TokenManager::getAccessToken();
        $request     = new GetAccountInformationRequest($url, $accessToken, $account->getIdentifier());
        /** @var ArrayResponse $response */

        $response = $request->get();

        if (!array_key_exists('account', $response->data)) {
            app('log')->error('Missing account array', $response->data);
            throw new ImporterHttpException('No account array, exit.');
        }

        $information = $response->data['account'];

        app('log')->debug('getAccountDetails: Collected information for account', $information);

        $account->setResourceId($information['resource_id'] ?? '');
        $account->setBban($information['bban'] ?? '');
        $account->setBic($information['bic'] ?? '');
        $account->setCashAccountType($information['cashAccountType'] ?? '');
        $account->setCurrency($information['currency'] ?? '');

        $account->setDetails($information['details'] ?? '');
        $account->setDisplayName($information['displayName'] ?? '');
        $account->setIban($information['iban'] ?? '');
        $account->setLinkedAccounts($information['linkedAccounts'] ?? '');
        $account->setMsisdn($information['msisdn'] ?? '');
        $account->setName($information['name'] ?? '');
        $account->setOwnerName($information['ownerName'] ?? '');
        $account->setProduct($information['product'] ?? '');
        $account->setResourceId($information['resourceId'] ?? '');
        $account->setStatus($information['status'] ?? '');
        $account->setUsage($information['usage'] ?? '');

        // set owner info (could be array or string)
        $ownerAddress = [];
        if (array_key_exists('ownerAddressUnstructured', $information) && is_array($information['ownerAddressUnstructured'])) {
            $ownerAddress = $information['ownerAddressUnstructured'];
        }
        if (array_key_exists('ownerAddressUnstructured', $information) && is_string($information['ownerAddressUnstructured'])) {
            $ownerAddress = ['ownerAddressUnstructured' => $information['ownerAddressUnstructured']];
        }
        $account->setOwnerAddressUnstructured($ownerAddress);


        return $account;
    }

    /**
     * @param Account $account
     *
     * @return Account
     * @throws ImporterErrorException
     * @throws ImporterHttpException
     */
    private static function getBalanceDetails(Account $account): Account
    {
        app('log')->debug(sprintf('Now in %s(%s)', __METHOD__, $account->getIdentifier()));

        $url         = config('nordigen.url');
        $accessToken = TokenManager::getAccessToken();
        $request     = new GetAccountBalanceRequest($url, $accessToken, $account->getIdentifier());
        $request->setTimeOut(config('importer.connection.timeout'));
        /** @var ArrayResponse $response */
        $response = $request->get();
        if (array_key_exists('balances', $response->data)) {
            foreach ($response->data['balances'] as $array) {
                app('log')->debug(sprintf('Added "%s" balance "%s"', $array['balanceType'], $array['balanceAmount']['amount']));
                $account->addBalance(Balance::createFromArray($array));
            }
        }

        return $account;
    }

    /**
     * @param Account $account
     */
    private static function getBasicDetails(Account $account): Account
    {
        app('log')->debug(sprintf('Now in %s(%s)', __METHOD__, $account->getIdentifier()));

        $url         = config('nordigen.url');
        $accessToken = TokenManager::getAccessToken();
        $request     = new GetAccountBasicRequest($url, $accessToken, $account->getIdentifier());
        $request->setTimeOut(config('importer.connection.timeout'));
        /** @var ArrayResponse $response */
        $response = $request->get();
        $array    = $response->data;
        app('log')->debug('Response for basic information request:', $array);

        // save IBAN if not already present
        if (array_key_exists('iban', $array) && '' !== $array['iban'] && '' === $account->getIban()) {
            app('log')->debug('Set new IBAN from basic details.');
            $account->setIban($array['iban']);
        }

        return $account;
    }
}
