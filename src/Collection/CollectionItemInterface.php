<?php

namespace Octisfall\TelegramBot\Api\Collection;

interface CollectionItemInterface
{
    public function toJson($inner = false);
}
