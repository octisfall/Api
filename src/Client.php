<?php

namespace Octisfall\TelegramBot\Api;

use Closure;
use ReflectionFunction;
use Octisfall\TelegramBot\Api\Events\EventCollection;
use Octisfall\TelegramBot\Api\Types\Update;
use Octisfall\TelegramBot\Api\Types\Message;
use Octisfall\TelegramBot\Api\Types\Inline\InlineKeyboardMarkup;
use Octisfall\TelegramBot\Api\Types\ReplyKeyboardRemove;
use Octisfall\TelegramBot\Api\Types\ForceReply;
use Octisfall\TelegramBot\Api\Types\ReplyKeyboardHide;
use Octisfall\TelegramBot\Api\Types\ReplyKeyboardMarkup;

/**
 * Class Client
 *
 * @package Octisfall\TelegramBot\Api
 * @method Message editMessageText(string $chatId, int $messageId, string $text, string $parseMode = null, bool $disablePreview = false, ReplyKeyboardMarkup|ReplyKeyboardHide|ForceReply|ReplyKeyboardRemove|InlineKeyboardMarkup|null $replyMarkup = null, string $inlineMessageId = null)
 */
class Client
{
    /**
     * RegExp for bot commands
     */
    const REGEXP = '/^(?:@\w+\s)?\/([^\s@]+)(@\S+)?\s?(.*)$/';

    /**
     * @var \Octisfall\TelegramBot\Api\BotApi
     */
    protected $api;

    /**
     * @var \Octisfall\TelegramBot\Api\Events\EventCollection
     */
    protected $events;

    /**
     * Client constructor
     *
     * @param string $token Telegram Bot API token
     * @param string|null $trackerToken Yandex AppMetrica application api_key
     */
    public function __construct($token, $trackerToken = null)
    {
        $this->api = new BotApi($token);
        $this->events = new EventCollection($trackerToken);
    }

    /**
     * Use this method to add command. Parameters will be automatically parsed and passed to closure.
     *
     * @param string $name
     * @param \Closure $action
     *
     * @return \Octisfall\TelegramBot\Api\Client
     */
    public function command($name, Closure $action)
    {
        return $this->on(self::getEvent($action), self::getChecker($name));
    }

    public function editedMessage(Closure $action)
    {
        return $this->on(self::getEditedMessageEvent($action), self::getEditedMessageChecker());
    }

    public function callbackQuery(Closure $action)
    {
        return $this->on(self::getCallbackQueryEvent($action), self::getCallbackQueryChecker());
    }

    public function channelPost(Closure $action)
    {
        return $this->on(self::getChannelPostEvent($action), self::getChannelPostChecker());
    }

    public function editedChannelPost(Closure $action)
    {
        return $this->on(self::getEditedChannelPostEvent($action), self::getEditedChannelPostChecker());
    }

    public function inlineQuery(Closure $action)
    {
        return $this->on(self::getInlineQueryEvent($action), self::getInlineQueryChecker());
    }

    public function chosenInlineResult(Closure $action)
    {
        return $this->on(self::getChosenInlineResultEvent($action), self::getChosenInlineResultChecker());
    }

    public function shippingQuery(Closure $action)
    {
        return $this->on(self::getShippingQueryEvent($action), self::getShippingQueryChecker());
    }

    public function preCheckoutQuery(Closure $action)
    {
        return $this->on(self::getPreCheckoutQueryEvent($action), self::getPreCheckoutQueryChecker());
    }

    public function poll(Closure $action)
    {
        return $this->on(self::getPollEvent($action), self::getPollChecker());
    }

    public function pollAnswer(Closure $action)
    {
        return $this->on(self::getPollAnswerEvent($action), self::getPollAnswerChecker());
    }

    public function myChatMember(Closure $action)
    {
        return $this->on(self::getMyChatMemberEvent($action), self::getMyChatMemberChecker());
    }

    public function chatMember(Closure $action)
    {
        return $this->on(self::getChatMemberEvent($action), self::getChatMemberChecker());
    }

    public function chatJoinRequest(Closure $action)
    {
        return $this->on(self::getChatJoinRequestEvent($action), self::getChatJoinRequestChecker());
    }

    /**
     * Use this method to add an event.
     * If second closure will return true (or if you are passed null instead of closure), first one will be executed.
     *
     * @param \Closure $event
     * @param \Closure|null $checker
     *
     * @return \Octisfall\TelegramBot\Api\Client
     */
    public function on(Closure $event, Closure $checker = null)
    {
        $this->events->add($event, $checker);

        return $this;
    }

    /**
     * Handle updates
     *
     * @param Update[] $updates
     */
    public function handle(array $updates)
    {
        foreach ($updates as $update) {
            /* @var \Octisfall\TelegramBot\Api\Types\Update $update */
            $this->events->handle($update);
        }
    }

