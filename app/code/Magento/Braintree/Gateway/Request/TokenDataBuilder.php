<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Braintree\Gateway\Request;

use Magento\Payment\Gateway\Command\CommandException;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Vault\Api\Data\PaymentTokenInterface;

class TokenDataBuilder implements BuilderInterface
{
    /**
     * @inheritdoc
     * @throws CommandException
     */
    public function build(array $buildSubject)
    {
        if (empty($buildSubject['storeId'])) {
            throw new CommandException(__('Store should be specified.'));
        }

        /** @var PaymentTokenInterface $paymentToken */
        $paymentToken = $buildSubject['paymentToken'];
        if ($paymentToken === null) {
            throw new CommandException(__('Payment Token should not be empty.'));
        }

        return [
            'token' => $paymentToken->getGatewayToken(),
            'store_id' => $buildSubject['storeId']
        ];
    }
}
