<?php

namespace dwy\FacebookConversion\logger;

use Craft;
use FacebookAds\Http\FileParameter;
use FacebookAds\Http\Parameters;
use FacebookAds\Http\RequestInterface;
use FacebookAds\Logger\CurlLogger;
use FacebookAds\Logger\CurlLogger\JsonAwareParameters;

class CraftLogger extends CurlLogger
{
    /**
     * @param Parameters $params
     * @param string $method
     * @param bool $is_file
     * @return string
     */
    protected function processParams(Parameters $params, $method, $is_file)
    {
        $chunks = array();

        if ($this->isJsonPrettyPrint()) {
            $params = new JsonAwareParameters($params);
        }

        foreach ($params->export() as $name => $value) {
            if ($is_file && $params->offsetGet($name) instanceof FileParameter) {
                $value = "@" . $this->normalizeFileParam($params->offsetGet($name));
            } else {
                $value = addcslashes(
                strpos($value, "\n") !== false
                    ? $this->indent($value, 2)
                    : $value,
                '\'');
            }

            if ($name === 'access_token') {
                $value = substr($value, 0, 5) . '***';
            }

            $chunks[$name] = sprintf(
                '-%s \'%s=%s\'',
                $this->getParamFlag($method, $value),
                $name,
                $value);
        }

        return $chunks;
    }

    /**
     * @param string $level
     * @param RequestInterface $request
     * @param array $context
     */
    public function logRequest($level, RequestInterface $request, array $context = array())
    {
        $new_line = ' \\'.PHP_EOL.'  ';
        $method = $request->getMethod();
        $method_flag = static::getMethodFlag($method);
        $params = $this->sortParams(array_merge(
            $this->processParams($request->getQueryParams(), $method, false),
            $this->processParams($request->getBodyParams(), $method, false),
            $this->processParams($request->getFileParams(), $method, true)
        ));

        $buffer = 'curl'.($method_flag ? ' -'.$method_flag : '');

        foreach ($params as $param) {
            $buffer .= $new_line.$param;
        }

        $buffer .= $new_line.$this->processUrl($request);

        Craft::info($buffer);
    }
}
