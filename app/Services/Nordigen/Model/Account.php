<?php
/*
 * Account.php
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

namespace App\Services\Nordigen\Model;

/**
 * Class Account
 */
class Account
{
    private array  $balances = [];
    private string $bban;
    private string $bic;
    private string $cashAccountType;
    private string $currency;
    private string $details;
    private string $displayName;
    private string $iban;
    private string $identifier;
    private string $linkedAccounts;
    private string $msisdn;
    private string $name;
    private array  $ownerAddressUnstructured;
    private string $ownerName;
    private string $product;
    private string $resourceId;
    private string $status;
    private string $usage;

    /**
     * Make sure all fields have an (empty) value
     */
    public function __construct()
    {
        $this->identifier               = '';
        $this->bban                     = '';
        $this->bic                      = '';
        $this->cashAccountType          = '';
        $this->currency                 = '';
        $this->details                  = '';
        $this->displayName              = '';
        $this->iban                     = '';
        $this->linkedAccounts           = '';
        $this->msisdn                   = '';
        $this->name                     = '';
        $this->ownerAddressUnstructured = [];
        $this->ownerName                = '';
        $this->product                  = '';
        $this->resourceId               = '';
        $this->status                   = '';
        $this->usage                    = '';
        $this->balances                 = [];
    }


    public static function createFromIdentifier(string $identifier): self
    {
        $self = new self();
        $self->setIdentifier($identifier);

        return $self;
    }

    /**
     * @param array $array
     *
     * @return static
     */
    public static function fromLocalArray(array $array): self
    {
        $object                           = new self();
        $object->identifier               = $array['identifier'];
        $object->bban                     = $array['bban'];
        $object->bic                      = $array['bic'];
        $object->cashAccountType          = $array['cash_account_type'];
        $object->currency                 = $array['currency'];
        $object->details                  = $array['details'];
        $object->displayName              = $array['display_name'];
        $object->iban                     = $array['iban'];
        $object->linkedAccounts           = $array['linked_accounts'];
        $object->msisdn                   = $array['msisdn'];
        $object->name                     = $array['name'];
        $object->ownerAddressUnstructured = $array['owner_address_unstructured'];
        $object->ownerName                = $array['owner_name'];
        $object->product                  = $array['product'];
        $object->resourceId               = $array['resource_id'];
        $object->status                   = $array['status'];
        $object->usage                    = $array['usage'];
        $object->balances                 = [];
        foreach ($array['balances'] as $arr) {
            $object->balances[] = Balance::fromLocalArray($arr);
        }

        return $object;
    }

    /**
     * @param Balance $balance
     */
    public function addBalance(Balance $balance): void
    {
        $this->balances[] = $balance;
    }

    /**
     * @return string
     */
    public function getBban(): string
    {
        return $this->bban;
    }

    /**
     * @param string $bban
     */
    public function setBban(string $bban): void
    {
        $this->bban = $bban;
    }

    /**
     * @return string
     */
    public function getBic(): string
    {
        return $this->bic;
    }

    /**
     * @param string $bic
     */
    public function setBic(string $bic): void
    {
        $this->bic = $bic;
    }

    /**
     * @return string
     */
    public function getCashAccountType(): string
    {
        return $this->cashAccountType;
    }

    /**
     * @param string $cashAccountType
     */
    public function setCashAccountType(string $cashAccountType): void
    {
        $this->cashAccountType = $cashAccountType;
    }

    /**
     * @return string
     */
    public function getCurrency(): string
    {
        return $this->currency;
    }

    /**
     * @param string $currency
     */
    public function setCurrency(string $currency): void
    {
        $this->currency = $currency;
    }

    /**
     * @return string
     */
    public function getDetails(): string
    {
        return $this->details;
    }

    /**
     * @param string $details
     */
    public function setDetails(string $details): void
    {
        $this->details = $details;
    }

