<?php

use MobileApi\Util\Config;

class Console_Swagger_Create_Parameter_Controller extends Base_Controller
{

    public function process()
    {
        $swaggerPath = Config::get('application.swagger.bin.path');
        $swaggerDocsPath = Config::get('application.swagger.ui.path');

        system("php $swaggerPath " . APPLICATION_PATH . "/controllers/ -o $swaggerDocsPath", $retval);

        $oldSwagger        = json_decode(file_get_contents($swaggerDocsPath));
        $oldSwagger->paths = new StdClass;

        $outputConfig = array_keys(Config::get("response"));

        foreach ($outputConfig as $pathTmp) {
            $pathArray = explode('_', $pathTmp);
            $apiTag    = "default";

            if (!empty($pathArray[0]) && !empty($oldSwagger->tags)) {
                foreach ($oldSwagger->tags as $tag) {
                    if (strcasecmp($pathArray[0], $tag->name) === 0) {
                        $apiTag = $tag->name;
                    }
                }
            }
            $globalDescription = Config::get("validation.global.parameter");
            $outputParameter   = Config::get("response.{$pathTmp}");
            $config            = Config::get(strtolower("validation.{$pathTmp}"));

            $path = "/" . str_replace("_", "/", $pathTmp) . '.json';

            $oldSwagger->paths->$path = new StdClass;

            if (!empty($config['method'])) {
                if ($config['method'] === 'get') {
                    $oldSwagger->paths->$path->get                                                          = new StdClass;
                    $oldSwagger->paths->$path->get->summary                                                 = $outputParameter['summary'] ?? '';
                    $oldSwagger->paths->$path->get->description                                             = $outputParameter['description'] ?? '';
                    $oldSwagger->paths->$path->get->tags[0]                                                 = $apiTag;
                    $oldSwagger->paths->$path->get->responses                                               = new StdClass;
                    $oldSwagger->paths->$path->get->responses->{200}                                        = new StdClass;
                    $oldSwagger->paths->$path->get->responses->{200}->content                               = new StdClass;
                    $oldSwagger->paths->$path->get->responses->{200}->description = "正确返回";
                    $oldSwagger->paths->$path->get->responses->{200}->content->{'application/json'}         = new StdClass;
                    $oldSwagger->paths->$path->get->responses->{200}->content->{'application/json'}->schema = new StdClass;
                    $swaggerData                                                                            = &$oldSwagger->paths->$path->get->responses->{200}->content->{'application/json'}->schema;

                    if (!empty($outputParameter['data']['content'])) {
                        $descriptionData = Config::get("response.{$pathTmp}.data.description") ?? '';
                        $this->construct(json_decode($outputParameter['data']['content']), $swaggerData, $descriptionData);
                    }

                    //add parameters from validation.ini
                    if (!empty($config['rule'])) {
                        foreach ($config['rule'] as $name => $rule) {
                            $ruleItem                    = explode('|', $rule);
                            $parametersObj               = new StdClass;
                            $parametersObj->name         = $name;
                            $parametersObj->in           = "query";
                            $parametersObj->required     = substr($ruleItem[0], 0, 8) === "required" ? true : false;
                            $parametersObj->schema       = new StdClass;
                            $parametersObj->schema->type = $ruleItem[1];

                            if (isset($ruleItem[2]) && substr($ruleItem[2], 0, 2) === 'in') {
                                $enum = [];
                                $enumArr = explode(',', substr($ruleItem[2], 3));
                                foreach ($enumArr as $value) {
                                    $enum[] = $value;
                                }
                                $parametersObj->schema->enum = $enum;
                            }

                            if (isset($globalDescription['description'][$name])) {
                                $parametersObj->description = $globalDescription['description'][$name];
                            } elseif (isset($config['description'][$name])) {
                                $parametersObj->description = $config['description'][$name];
                            }

                            $oldSwagger->paths->$path->get->parameters[] = $parametersObj;
                        }
                    }


                } elseif ($config['method'] === 'post') {
                    $oldSwagger->paths->$path->post                                                          = new StdClass;
                    $oldSwagger->paths->$path->post->summary                                                 = $outputParameter['summary'] ?? '';
                    $oldSwagger->paths->$path->post->description                                             = $outputParameter['description'] ?? '';
                    $oldSwagger->paths->$path->post->tags[0]                                                 = $apiTag;
                    $oldSwagger->paths->$path->post->responses                                               = new StdClass;
                    $oldSwagger->paths->$path->post->responses->{200}                                        = new StdClass;
                    $oldSwagger->paths->$path->post->responses->{200}->description                           = "正确返回";
                    $oldSwagger->paths->$path->post->responses->{200}->content                               = new StdClass;
                    $oldSwagger->paths->$path->post->responses->{200}->content->{'application/json'}         = new StdClass;
                    $oldSwagger->paths->$path->post->responses->{200}->content->{'application/json'}->schema = new StdClass;
                    $swaggerData                                                                             = &$oldSwagger->paths->$path->post->responses->{200}->content->{'application/json'}->schema;

                    if (!empty($outputParameter['data']['content'])) {
                        $descriptionData = Config::get("response.{$pathTmp}.data.description") ?? '';
                        $this->construct(json_decode($outputParameter['data']['content']), $swaggerData, $descriptionData);
                    }

                    //add parameters from validation.ini

                    $oldSwagger->paths->$path->post->requestBody = new StdClass;
                    $oldSwagger->paths->$path->post->requestBody->required = true;
                    $oldSwagger->paths->$path->post->requestBody->content                               = new StdClass;
                    $oldSwagger->paths->$path->post->requestBody->content->{'application/x-www-form-urlencoded'}         = new StdClass;
                    $oldSwagger->paths->$path->post->requestBody->content->{'application/x-www-form-urlencoded'}->schema         = new StdClass;
                    $requestData = &$oldSwagger->paths->$path->post->requestBody->content->{'application/x-www-form-urlencoded'}->schema;
                    $requestData->type = "object";
                    $requestData->properties = new StdClass;

                    if (!empty($config['rule'])) {
                        foreach ($config['rule'] as $name => $rule) {
                            $ruleItem                    = explode('|', $rule);
                            $parametersObj               = new StdClass;
                            $parametersObj->required     = substr($ruleItem[0], 0, 8) === "required" ? true : false;
                            if ($ruleItem[1] === 'numeric') {
                                $parametersObj->type = 'number';
                            } else {
                                $parametersObj->type = $ruleItem[1];
                            }
                            if (isset($ruleItem[2]) && substr($ruleItem[2], 0, 2) === 'in') {
                                $enum = [];
                                $enumArr = explode(',', substr($ruleItem[2], 3));
                                foreach ($enumArr as $value) {
                                    $enum[] = $value;
                                }
                                $parametersObj->enum = $enum;
                            }

                            // post descriptions are not shown, so show  description in example
                            if (isset($globalDescription['description'][$name])) {
                                $parametersObj->example = $globalDescription['description'][$name];
                            } elseif (isset($config['description'][$name])) {
                                $parametersObj->example = $config['description'][$name];
                            }
                            $requestData->properties->$name = $parametersObj;
                        }
                    }
                }
            }
        }

        file_put_contents($swaggerDocsPath, json_encode($oldSwagger));

        return true;
    }


