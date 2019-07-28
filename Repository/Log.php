<?php

namespace Truonglv\Api\Repository;

use XF\Mvc\Entity\Repository;

class Log extends Repository
{
    const MAX_ARRAY_ELEMENTS = 10;
    const MAX_STRING_LENGTH = 200;

    public function prepareDataForLog($data)
    {
        $json = json_decode($data, true);
        if (!is_array($json)) {
            return $this->prepareValueForLogging($data);
        }

        $results = [];
        foreach ($json as $key => $value) {
            if (is_array($value)) {
                $results[$key] = $this->prepareArrayForLogging($value);
            } else {
                $results[$key] = $this->prepareValueForLogging($value);
            }
        }

        return json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    protected function prepareValueForLogging($value)
    {
        if (is_string($value)) {
            if (strlen($value) > self::MAX_STRING_LENGTH) {
                $value = substr($value, 0, self::MAX_STRING_LENGTH) . ' (STRIPPED)';
            }

            return $value;
        }

        return $value;
    }

    protected function prepareArrayForLogging(array $data, $depth = 1)
    {
        if ($depth > 3) {
            return ['(...) (Too many depths)'];
        }

        $keys = array_keys($data);
        $arrayStripped = 0;

        if (count($keys) > self::MAX_ARRAY_ELEMENTS) {
            $arrayStripped = count($keys) - self::MAX_ARRAY_ELEMENTS;
            $keys = array_slice($keys, 0, self::MAX_ARRAY_ELEMENTS);
        }

        $results = [];
        foreach ($keys as $key) {
            $value = $data[$key];
            if (is_array($value)) {
                $results[$key] = $this->prepareArrayForLogging($value, $depth + 1);
            } else {
                $results[$key] = $this->prepareValueForLogging($value);
            }
        }

        if ($arrayStripped) {
            $results['(...)'] = '(Stripped ' . $arrayStripped . ' elements)';
        }

        return $results;
    }
}