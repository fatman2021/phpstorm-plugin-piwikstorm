<?php
namespace Piwik\Plugins\MyPlugin;

use Piwik\ApiClass;
use Piwik\ApiInterface;
use Piwik\DerivedClass;
use Piwik\Something\ClassWithApiMembers;
use Piwik\Something\DerivedClassWithApiMembers;
use AnotherLibrariesNamespace\Utility;

class MyTestClass
{
    public function myMethod()
    {
        // class w/ @api in Piwik namespace used
        $apiClass = new ApiClass();
        $apiClass->property = 1;
        $apiClass->doSomething();
        ApiClass::staticDoSomething();

        // class w/ @api in Piwik\Something namespace used
        $classWithApiMembers = new ClassWithApiMembers();
        $classWithApiMembers->property = 1;
        $classWithApiMembers->doSomethingElse();
        ClassWithApiMembers::staticDoSomething();

        // class whose ancestor marked w/ @api used
        $derivedApiClass = new DerivedClass();
        $derivedApiClass->property = 1;
        $derivedApiClass->doSomething();
        DerivedClass::staticDoSomething();

        // class whose ancestor has @api methods/properties
        $derivedClassWithApiMembers = new DerivedClassWithApiMembers();
        $derivedClassWithApiMembers->property = 1;
        $derivedClassWithApiMembers->doSomethingElse();
        DerivedClassWithApiMembers::staticDoSomething();

        // class in Piwik\Plugins\MyPlugin w/o @api that is used
        $localNonApiClass = new LocalNonApiClass();
        $localNonApiClass->property = 1;
        $localNonApiClass->doSomething();
        LocalNonApiClass::staticDoSomething();

        // class outside of Piwik w/o @api that is used
        $utility = new Utility();
        $utility->data = 1;
        $utility->doSomething();
        Utility::staticDoSomething();
    }
}

// test deriving from api class
class MyDerived extends ApiClass
{
    // empty
}

// test deriving from non-api class and overriding only api methods
class MyOtherDerived extends ClassWithApiMembers
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

// test implementing api interface
interface MyImplemented implements ApiInterface
{
    // empty
}