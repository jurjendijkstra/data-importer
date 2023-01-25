<?php

/*
 * RoutineManager.php
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

namespace App\Services\Shared\Import\Routine;

use App\Exceptions\ImporterErrorException;
use App\Services\Shared\Configuration\Configuration;

/**
 * Class RoutineManager
 */
class RoutineManager
{
    private array         $allErrors;
    private array         $allMessages;
    private array         $allWarnings;
    private ApiSubmitter  $apiSubmitter;
    private string        $identifier;
    private InfoCollector $infoCollector;
    private array         $transactions;

    /**
     * @param string $identifier
     */
    public function __construct(string $identifier)
    {
        $this->identifier   = $identifier;
        $this->transactions = [];
        $this->allMessages  = [];
        $this->allWarnings  = [];
        $this->allErrors    = [];
    }

    /**
     * @return array
     */
    public function getAllErrors(): array
    {
        return $this->allErrors;
    }

    /**
     * @return array
     */
    public function getAllMessages(): array
    {
        return $this->allMessages;
    }

    /**
     * @return array
     */
    public function getAllWarnings(): array
    {
        return $this->allWarnings;
    }

    /**
     * @param Configuration $configuration
     */
    public function setConfiguration(Configuration $configuration): void
    {
        $this->infoCollector = new InfoCollector();
        $this->apiSubmitter  = new ApiSubmitter();
        $this->apiSubmitter->setIdentifier($this->identifier);
        $this->apiSubmitter->setConfiguration($configuration);

        app('log')->debug('Created APISubmitter in RoutineManager');
    }

    /**
     * @param array $transactions
     */
    public function setTransactions(array $transactions): void
    {
        $this->transactions = $transactions;
        app('log')->debug(sprintf('Now have %d transaction(s) in RoutineManager', count($transactions)));
    }

    /**
     *
     * @throws ImporterErrorException
     */
    public function start(): void
    {
        app('log')->debug('Start of shared import routine.');

        app('log')->debug('First collect account information from Firefly III.');
        $accountInfo = $this->infoCollector->collectAccountTypes();

        app('log')->debug('Now starting submission by calling API Submitter');
        // submit transactions to API:
        $this->apiSubmitter->setAccountInfo($accountInfo);
        $this->apiSubmitter->processTransactions($this->transactions);
        $this->allMessages = $this->apiSubmitter->getMessages();
        $this->allWarnings = $this->apiSubmitter->getWarnings();
        $this->allErrors   = $this->apiSubmitter->getErrors();
    }
}
