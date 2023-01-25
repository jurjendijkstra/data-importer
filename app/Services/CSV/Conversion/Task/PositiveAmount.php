<?php
/*
 * PositiveAmount.php
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

use App\Services\CSV\Converter\Amount as AmountConverter;

/**
 * Class PositiveAmount
 */
class PositiveAmount extends AbstractTask
{
    /**
     * Make sure amount is always positive when submitting.
     *
     * @inheritDoc
     */
    public function process(array $group): array
    {
        foreach ($group['transactions'] as $index => $transaction) {
            $group['transactions'][$index]['amount'] = $group['transactions'][$index]['amount'] ?? '0';
            $group['transactions'][$index]['amount'] = AmountConverter::positive($group['transactions'][$index]['amount']);
        }

        return $group;
    }

    /**
     * @inheritDoc
     */
    public function requiresDefaultAccount(): bool
    {
        return false;
    }

    /**
     * @inheritDoc
     */
    public function requiresTransactionCurrency(): bool
    {
        return false;
    }
}
