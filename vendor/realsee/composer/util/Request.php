<?php

namespace MobileApi\Util;

use Enums\Cache as CacheEnums;
use Enums\Client as ClientEnums;
use Enums\User as UserEnums;
use MobileApi\Api\Configure;
use MobileApi\Api\Session;
use MobileApi\Api\UserCenter;
use MobileApi\Exception\Exception;
use MobileApi\Exception\Http\HttpResponseException;
use MobileApi\Util\User\LoginInfoInterface;
use Service_Version_Model;
use Yaf\Registry;

class Request implements RequestInterface
{

    /**
     * Variable  _userId
     *
     * @author   wangxuan
     * @static
     * @var      null
     */
    protected static $_userId = null;

    /**
     * Variable  _realUserId 真实登录用户id
     *
     * @author   wangxuan
     * @static
     * @var      null
     */
    protected static $_realUserId = null;

    /**
     * Variable  _userInfo
     *
     * @author   wangxuan
     * @static
     * @var      null
     */
    protected static $_userInfo = null;

    /**
     * Variable  _deviceInfo
     *
     * @author   wangxuan
     * @static
     * @var      null
     */
    protected static $_deviceInfo = null;

    /**
     * Variable  _daikeSwitch
     *
     * @author   wangxuan
     * @static
     * @var      boolean
     */
    protected static $_daikeSwitch = false;

    /**
     * Variable  _daikes 数据结构 真实登录用户id=>被代客经纪人ID
     *
     * @author   wangxuan
     * @static
     * @var      array
     */
    protected static $_daikeUsers = [];

    /**
     * Variable 登陆信息处理
     *
     * @var LoginInfoInterface
     */
    protected static $_loginInfoHandler = null;

    /**
     * Method __construct
     *
     * @author wangxuan
     */
    private function __construct()
    {

    }

    /**
     * Method  init
     *
     * @author wangxuan
     * @static
     * @throws Exception
     */
    public static function init()
    {
        if (!self::isUserAgentExcludeUri()) {
            self::getClientInfo();
        }

        if (!self::isVerifyExcludeUri()) {
            self::getAccessToken();

            self::getUserId();
        }

        $positionCodeList = (array)Config::get('application.version.exclude.position_codes');

        if (!self::isVersionExcludeUri() && (!self::isVerifyExcludeUri() && !in_array(Request::getUserAttribute('positionCode'), $positionCodeList))) {
            if (Request::isAndroid()) {
                switch (Registry::get('request_uri')) {
                    case 'im/account/config.json':
                    case 'common/index/config.json':
                        break;
                    default:
                        self::verifyVersion();
                }
            } else {
                self::verifyVersion();
            }
        }
    }

    /**
     * Method  isUserAgentExcludeUri
     * 是否是新IM
     *
     * @return bool
     */
    public static function isNewIM()
    {
        return (bool)Server::get('http_im_version', 0);
    }

    public static function setLoginInfoHandler(LoginInfoInterface $loginInfoHandler)
    {
        static::$_loginInfoHandler = $loginInfoHandler;
    }

    /**
     * Method  isUserAgentExcludeUri
     * 是否在排除验证列表中
     *
     * @return mixed
     */
    public static function isUserAgentExcludeUri()
    {
        return in_array(Registry::get('request_uri'), Config::get('application.user_agent.exclude.uris'));
    }