    function construct($data, &$swaggerDataTmp, $description)
    {
        $attributes           = get_object_vars($data);
        $swaggerDataTmp->type = "object";
        if (is_string($description) && $description !== '') {
            $swaggerDataTmp->description = $description;
        }
        $swaggerDataTmp->properties = new StdClass;
        foreach ($attributes as $attributesName => $attributesValue) {
            if (is_object($attributesValue)) {
                $swaggerDataTmp->properties->$attributesName = new StdClass;
                $descriptionNew                              = $description[$attributesName] ?? '';
                $this->construct($attributesValue, $swaggerDataTmp->properties->$attributesName, $descriptionNew);
            } elseif (is_array($attributesValue)) {
                $parametersObj        = new StdClass;
                $parametersObj->type  = "array";
                $parametersObj->items = new StdClass;
                if (is_object($attributesValue[0])) {
                    $swaggerDataTmp->properties->$attributesName        = new StdClass;
                    $swaggerDataTmp->properties->$attributesName->type  = "array";
                    $swaggerDataTmp->properties->$attributesName->items = new StdClass;
                    $descriptionNew                                     = $description[$attributesName] ?? '';
                    $this->construct($attributesValue[0], $swaggerDataTmp->properties->$attributesName->items, $descriptionNew);
                } else {
                    if (is_integer($attributesValue[0])) {
                        $parametersObj->items->type = 'integer';
                    } elseif (is_bool($attributesValue[0])) {
                        $parametersObj->items->type = 'boolean';
                    } elseif (is_numeric($attributesValue[0])) {
                        $parametersObj->items->type = 'numeric';
                    } elseif (is_array($attributesValue[0])) {
                        $parametersObj->items->type = 'arrays';
                    } else {
                        $parametersObj->items->type = 'string';
                    }
                    $parametersObj->description                  = $description[$attributesName] ?? '';
                    $parametersObj->example                      = $attributesValue ?? '';
                    $swaggerDataTmp->properties->$attributesName = $parametersObj;
                }

            } elseif (is_integer($attributesValue)) {
                $parametersObj                               = new StdClass;
                $parametersObj->example                      = $attributesValue ?? '';
                $parametersObj->type                         = 'integer';
                $parametersObj->description                  = $description[$attributesName] ?? '';
                $swaggerDataTmp->properties->$attributesName = $parametersObj;
            } elseif (is_bool($attributesValue)) {
                $parametersObj                               = new StdClass;
                $parametersObj->example                      = $attributesValue ?? '';
                $parametersObj->type                         = 'boolean';
                $parametersObj->description                  = $description[$attributesName] ?? '';
                $swaggerDataTmp->properties->$attributesName = $parametersObj;
            } elseif (is_numeric($attributesValue)) {
                $parametersObj                               = new StdClass;
                $parametersObj->example                      = $attributesValue ?? '';
                $parametersObj->type                         = 'numeric';
                $parametersObj->description                  = $description[$attributesName] ?? '';
                $swaggerDataTmp->properties->$attributesName = $parametersObj;
            } else {
                $parametersObj                               = new StdClass;
                $parametersObj->example                      = $attributesValue ?? '';
                $parametersObj->type                         = 'string';
                $parametersObj->description                  = $description[$attributesName] ?? '';
                $swaggerDataTmp->properties->$attributesName = $parametersObj;
            }
        }

    }

}