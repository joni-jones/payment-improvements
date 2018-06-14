<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Vault\Api;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NotFoundException;
use Magento\Payment\Gateway\Command\CommandException;

/**
 * @api
 */
interface TokenDeleterInterface
{
    /**
     * Deletes Payment Token on the Payment Gateway side.
     *
     * @param string $publicHash
     * @return void
     * @throws NotFoundException
     * @throws CommandException
     * @throws LocalizedException
     */
    public function execute(string $publicHash): void;
}
