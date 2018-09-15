<?php
/**
 * Created by PhpStorm.
 * User: Lianjia
 * Date: 2016/8/26
 * Time: 17:15
 */
namespace MobileApi\Util;

class Filter
{
    /**
     * 过滤掉字符串中的emoji表情
     *
     * @param $text
     *
     * @see http://stackoverflow.com/questions/12807176/php-writing-a-simple-removeemoji-function
     * @return mixed|string
     */
    public static function emoji($text)
    {
        $clean_text = "";

        // Match Emoticons
        $regexEmoticons = '/[\x{1F600}-\x{1F64F}]/u';
        $clean_text     = preg_replace($regexEmoticons, '', $text);

        // Match Miscellaneous Symbols and Pictographs
        $regexSymbols = '/[\x{1F300}-\x{1F5FF}]/u';
        $clean_text   = preg_replace($regexSymbols, '', $clean_text);

        // Match Transport And Map Symbols
        $regexTransport = '/[\x{1F680}-\x{1F6FF}]/u';
        $clean_text     = preg_replace($regexTransport, '', $clean_text);

        // Match Miscellaneous Symbols
        $regexMisc = '/[\x{2600}-\x{26FF}]/u';
        $clean_text = preg_replace($regexMisc, '', $clean_text);

        // Match Dingbats
        $regexDingbats = '/[\x{2700}-\x{27BF}]/u';
        $clean_text    = preg_replace($regexDingbats, '', $clean_text);

        $regexDingbats = '/[\x{231a}-\x{23ab}\x{23e9}-\x{23ec}\x{23f0}-\x{23f3}]/u';
        $clean_text    = preg_replace($regexDingbats, '', $clean_text);

        return $clean_text;
    }

    /**
     * 在字符串的结尾删除指定字符
     * 在$string的结尾开始找$needle中的字符，找到就去掉，直到找不到为止。
     * 默认情况下是去除结尾的标点符号 '，。,.'
     *
     * @param string $string
     * @param array  $needle
     *
     * @return string
     */
    public static function deleteUnicodeCharAtTail($string, array $needle = [
        '，',
        ',',
        '.',
        '。',
    ])
    {

        $length = mb_strlen($string);

        while ($length > 0) {
            if (in_array(mb_substr($string, $length - 1, 1), $needle)) {
                $length--;
            } else {
                break;
            }
        }

        return mb_substr($string, 0, $length);

    }
}