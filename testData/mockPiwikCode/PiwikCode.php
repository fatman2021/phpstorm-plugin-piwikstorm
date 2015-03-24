<?php

namespace Piwik
{
    /**
     * @api
     */
    class ApiClass
    {
        public $property;

        public function doSomething()
        {
            // empty
        }

        public static function staticDoSomething()
        {
            // empty
        }
    }

    class DerivedClass extends ApiClass
    {
        // empty
    }

    class NonApiClass
    {
        public $property;

        public function doSomething()
        {
            // empty
        }

        public static function staticDoSomething()
        {
            // empty
        }
    }

    interface NonApiInterface
    {
        // empty
    }

    /**
     * @api
     */
    interface ApiInterface
    {
        // empty
    }
}

namespace Piwik\Something
{
    class ClassWithApiMembers
    {
        /**
         * @api
         */
        public $property;

        public $nonApiProperty;

        /**
         * @api
         */
        public function doSomethingElse()
        {
            // empty
        }

        public function nonApiDoSomethingElse()
        {
            // empty
        }

        /**
         * @api
         */
        public static function staticDoSomething()
        {
            // empty
        }

        public static function nonApiStaticDoSomething()
        {
            // empty
        }
    }

    class DerivedClassWithApiMembers extends ApiClass
    {
        // empty
    }

    class NonApiClass
    {
        public $property;

        public function doSomething()
        {
            // empty
        }

        public static function staticDoSomething()
        {
            // empty
        }
    }
}

namespace Piwik\Plugins\AnotherPlugin
{
    /**
     * @api
     */
    class PluginApiClass
    {
        public $property;

        public function doSomething()
        {
            // empty
        }
    }

    class PluginClassWithApiMembers
    {
        /**
         * @api
         */
        public $property;

        /**
         * @api
         */
        public function doSomethingElse()
        {
            // empty
        }
    }

    class NonApiClass
    {
        public $property;

        public function doSomething()
        {
            // empty
        }

        public static function staticDoSomething()
        {
            // empty
        }
    }
}

namespace Piwik\Plugins\MyPlugin
{
    class LocalNonApiClass
    {
        public $property;

        public function doSomething()
        {
            // empty
        }

        public static function staticDoSomething()
        {
            // empty
        }
    }
}

namespace AnotherLibrariesNamespace
{
    class Utility
    {
        public $data;

        public function doSomething()
        {
            // empty
        }

        public static function staticDoSomething()
        {
            // empty
        }
    }
}