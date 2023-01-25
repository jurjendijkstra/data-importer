<?php
/*
 * Currency.php
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

namespace App\Services\CSV\Conversion\Task;

/**
 * Class Currency
 */
class Currency extends AbstractTask
{
    /**
     * @param array $group
     *
     * @return array
     */
    public function process(array $group): array
    {
        foreach ($group['transactions'] as $index => $transaction) {
            $group['transactions'][$index] = $this->processCurrency($transaction);
        }

        return $group;
    }

    /**
     * Returns true if the task requires the default account.
     *
     * @return bool
     */
    public function requiresDefaultAccount(): bool
    {
        return false;
    }

    /**
     * Returns true if the task requires the default currency of the user.
     *
     * @return bool
     */
    public function requiresTransactionCurrency(): bool
    {
        return true;
    }

    /**
     * @param array $transaction
     *
     * @return array
     */
    private function processCurrency(array $transaction): array
    {
        if (
            (0 === $transaction['currency_id'] || null === $transaction['currency_id'])
            && (null === $transaction['currency_code'] || '' === $transaction['currency_code'])) {
            $transaction['currency_id']   = $this->transactionCurrency->id;
            $transaction['currency_code'] = null;
            app('log')->debug(sprintf('Set currency to %d because it was NULL or empty.', $this->transactionCurrency->id));
        }

        return $transaction;
    }
}
