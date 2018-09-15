<?php
namespace MobileApi\Util\Push;

use Yaf\Registry;

/**
 * Client.php
 *
 * @author: Anson
 * @date  : 2016-09-21 13:00
 */
class Message
{
    const PRIO_TYPE_NORMAL = 'normal';
    const PRIO_TYPE_LOW    = 'low';

    private $proto = '(appid)';

    private $passcode = '(appkey)';

    private $op = 'push';

    private $targets = [];

    public $rpcid = 0;

    public $timeout;

    public $dont_route = false; //push内部字段

    public $badge = 1;

    public $sound = 'default';

    public $title = '';

    public $description = '';

    public $payload = '';

    public $actionurl = '';

    public $ontime = 0;

    public $prio = Message::PRIO_TYPE_NORMAL;

    public function __construct($proto, $passcode)
    {
        $this->proto    = $proto;
        $this->passcode = $passcode;
    }

    public function addTarget($ucid, $proto = '')
    {
        $ucid = (string)$ucid;
        if (!$ucid) {
            return $this;
        }

        $this->targets[] = [
            'ucid'  => (string)$ucid,
            'uuid'  => '*',
            'proto' => (string)($proto ?: $this->proto),
        ];

        return $this;
    }

    public function addDeviceTarget($uuid, $proto = '')
    {
        $this->targets[] = [
            'ucid'  => '_anon',
            'uuid'  => $uuid,
            'proto' => (string)($proto ?: $this->proto),
        ];

        return $this;
    }

    public function toArray()
    {
        return [
            "op"          => $this->op,
            "rpcid"       => $this->rpcid,
            "timeout"     => $this->timeout,
            "proto"       => $this->proto,
            "passcode"    => $this->passcode,
            "dont_route"  => $this->dont_route,
            "targets"     => $this->targets,
            "badge"       => $this->badge,
            "sound"       => $this->sound,
            "title"       => $this->title,
            "description" => $this->description,
            "actionurl"   => $this->actionurl,
            "payload"     => $this->payload,
            "ontime"      => (int)$this->ontime,
            "prio"        => $this->prio,
        ];
    }

    public function __toString()
    {
        return (string)json_encode($this->toArray(), JSON_UNESCAPED_UNICODE);
    }
}