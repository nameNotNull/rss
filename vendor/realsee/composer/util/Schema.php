<?php

namespace MobileApi\Util;

class Schema
{

    private static $_instances;

    private $_placeholder = '%s';

    public function __construct()
    {

    }

    /**
     * 模板解析
     *
     * @param array $templates
     *
     * @return array
     */
    public static function parse($templates = [])
    {
        if (empty($templates)) {
            return [];
        }

        $result = [];

        if (self::$_instances === null) {
            self::$_instances = new self();
        }

        if (array_key_exists('template', $templates)) {
            $result = self::$_instances->parseAction($templates);
        } else {
            foreach ($templates as $template) {
                $result[] = self::$_instances->parseAction($template);
            }
        }

        return $result;
    }

    /**
     * 模板解析操作
     *
     * @param array $template
     *
     * @return array
     */
    private function parseAction($template = [])
    {
        if (empty($template)) {
            return [];
        }

        $templateTextList = explode($this->_placeholder, $template['template']);

        //参数和占位符数量不匹配，匹配要求：参数大于等于占位符数量
        if (count($templateTextList) - count($template['params']) > 1) {
            return [];
        }

        $result = [];

        foreach ($templateTextList as $key => $templateTextItem) {
            //首位元素特殊处理
            if ($key === 0) {
                if (!empty($templateTextItem)) {
                    $result[] = [
                        'content' => (string)$templateTextItem,
                        'schema'  => '',
                        'logKey'  => '',
                    ];
                }
                continue;
            }

            $url                = '';
            $parameters         = [];
            $templateParamsItem = $template['params'][$key - 1];

            if (!empty($templateParamsItem['destination'])) {
                $config = Config::get('schema.' . $templateParamsItem['destination']);

                if (!empty($config)) {
                    $parameters = $this->parseParams($templateParamsItem['data'], $config);

                    //处理子参数
                    if (!empty($config['subParameter'])) {
                        foreach ($parameters as $parameterName => $parameterValue) {

                            if (!empty($config['subParameter'][$parameterName])) {
                                $subParameters = $this->parseParams($templateParamsItem['data'], $config['subParameter'][$parameterName]);

                                if ($subParameters !== false) {
                                    $parameters[$parameterName] = $parameterValue . (empty($subParameters) ? '' : '?' . http_build_query($subParameters));
                                } else {
                                    //缺少子参数，解析失败
                                    $parameters = false;
                                }
                            }

                        }
                        unset($parameter);
                    }

                    $url = $parameters === false ? '' : $config['url'];
                }
            }

            $result[] = [
                'content' => (string)$templateParamsItem['text'],
                'schema'  => $url . (empty($parameters) ? '' : '?' . http_build_query($parameters)),
                'logKey'  => (string)$templateParamsItem['logKey'],
            ];

            if (!empty($templateTextItem)) {
                $result[] = [
                    'content' => (string)$templateTextItem,
                    'schema'  => '',
                    'logKey'  => '',
                ];
            }
        }

        return $result;
    }

    /**
     * 变量解析
     *
     * @param array $requestParams
     * @param array $config
     *
     * @return array|bool
     */
    private function parseParams($requestParams = [], $config = [])
    {

        if (empty($config['parameter'])) {
            return [];
        }

        $configParameters = array_flip(explode(',', $config['parameter']));

        //变量替换
        if (!empty($config['mapping'])) {
            foreach (explode(',', $config['mapping']) as $mapping) {
                list($mappingKey, $parameterKey) = explode(':', $mapping);

                if (isset($requestParams[$parameterKey])) {
                    $requestParams[$mappingKey] = $requestParams[$parameterKey];
                    unset($requestParams[$parameterKey]);
                }
            }
        }

        //填充默认值
        if (!empty($config['default'])) {
            foreach ($config['default'] as $defaultKey => $value) {
                if (!isset($requestParams[$defaultKey])) {
                    $requestParams[$defaultKey] = $value;
                }
            }
        }

        //检查是否缺少参数
        if (!empty(array_diff_key($configParameters, $requestParams))) {
            return false;
        }

        return array_intersect_key($requestParams, $configParameters);
    }
}