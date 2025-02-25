<?php

namespace Octisfall\TelegramBot\Api\Types\Payments\Query;

use Octisfall\TelegramBot\Api\BaseType;
use Octisfall\TelegramBot\Api\Types\Payments\ArrayOfLabeledPrice;

/**
 * Class AnswerShippingQuery
 * If you sent an invoice requesting a shipping address and the parameter is_flexible was specified,
 * the Bot API will send an Update with a shipping_query field to the bot
 *
 * @package Octisfall\TelegramBot\Api\Types\Payments\Query
 */
class AnswerShippingQuery extends BaseType
{
    /**
     * @var array
     */
    static protected $requiredParams = ['shipping_query_id', 'ok'];

    /**
     * @var array
     */
    static protected $map = [
        'shipping_query_id' => true,
        'ok' => true,
        'shipping_options' => ArrayOfLabeledPrice::class,
        'error_message' => true,
    ];

    /**
     * Unique identifier for the query to be answered
     *
     * @var string
     */
    protected $shippingQueryId;

    /**
     * Specify True if delivery to the specified address is possible and False if there are any problems
     *
     * @var true
     */
    protected $ok;

    /**
     * Required if ok is True. A JSON-serialized array of available shipping options.
     *
     * @var array
     */
    protected $shippingOptions;

    /**
     * Required if ok is False. Error message in human readable form that explains why it is impossible to complete
     * the order
     *
     * @var string
     */
    protected $errorMessage;

    /**
     * @author MY
     * @return string
     */
    public function getShippingQueryId()
    {
        return $this->shippingQueryId;
    }

    /**
     * @author MY
     * @param string $shippingQueryId
     */
    public function setShippingQueryId($shippingQueryId)
    {
        $this->shippingQueryId = $shippingQueryId;
    }

    /**
     * @author MY
     * @return bool
     */
    public function getOk()
    {
        return $this->ok;
    }

    /**
     * @author MY
     * @param true $ok
     */
    public function setOk($ok)
    {
        $this->ok = $ok;
    }

    /**
     * @author MY
     * @return array
     */
    public function getShippingOptions()
    {
        return $this->shippingOptions;
    }

    /**
     * @author MY
     * @param array $shippingOptions
     */
    public function setShippingOptions($shippingOptions)
    {
        $this->shippingOptions = $shippingOptions;
    }

    /**
     * @author MY
     * @return string
     */
    public function getErrorMessage()
    {
        return $this->errorMessage;
    }

    /**
     * @author MY
     * @param string $errorMessage
     */
    public function setErrorMessage($errorMessage)
    {
        $this->errorMessage = $errorMessage;
    }
}