    /**
     * Method  getClientInfo
     *
     * @author wangxuan
     * @static
     * @throws Exception
     * @return array|null
     */
    public static function getClientInfo()
    {
        if (self::$_deviceInfo !== null) {
            return self::$_deviceInfo;
        }

        $userAgent = self::getUserAgent();

        if (preg_match(ClientEnums::CLIENT_INFO_MATCH_PATTERN, $userAgent, $matches)) {
            $deviceInfo = [
                'channel'  => '',
                'platform' => '',
                'version'  => '',
            ];

            $channel = strtolower($matches[1]);

            if ($channel === ClientEnums::CHANNEL_LINK) {
                $deviceInfo['channel'] = ClientEnums::CHANNEL_LINK;
            } elseif ($channel === ClientEnums::CHANNEL_LINK_NEWHOUSE) {
                $deviceInfo['channel'] = ClientEnums::CHANNEL_LINK_NEWHOUSE;
            } else {
                throw new Exception('user_agent_error');
            }

            if (isset($matches[4])) {
                $deviceInfo['version'] = sprintf("%03s%03s%03s", $matches[2], $matches[3], $matches[4]);
            } else {
                $deviceInfo['version'] = '000000000';
            }

            if (stripos($userAgent, ClientEnums::PLATFORM_IOS) !== false) {
                $deviceInfo['platform'] = ClientEnums::PLATFORM_IOS;
            } elseif (stripos($userAgent, ClientEnums::PLATFORM_ANDROID) !== false) {
                $deviceInfo['platform'] = ClientEnums::PLATFORM_ANDROID;
            } else {
                throw new Exception('user_agent_error');
            }

            self::$_deviceInfo = $deviceInfo;

            return self::$_deviceInfo;
        }

        throw new Exception('user_agent_error');
    }

    /**
     * Method  getUserAgent
     *
     * @author wangxuan
     * @static
     * @return null|string
     */
    public static function getUserAgent()
    {
        if ((Env::isDevelop() || Env::isTest()) && !empty($_GET['debug'])) {
            $userAgent = Server::get('http_user_agent', null);
            if (!empty($userAgent) && stripos($userAgent, 'link') !== false) {
                return $userAgent;
            }

            return 'LINK/3.12.0 (iPhone; iOS 9.3.3; Scale/3.00)';
        }

        return Server::get('http_user_agent', null);
    }

    /**
     * Method  isVerifyExcludeUri
     * 是否在排除验证列表中
     *
     * @return mixed
     */
    public static function isVerifyExcludeUri()
    {
        return in_array(Registry::get('request_uri'), Config::get('application.verify.exclude.uris'));
    }

    /**
     * Method  getAccessToken
     *
     * @author wangxuan
     * @static
     * @throws Exception
     * @return null|string
     */
    public static function getAccessToken()
    {
        if ((Env::isDevelop() || Env::isTest()) && !empty($_GET['debug'])) {
            return '2.00';
        }

        $accessToken = Server::get(ClientEnums::ACCESS_TOKEN, null);

        if (empty($accessToken) || strpos($accessToken, '2.00') === false) {
            throw new Exception('token_verify_failed');
        }

        return $accessToken;
    }

    /**
     * Method  getUserId
     *
     * @author wangxuan
     * @static
     * @throws Exception
     * @throws \Exception
     * @return int|null
     */
    public static function getUserId()
    {
        if (Env::isDevelop() || Env::isTest()) {
            if (!empty($_GET['debug'])) {
                if (is_numeric($_GET['debug']) && strlen($_GET['debug']) === 16) {
                    self::$_userId = (int)$_GET['debug'];
                } else {
                    self::$_userId = (int)'1000000020207508';
                }

                self::setDaikeUserId();

                return self::$_userId;
            }
        }

        if (self::$_userId !== null) {
            // self::setDaikeUserId();

            return self::$_userId;
        }

        try {
            $session = Session::request('token_verify', ['token' => self::getAccessToken()]);
        } catch (HttpResponseException $exception) {
            if ((string)$exception->getCode() === '200003') {
                // token 验证失败后判定是否是被踢下线造成的
                if (empty(static::$_loginInfoHandler)) {
                    throw new Exception('token_verify_failed');
                }

                $userLoginInfo = static::$_loginInfoHandler->get(self::getAccessToken());

                if (!empty($userLoginInfo) && (int)$userLoginInfo['status'] === 0) {
                    throw new Exception('token_reject', ['date' => $userLoginInfo['modify_time']]);
                } else {
                    throw new Exception('token_verify_failed');
                }
            } elseif ((string)$exception->getCode() === '200004') {
                throw new Exception('token_expire');
            } else {
                throw new \Exception($exception->getMessage(), $exception->getCode());
            }
        }

        if (empty($session['user_info']['id']) && empty(self::$_userId)) {
            throw new Exception('token_verify_failed');
        }

        self::$_userId = (int)$session['user_info']['id'];

        self::setDaikeUserId();

        return self::$_userId;
    }