    /**
     * Webhook handler
     *
     * @return array
     * @throws \Octisfall\TelegramBot\Api\InvalidJsonException
     */
    public function run()
    {
        if ($data = BotApi::jsonValidate($this->getRawBody(), true)) {
            $this->handle([Update::fromResponse($data)]);
        }
    }

    public function getRawBody()
    {
        return file_get_contents('php://input');
    }

    /**
     * Returns event function to handling the command.
     *
     * @param \Closure $action
     *
     * @return \Closure
     */
    protected static function getEvent(Closure $action)
    {
        return function (Update $update) use ($action) {
            $message = $update->getMessage();
            if (!$message) {
                return true;
            }

            preg_match(self::REGEXP, $message->getText(), $matches);

            if (isset($matches[3]) && !empty($matches[3])) {
                $parameters = str_getcsv($matches[3], chr(32));
            } else {
                $parameters = [];
            }

            array_unshift($parameters, $message);

            $action = new ReflectionFunction($action);

            if (count($parameters) >= $action->getNumberOfRequiredParameters()) {
                $action->invokeArgs($parameters);
            }

            return false;
        };
    }

    protected static function getEditedMessageEvent(Closure $action)
    {
        return function (Update $update) use ($action) {
            if (!$update->getEditedMessage()) {
                return true;
            }

            $reflectionAction = new ReflectionFunction($action);
            $reflectionAction->invokeArgs([$update->getEditedMessage()]);
            return false;
        };
    }

    protected static function getChannelPostEvent(Closure $action)
    {
        return function (Update $update) use ($action) {
            if (!$update->getChannelPost()) {
                return true;
            }

            $reflectionAction = new ReflectionFunction($action);
            $reflectionAction->invokeArgs([$update->getChannelPost()]);
            return false;
        };
    }

    protected static function getCallbackQueryEvent(Closure $action)
    {
        return function (Update $update) use ($action) {
            if (!$update->getCallbackQuery()) {
                return true;
            }

            $reflectionAction = new ReflectionFunction($action);
            $reflectionAction->invokeArgs([$update->getCallbackQuery()]);
            return false;
        };
    }

    protected static function getEditedChannelPostEvent(Closure $action)
    {
        return function (Update $update) use ($action) {
            if (!$update->getEditedChannelPost()) {
                return true;
            }

            $reflectionAction = new ReflectionFunction($action);
            $reflectionAction->invokeArgs([$update->getEditedChannelPost()]);
            return false;
        };
    }

    protected static function getInlineQueryEvent(Closure $action)
    {
        return function (Update $update) use ($action) {
            if (!$update->getInlineQuery()) {
                return true;
            }

            $reflectionAction = new ReflectionFunction($action);
            $reflectionAction->invokeArgs([$update->getInlineQuery()]);
            return false;
        };
    }

    protected static function getChosenInlineResultEvent(Closure $action)
    {
        return function (Update $update) use ($action) {
            if (!$update->getChosenInlineResult()) {
                return true;
            }

            $reflectionAction = new ReflectionFunction($action);
            $reflectionAction->invokeArgs([$update->getChosenInlineResult()]);
            return false;
        };
    }

    protected static function getShippingQueryEvent(Closure $action)
    {
        return function (Update $update) use ($action) {
            if (!$update->getShippingQuery()) {
                return true;
            }

            $reflectionAction = new ReflectionFunction($action);
            $reflectionAction->invokeArgs([$update->getShippingQuery()]);
            return false;
        };
    }

    protected static function getPreCheckoutQueryEvent(Closure $action)
    {
        return function (Update $update) use ($action) {
            if (!$update->getPreCheckoutQuery()) {
                return true;
            }

            $reflectionAction = new ReflectionFunction($action);
            $reflectionAction->invokeArgs([$update->getPreCheckoutQuery()]);
            return false;
        };
    }

    protected static function getPollEvent(Closure $action)
    {
        return function (Update $update) use ($action) {
            if (!$update->getPoll()) {
                return true;
            }

            $reflectionAction = new ReflectionFunction($action);
            $reflectionAction->invokeArgs([$update->getPoll()]);
            return false;
        };
    }

    protected static function getPollAnswerEvent(Closure $action)
    {
        return function (Update $update) use ($action) {
            if (!$update->getPollAnswer()) {
                return true;
            }

            $reflectionAction = new ReflectionFunction($action);
            $reflectionAction->invokeArgs([$update->getPollAnswer()]);
            return false;
        };
    }

    protected static function getMyChatMemberEvent(Closure $action)
    {
        return function (Update $update) use ($action) {
            if (!$update->getChatMemberUpdated()) {
                return true;
            }

            $reflectionAction = new ReflectionFunction($action);
            $reflectionAction->invokeArgs([$update->getChatMemberUpdated()]);
            return false;
        };
    }

