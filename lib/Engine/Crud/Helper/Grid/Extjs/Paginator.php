<?php
/**
 * @namespace
 */
namespace Engine\Crud\Helper\Grid\Extjs;

use Engine\Crud\Grid\Extjs as Grid;

/**
 * Class grid paginator helper
 *
 * @category   Engine
 * @package    Crud
 * @subpackage Helper
 */
class Paginator extends BaseHelper
{
    /**
     * Generates grid paginate object
     *
     * @param \Engine\Crud\Grid\Extjs $grid
     * @return string
     */
    static public function _(Grid $grid)
    {
        $action = $grid->getAction();
        $sortParams = $grid->getSortParams();

        if ($sortParams) {
            foreach ($sortParams as $param => $value) {
                $action = self::setUrlParam($action, $param, $value);
            }
        }

        $code = "
            getBottomToolbarItems: function() {
                var me = this;

                var items = [
                    me.getPagingToolbar()
                ];

                return items;
            },";

        return $code;
    }

    /*
     * function setUrlParam sets parameters values in URL
     * $url - URL to set parameters in
     * $paramName - array of parameters names
     *              if one parameter to set $paramName can be string
     * $paramValue - array of parameters values
     *               if one parameter to set $paramValue can be string
     * $paramName and $paramValue must be same size arrays!
     *
     * if not set $paramValue - $paramName must be
     * array of names and values: array(name1=>value1, name2=>value2)
     */
    static function setUrlParam($url, $paramName, $paramValue = null, $urlDecode = false)
    {
        if (!is_array($paramName)) {
            $paramName = [$paramName];
        }
        if ($paramValue !== null) {
            if (!is_array($paramValue)) {
                $paramValue = array($paramValue);
            }
            if (($paramsArray = array_combine($paramName, $paramValue)) === false) {
                return $url;
            }
        } else {
            $paramsArray = $paramName;
        }
        $parse_url = parse_url($url);
        $url = '';
        if (isset($parse_url['scheme']) && isset($parse_url['host'])) {
            $url .= $parse_url['scheme'].'://'.$parse_url['host'].$parse_url['path'];
        }
        if (isset($parse_url['path'])) {
            $url .= $parse_url['path'];
        }
        $parse_str = [];
        if (isset($parse_url['query'])) {
            $parse_str = self::parseStr($parse_url['query']);
        }
        $parse_str = array_merge($parse_str, $paramsArray);
        $query = '';
        if ($query = http_build_query($parse_str)) {
            $url .= '?'.$query;
        }
        if ($urlDecode) {
            $url = urldecode($url);
        }

        return $url;
    }

    /**
     * Clear query params from url
     *
     * @param $url
     * @param $clearArray
     * @return string
     */
    static function clearUrlParam($url, $clearArray)
    {
        $parse_url = parse_url($url);
        $url = $parse_url['scheme'].'://'.$parse_url['host'].$parse_url['path'];
        $parse_str = self::parseStr($parse_url['query']);
        foreach ($clearArray as $paramName) {
            unset($parse_str[$paramName]);
        }
        $query = '';
        if ($query = http_build_query($parse_str)) {
            $url .= '?'.$query;
        }

        return $url;
    }

    /**
     * @param $urlParamStr
     * @return array
     */
    static function parseStr($urlParamStr)
    {
        $paramArr = explode('&', $urlParamStr);
        $return = [];
        foreach ($paramArr as $param) {
            $tmp = explode('=', $param);
            if ($tmp[0])
                $return[$tmp[0]] = $tmp[1];
        }
        return $return;
    }
}