    /**
     * Method  setDaikeUserId
     *
     * @author wangxuan
     * @static
     */
    private static function setDaikeUserId()
    {
        if (empty(Config::get('application.daike_switch'))) {
            return;
        }

        $cache = new Cache();
        // 获取职位编码缓存
        $positionCodes = $cache->remember(CacheEnums::POSITION_CODE_LIST_KEY_TEMPLATE, CacheEnums::POSITION_CODE_LIST_EXPIRE_TIME, function () {
            $positionCodes = Configure::request('position_search', [
                'page'      => 1,
                'page_size' => Configure::INFINITE_PAGE_SIZE,
            ]);
            if (!empty($positionCodes['result']) && is_array($positionCodes['result'])) {
                return array_column($positionCodes['result'], null, 'code');
            } else {
                return false;
            }
        });

        $whitePositionCodes = Config::get('application.white_daike_position_code', []);

        //在职位编码列表中的，不走代客
        $userInfo = UserCenter::getAgentInfoById(self::$_userId, false);
        if (isset($userInfo['positionCode']) && isset($positionCodes[$userInfo['positionCode']]) && !in_array($userInfo['positionCode'], $whitePositionCodes)) {
            self::$_daikeSwitch = false;
        }

        if (self::$_daikeSwitch === false) {
            if (self::$_realUserId === null) {
                self::$_realUserId = self::$_userId;
            }
        } elseif (array_key_exists(self::$_userId, self::$_daikeUsers) === false) {
            self::$_realUserId = self::$_userId;

            self::$_daikeUsers[self::$_userId] = UserCenter::getDaikeUserIdByUserId(self::$_userId);

            if (!empty(self::$_daikeUsers[self::$_userId])) {
                self::$_userId = self::$_daikeUsers[self::$_userId];
            }

            if (!empty(static::$_loginInfoHandler) && Registry::get('request_uri') !== 'user/login.json') {
                $userLoginInfo = static::$_loginInfoHandler->get(self::getAccessToken());
                if ((!empty(self::$_daikeUsers[self::$_realUserId]) || !empty($userLoginInfo['daike_user_id']))
                    && ((string)self::$_daikeUsers[self::$_realUserId] !== (string)$userLoginInfo['daike_user_id'])
                ) {
                    static::$_loginInfoHandler->destroy(self::getAccessToken());
                    throw new Exception(empty((string)self::$_daikeUsers[self::$_realUserId]) ? 'remove_daike_status' : 'add_daike_status', ['date' => $userLoginInfo['modify_time']]);
                }
            }
        }
    }

    /**
     * Method  isVersionExcludeUri
     * 是否在排除验证列表中
     *
     * @return mixed
     */
    public static function isVersionExcludeUri()
    {
        return in_array(Registry::get('request_uri'), Config::get('application.version.exclude.uris'));
    }

    /**
     * 校验版本
     */
    public static function verifyVersion()
    {
        $version = Service_Version_Model::getVersion();

        if (empty($version) || empty($version['min_version']) || empty($version['max_version'])) {
            return true;
        }

        if (Version::isLessThan($version['min_version']) || Version::isGreaterThan($version['max_version'])) {

            $replaces = [
                'channel' => Config::get('application.app.' . self::getChannel() . '.name'),
                'url'     => $version['url'],
            ];

            throw new Exception($version['is_grayscale'] === false ? 'version_expire' : 'version_gray', $replaces);
        }
    }

    /**
     * Method  getVersion
     *
     * @author wangxuan
     * @static
     * @return mixed|null
     */
    public static function getVersion()
    {
        $deviceInfo = self::getClientInfo();

        return isset($deviceInfo['version']) ? Version::transToStringStyle($deviceInfo['version']) : null;
    }

    /**
     * Method  getChannel
     *
     * @author wangxuan
     * @static
     * @return mixed|null
     */
    public static function getChannel()
    {
        $deviceInfo = self::getClientInfo();

        return isset($deviceInfo['channel']) ? $deviceInfo['channel'] : null;
    }

    /**
     * getRealUserId
     *
     * @return null
     */
    public static function getRealUserId()
    {
        return self::$_realUserId;
    }

