<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\TestFramework\Annotation;

use Magento\Framework\Exception\LocalizedException;
use Magento\TestFramework\Event\Param\Transaction;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * Implementation of the @magentoDbIsolation DocBlock annotation
 */
class DbIsolation
{
    public const MAGENTO_DB_ISOLATION = 'magentoDbIsolation';

    /**
     * @var bool
     */
    protected $_isIsolationActive = false;

    /**
     * Handler for 'startTestTransactionRequest' event
     *
     * @param TestCase $test
     * @param Transaction $param
     */
    public function startTestTransactionRequest(TestCase $test, Transaction $param)
    {
        $methodIsolation = $this->_getIsolation($test);
        if ($this->_isIsolationActive) {
            if ($methodIsolation === false) {
                $param->requestTransactionRollback();
            }
        } elseif ($methodIsolation || ($methodIsolation === null && $this->_getIsolation($test))) {
            $param->requestTransactionStart();
        }
    }

    /**
     * Handler for 'endTestTransactionRequest' event
     *
     * @param TestCase $test
     * @param Transaction $param
     */
    public function endTestTransactionRequest(TestCase $test, Transaction $param)
    {
        if ($this->_isIsolationActive && $this->_getIsolation($test)) {
            $param->requestTransactionRollback();
        }
    }

    /**
     * Handler for 'startTransaction' event
     *
     * @param TestCase $test
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function startTransaction(TestCase $test)
    {
        $this->_isIsolationActive = true;
    }

    /**
     * Handler for 'rollbackTransaction' event
     */
    public function rollbackTransaction()
    {
        $this->_isIsolationActive = false;
    }

    /**
     * Retrieve database isolation annotation value for the current scope.
     * Possible results:
     *   NULL  - annotation is not defined
     *   TRUE  - annotation is defined as 'enabled'
     *   FALSE - annotation is defined as 'disabled'
     *
     * @param TestCase $test
     * @return bool|null Returns NULL, if isolation is not defined for the current scope
     * @throws LocalizedException
     */
    protected function _getIsolation(TestCase $test)
    {
        $isolation = Bootstrap::getObjectManager()->get(DbIsolationState::class)->getState($test);
        if ($isolation) {
            if ($isolation !== ['enabled'] && $isolation !== ['disabled']) {
                throw new LocalizedException(
                    __('Invalid "@magentoDbIsolation" annotation, can be "enabled" or "disabled" only.')
                );
            }
            return $isolation === ['enabled'];
        }
        return null;
    }
}