    /**
     * @return string
     */
    public function getFullName(): string
    {
        app('log')->debug('Account::getFullName()');
        if ('' !== $this->getName()) {
            app('log')->debug(sprintf('Return getName(): "%s"', $this->getName()));


            return $this->getName();
        }
        if ('' !== $this->getDisplayName()) {
            app('log')->debug(sprintf('Return getDisplayName(): "%s"', $this->getDisplayName()));

            return $this->getDisplayName();
        }
        if ('' !== $this->getOwnerName()) {
            app('log')->debug(sprintf('Return getOwnerName(): "%s"', $this->getOwnerName()));

            return $this->getOwnerName();
        }
        if ('' !== $this->getIban()) {
            app('log')->debug(sprintf('Return getIban(): "%s"', $this->getIban()));

            return $this->getIban();
        }
        app('log')->warning('Account::getFullName(): no field with name, return "(no name)"');

        return '(no name)';
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getDisplayName(): string
    {
        return $this->displayName;
    }

    /**
     * @param string $displayName
     */
    public function setDisplayName(string $displayName): void
    {
        $this->displayName = $displayName;
    }

    /**
     * @return string
     */
    public function getOwnerName(): string
    {
        return $this->ownerName;
    }

    /**
     * @param string $ownerName
     */
    public function setOwnerName(string $ownerName): void
    {
        $this->ownerName = $ownerName;
    }

    /**
     * @return string
     */
    public function getIban(): string
    {
        return $this->iban;
    }

    /**
     * @param string $iban
     */
    public function setIban(string $iban): void
    {
        $this->iban = $iban;
    }

    /**
     * @return string
     */
    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    /**
     * @param string $identifier
     */
    public function setIdentifier(string $identifier): void
    {
        $this->identifier = $identifier;
    }

    /**
     * @return string
     */
    public function getLinkedAccounts(): string
    {
        return $this->linkedAccounts;
    }

    /**
     * @param string $linkedAccounts
     */
    public function setLinkedAccounts(string $linkedAccounts): void
    {
        $this->linkedAccounts = $linkedAccounts;
    }

    /**
     * @return string
     */
    public function getMsisdn(): string
    {
        return $this->msisdn;
    }

    /**
     * @param string $msisdn
     */
    public function setMsisdn(string $msisdn): void
    {
        $this->msisdn = $msisdn;
    }

    /**
     * @return array
     */
    public function getOwnerAddressUnstructured(): array
    {
        return $this->ownerAddressUnstructured;
    }

    /**
     * @param array $ownerAddressUnstructured
     */
    public function setOwnerAddressUnstructured(array $ownerAddressUnstructured): void
    {
        $this->ownerAddressUnstructured = $ownerAddressUnstructured;
    }

    /**
     * @return string
     */
    public function getProduct(): string
    {
        return $this->product;
    }

    /**
     * @param string $product
     */
    public function setProduct(string $product): void
    {
        $this->product = $product;
    }

    /**
     * @return string
     */
    public function getResourceId(): string
    {
        return $this->resourceId;
    }

    /**
     * @param string $resourceId
     */
    public function setResourceId(string $resourceId): void
    {
        $this->resourceId = $resourceId;
    }

    /**
     * @return string
     */
    public function getStatus(): string
    {
        return $this->status;
    }

    /**
     * @param string $status
     */
    public function setStatus(string $status): void
    {
        $this->status = $status;
    }

    /**
     * @return string
     */
    public function getUsage(): string
    {
        return $this->usage;
    }

    /**
     * @param string $usage
     */
    public function setUsage(string $usage): void
    {
        $this->usage = $usage;
    }

    /**
     * @return array
     */
    public function toLocalArray(): array
    {
        $array = [
            'identifier'                 => $this->identifier,
            'bban'                       => $this->bban,
            'bic'                        => $this->bic,
            'cash_account_type'          => $this->cashAccountType,
            'currency'                   => $this->currency,
            'details'                    => $this->details,
            'display_name'               => $this->displayName,
            'iban'                       => $this->iban,
            'linked_accounts'            => $this->linkedAccounts,
            'msisdn'                     => $this->msisdn,
            'name'                       => $this->name,
            'owner_address_unstructured' => $this->ownerAddressUnstructured,
            'owner_name'                 => $this->ownerName,
            'product'                    => $this->product,
            'resource_id'                => $this->resourceId,
            'status'                     => $this->status,
            'usage'                      => $this->usage,
            'balances'                   => [],
        ];
        /** @var Balance $balance */
        foreach ($this->balances as $balance) {
            $array['balances'][] = $balance->toLocalArray();
        }


        return $array;
    }
}