    /**
     * Method  isLink
     *
     * @author wangxuan
     * @static
     * @return bool
     */
    public static function isLink()
    {
        return self::getChannel() === ClientEnums::CHANNEL_LINK;
    }

    /**
     * Method  isLinkNewhouse
     *
     * @author wangxuan
     * @static
     * @return bool
     */
    public static function isLinkNewhouse()
    {
        return self::getChannel() === ClientEnums::CHANNEL_LINK_NEWHOUSE;
    }

    /**
     * Method  isIOS
     *
     * @author wangxuan
     * @static
     * @return bool
     */
    public static function isIOS()
    {
        return self::getPlatform() === ClientEnums::PLATFORM_IOS;
    }

    /**
     * Method  getPlatform
     *
     * @author wangxuan
     * @static
     * @return mixed|null
     */
    public static function getPlatform()
    {
        $deviceInfo = self::getClientInfo();

        return isset($deviceInfo['platform']) ? $deviceInfo['platform'] : null;
    }

    /**
     * Method  isAndroid
     *
     * @author wangxuan
     * @static
     * @return bool
     */
    public static function isAndroid()
    {
        return self::getPlatform() === ClientEnums::PLATFORM_ANDROID;
    }

    /**
     * Method  setDaikeSwitch
     *
     * @author wangxuan
     * @static
     *
     * @param $switch
     *
     * @return mixed
     */
    public static function setDaikeSwitch($switch)
    {
        return self::$_daikeSwitch = $switch;
    }

    /**
     * Method  isNewhouseAgent
     *
     * @author wangxuan
     * @static
     * @return mixed
     */
    public static function isNewhouseAgent()
    {
        return in_array(self::getUserAttribute('positionCode'), UserEnums::NEWHOUSE_AGENT_POSITION_CODES);
    }

    /**
     * Method  getUserAttribute
     *
     * @author wangxuan
     * @static
     *
     * @param $attribute
     *
     * @return null|mixed
     */
    public static function getUserAttribute($attribute)
    {
        $userInfo = self::getUserInfo();

        return isset($userInfo[$attribute]) ? $userInfo[$attribute] : null;
    }

    /**
     * Method  getUserInfo
     *
     * @author wangxuan
     * @static
     * @throws Exception
     * @return int|mixed|null|string
     */
    public static function getUserInfo()
    {
        if (self::$_userInfo !== null) {
            return self::$_userInfo;
        }

        $userId = self::getUserId();
        if (empty($userId)) {
            return $userId;
        }

        self::$_userInfo = UserCenter::getAgentInfoById($userId, false);

        return self::$_userInfo;
    }

    /**
     * Method  isNewhouseChannelMembers
     *
     * @author wangxuan
     * @static
     * @return mixed
     */
    public static function isNewhouseChannelMembers()
    {
        return in_array(self::getUserAttribute('positionCode'), UserEnums::NEWHOUSE_CHANNEL_MEMBERS_POSITION_CODES);
    }

    /**
     * Method  isLevelC
     *
     * @author zhaoxinyu01
     * @static
     * @return bool
     */
    public static function isLevelC(): bool
    {
        return in_array(self::getUserAttribute('positionCode'), UserEnums::LEVEL_C_POSITION_CODES);
    }

    /**
     * Method  isLevelD
     *
     * @author zhaoxinyu01
     * @static
     * @return bool
     */
    public static function isLevelD(): bool
    {
        return in_array(self::getUserAttribute('positionCode'), UserEnums::LEVEL_D_POSITION_CODES);
    }

    /**
     * Method  isLevelS
     *
     * @author zhaoxinyu01
     * @static
     * @return bool
     */
    public static function isLevelS(): bool
    {
        return in_array(self::getUserAttribute('positionCode'), UserEnums::LEVEL_S_POSITION_CODES);
    }

    /**
     * Method  isLevelAM
     *
     * @author zhaoxinyu01
     * @static
     * @return bool
     */
    public static function isLevelAOrM(): bool
    {
        return in_array(self::getUserAttribute('positionCode'), UserEnums::LEVEL_A_M_POSITION_CODES);
    }
}
