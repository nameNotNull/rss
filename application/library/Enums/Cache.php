<?php

namespace Enums;

class Cache
{
    const RSS_KEY_TEMPLATE = 'RSS:%s_%s';
    const RSS_EXPIRE_TIME  = 10 * 60;

    const RSS_DETAIL_KEY_TEMPLATE = 'RSS_DETAIL:%s_%s_%s';
    const RSS_DETAIL_EXPIRE_TIME  = 10 * 60;
}
