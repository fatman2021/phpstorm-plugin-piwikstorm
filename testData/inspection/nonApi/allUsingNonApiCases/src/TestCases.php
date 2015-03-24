<?php
namespace Piwik\Plugins\MyPlugin;

use Piwik\Something\ClassWithApiMembers;
use Piwik\NonApiClass;
use Piwik\NonApiInterface;
use Piwik\Something\NonApiClass as SomethingNonApiClass;
use Piwik\Plugins\AnotherPlugin\NonApiClass as AnotherNonApiClass;

class MyTestClass
{
    public function myMethod()
    {
        // class in Piwik namespace (w/o @api annotation) used
        $nonApiClass = new NonApiClass();
        $nonApiClass->property = 1;
        $nonApiClass->doSomething();
        NonApiClass::staticDoSomething();

        // class in Piwik\Something namespace (w/o @api annotation) used
        $somethingNonApiClass = new SomethingNonApiClass();
        $somethingNonApiClass->property = 1;
        $somethingNonApiClass->doSomething();
        SomethingNonApiClass::staticDoSomething();

        // class in Piwik\Plugins\AnotherPlugin namespace (w/o @api annotation) used
        $anotherNonApiClass = new AnotherNonApiClass();
        $anotherNonApiClass->property = 1;
        $anotherNonApiClass->doSomething();
        AnotherNonApiClass::staticDoSomething();

        // non-api method/property use of class w/ some @api methods/properties
        $classWithApiMembers = new ClassWithApiMembers();
        $classWithApiMembers->nonApiProperty = 1;
        $classWithApiMembers->nonApiDoSomethingElse();
        ClassWithApiMembers::nonApiStaticDoSomething();
    }
}

// test deriving from class that is not API
class MyDerived extends NonApiClass
{
    // empty
}

// test deriving from API class and overriding non-API methods
class MyOtherDerived extends SomethingNonApiClass
{
    public $property = 'other';

    public function doSomethingElse()
    {
        // empty
    }

    public static function staticDoSomething()
    {
        // empty
    }
}

// test implementing non-API interface
class MyImplemented implements NonApiInterface
{
    // empty
}