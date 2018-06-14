<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

use Magento\Braintree\Model\Ui\ConfigProvider;
use Magento\Config\Model\Config;
use Magento\Vault\Api\Data\PaymentTokenFactoryInterface;
use Magento\Vault\Model\PaymentToken;
use Magento\Vault\Model\PaymentTokenRepository;
use Magento\TestFramework\Helper\Bootstrap;

$objectManager = Bootstrap::getObjectManager();

/** @var Config $config */
$config = $objectManager->get(Config::class);
$config->setDataByPath('payment/' . ConfigProvider::CODE . '/active', 1);
$config->save();
$config->setDataByPath('payment/' . ConfigProvider::CC_VAULT_CODE . '/active', 1);
$config->save();

$hash = '4ddf9ab5a15c76e23eb53856';
/** @var PaymentToken $paymentToken */
$paymentToken = $objectManager->create(PaymentToken::class);
$paymentToken->setPaymentMethodCode(ConfigProvider::CODE)
    ->setType(PaymentTokenFactoryInterface::TOKEN_TYPE_CREDIT_CARD)
    ->setGatewayToken('mx29vk')
    ->setPublicHash($hash)
    ->setTokenDetails(json_encode(['type' => 'VI']))
    ->setIsActive(true)
    ->setIsVisible(true)
    ->setExpiresAt(date('Y-m-d H:i:s', strtotime('+1 year')));

/** @var PaymentTokenRepository $tokenRepository */
$tokenRepository = $objectManager->create(PaymentTokenRepository::class);
$tokenRepository->save($paymentToken);