    protected static function getChatMemberEvent(Closure $action)
    {
        return function (Update $update) use ($action) {
            if (!$update->getChatMemberUpdated()) {
                return true;
            }

            $reflectionAction = new ReflectionFunction($action);
            $reflectionAction->invokeArgs([$update->getChatMemberUpdated()]);
            return false;
        };
    }

    protected static function getChatJoinRequestEvent(Closure $action)
    {
        return function (Update $update) use ($action) {
            if (!$update->getChatJoinRequest()) {
                return true;
            }

            $reflectionAction = new ReflectionFunction($action);
            $reflectionAction->invokeArgs([$update->getChatJoinRequest()]);
            return false;
        };
    }

    /**
     * Returns check function to handling the command.
     *
     * @param string $name
     *
     * @return \Closure
     */
    protected static function getChecker($name)
    {
        return function (Update $update) use ($name) {
            $message = $update->getMessage();
            if (is_null($message) || !strlen($message->getText())) {
                return false;
            }

            preg_match(self::REGEXP, $message->getText(), $matches);

            return !empty($matches) && $matches[1] == $name;
        };
    }

    /**
     * Returns check function to handling the edited message.
     *
     * @return Closure
     */
    protected static function getEditedMessageChecker()
    {
        return function (Update $update) {
            return !is_null($update->getEditedMessage());
        };
    }

    /**
     * Returns check function to handling the channel post.
     *
     * @return Closure
     */
    protected static function getChannelPostChecker()
    {
        return function (Update $update) {
            return !is_null($update->getChannelPost());
        };
    }

    /**
     * Returns check function to handling the callbackQuery.
     *
     * @return Closure
     */
    protected static function getCallbackQueryChecker()
    {
        return function (Update $update) {
            return !is_null($update->getCallbackQuery());
        };
    }

    /**
     * Returns check function to handling the edited channel post.
     *
     * @return Closure
     */
    protected static function getEditedChannelPostChecker()
    {
        return function (Update $update) {
            return !is_null($update->getEditedChannelPost());
        };
    }

    /**
     * Returns check function to handling the chosen inline result.
     *
     * @return Closure
     */
    protected static function getChosenInlineResultChecker()
    {
        return function (Update $update) {
            return !is_null($update->getChosenInlineResult());
        };
    }

    /**
     * Returns check function to handling the inline queries.
     *
     * @return Closure
     */
    protected static function getInlineQueryChecker()
    {
        return function (Update $update) {
            return !is_null($update->getInlineQuery());
        };
    }

    /**
     * Returns check function to handling the shipping queries.
     *
     * @return Closure
     */
    protected static function getShippingQueryChecker()
    {
        return function (Update $update) {
            return !is_null($update->getShippingQuery());
        };
    }

    /**
     * Returns check function to handling the pre checkout queries.
     *
     * @return Closure
     */
    protected static function getPreCheckoutQueryChecker()
    {
        return function (Update $update) {
            return !is_null($update->getPreCheckoutQuery());
        };
    }

    /**
     * Returns check function to handling the poll.
     *
     * @return Closure
     */
    protected static function getPollChecker()
    {
        return function (Update $update) {
            return !is_null($update->getPoll());
        };
    }

    /**
     * Returns check function to handling the poll answers.
     *
     * @return Closure
     */
    protected static function getPollAnswerChecker()
    {
        return function (Update $update) {
            return !is_null($update->getPollAnswer());
        };
    }

    /**
     * Returns check function to handling the updated chat member.
     * The bot's chat member status was updated in a chat.
     * For private chats, this update is received only when the bot is blocked or unblocked by the user.
     * @return Closure
     */
    protected static function getMyChatMemberChecker()
    {
        return function (Update $update) {
            return !is_null($update->getChatMemberUpdated());
        };
    }

    /**
     * Returns check function to handling the updated chat member.
     * The bot's chat member status was updated in a chat.
     * For private chats, this update is received only when the bot is blocked or unblocked by the user.
     * @return Closure
     */
    protected static function getChatMemberChecker()
    {
        return function (Update $update) {
            return !is_null($update->getChatMemberUpdated());
        };
    }

    /**
     * Returns check function to handling the chat join requests.
     * The bot's chat member status was updated in a chat.
     * For private chats, this update is received only when the bot is blocked or unblocked by the user.
     * @return Closure
     */
    protected static function getChatJoinRequestChecker()
    {
        return function (Update $update) {
            return !is_null($update->getChatJoinRequest());
        };
    }

    public function __call($name, array $arguments)
    {
        if (method_exists($this, $name)) {
            return call_user_func_array([$this, $name], $arguments);
        } elseif (method_exists($this->api, $name)) {
            return call_user_func_array([$this->api, $name], $arguments);
        }
        throw new BadMethodCallException("Method {$name} not exists");
    }
}
